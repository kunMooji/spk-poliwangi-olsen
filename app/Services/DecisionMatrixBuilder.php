<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\Criteria;
use App\Models\Setting;
use App\Models\StudyProgram;
use Illuminate\Support\Collection;

/**
 * Menyusun matriks keputusan x_ij dari data sesi tes dan data master.
 *
 * Nilai tiap sel ditentukan oleh kolom `criteria.source`, sehingga admin bisa
 * menambah atau menonaktifkan kriteria tanpa mengubah kode perhitungan.
 */
final class DecisionMatrixBuilder
{
    /**
     * Rentang teoretis nilai x_ij per jenis sumber, dipakai sebagai batas
     * normalisasi tetap menggantikan batas dari sampel alternatif.
     *
     * Aturannya: kriteria yang besarannya punya makna absolut dinormalisasi
     * terhadap skala bakunya, sedangkan kriteria yang maknanya relatif terhadap
     * alternatif lain tetap memakai min-max sampel seperti CoCoSo bawaan.
     *
     * `subject_score` (C1..C6) — nilainya berbentuk `nilai_rapor × relevansi`,
     * dengan nilai rapor sama untuk seluruh prodi pada satu sesi tes. Batas dari
     * sampel mencoret nilai rapor tersebut secara aljabar (lihat
     * CocosoService::calculate), sehingga rapor bagus dan rapor jelek
     * menghasilkan peringkat yang persis sama. Batas tetap 0..100 — rapor 0..100
     * dikali relevansi 0..1 — mengembalikan pengaruh nilai rapor, sekaligus
     * menghilangkan risiko `max - min = 0` pada kolom ini.
     *
     * `tracer` (C9) — rasio keterserapan kerja bermakna apa adanya: 0.80 berarti
     * 80% alumni terserap, bukan "terburuk di antara prodi lain". Batas sampel
     * merentangkan selisih nyata yang kecil (mis. 0.78..0.89) menjadi 0..1 penuh,
     * sehingga beda 11 poin diperlakukan seolah beda terburuk lawan terbaik dan
     * C9 mendominasi hasil jauh melebihi bobotnya.
     *
     * `riasec` (C7) dan `priority` (C8) sengaja tidak masuk daftar ini. Cosine
     * similarity tidak punya tafsir absolut — 69 bukan berarti "69% cocok" —
     * dan urutan prioritas hanya bermakna relatif terhadap pilihan lain pada
     * sesi tes yang sama. Untuk keduanya, perbandingan antar alternatif justru
     * tafsir yang benar, sehingga min-max sampel dipertahankan.
     */
    private const FIXED_BOUNDS = [
        'subject_score' => ['min' => 0.0, 'max' => 100.0],
        'tracer' => ['min' => 0.0, 'max' => 1.0],
    ];

    public function __construct(private readonly RiasecService $riasec) {}

    /**
     * Batas normalisasi tetap untuk kriteria yang memerlukannya.
     *
     * Menerima peta `[kodeKriteria => source]` — bukan koleksi model — supaya
     * bisa dipakai baik dari data kriteria yang aktif maupun dari
     * `weights_snapshot` sesi tes lama pada analisis sensitivitas.
     *
     * @param  array<string, string|null>  $sources
     * @return array<string, array{min: float, max: float}>
     */
    public static function boundsFor(array $sources): array
    {
        $bounds = [];

        foreach ($sources as $code => $source) {
            if ($source !== null && isset(self::FIXED_BOUNDS[$source])) {
                $bounds[$code] = self::FIXED_BOUNDS[$source];
            }
        }

        return $bounds;
    }

    /**
     * @param  Collection<int, StudyProgram>  $programs
     * @param  Collection<int, Criteria>  $criteria
     * @return array<int, array<string, float>> [studyProgramId => [kodeKriteria => x_ij]]
     */
    public function build(Assessment $assessment, Collection $programs, Collection $criteria): array
    {
        $subjectScores = $assessment->subjectScores();
        $studentVector = $assessment->riasecPercentages();
        $priorityRanks = $this->priorityRanks($assessment);
        $priorityCount = count($priorityRanks);
        $unselectedScore = (float) Setting::get('unselected_priority_score');

        $matrix = [];

        foreach ($programs as $program) {
            $row = [];

            foreach ($criteria as $criterion) {
                $row[$criterion->code] = $this->resolve(
                    $criterion,
                    $program,
                    $subjectScores,
                    $studentVector,
                    $priorityRanks,
                    $priorityCount,
                    $unselectedScore,
                );
            }

            $matrix[$program->id] = $row;
        }

        return $matrix;
    }

    /**
     * @param  array<string, float>  $subjectScores
     * @param  array<string, float>  $studentVector
     * @param  array<int, int>  $priorityRanks
     */
    private function resolve(
        Criteria $criterion,
        StudyProgram $program,
        array $subjectScores,
        array $studentVector,
        array $priorityRanks,
        int $priorityCount,
        float $unselectedScore,
    ): float {
        return match ($criterion->source) {
            // C1..C6 — nilai rapor dikalikan bobot relevansi mapel pada prodi terkait.
            // Perkalian inilah yang membuat kolom nilai rapor berbeda antar alternatif;
            // tanpa itu seluruh baris bernilai sama dan normalisasi min-max gagal.
            'subject_score' => $this->subjectValue($criterion, $program, $subjectScores),

            // C7 — cosine similarity vektor RIASEC.
            'riasec' => $this->riasec->compatibility($studentVector, $program->riasecVector()),

            // C8 — konversi urutan prioritas menjadi skor 0-100.
            'priority' => $this->priorityValue($program, $priorityRanks, $priorityCount, $unselectedScore),

            // C9 — rasio alumni terserap kerja (0.00 - 1.00).
            'tracer' => (float) $program->employment_rate,

            default => 0.0,
        };
    }

    /**
     * @param  array<string, float>  $subjectScores
     */
    private function subjectValue(Criteria $criterion, StudyProgram $program, array $subjectScores): float
    {
        $subject = (string) $criterion->subject;

        $score = (float) ($subjectScores[$subject] ?? 0.0);
        $relevance = (float) ($program->subjectRelevance()[$subject] ?? 0.0);

        return $score * $relevance;
    }

    /**
     * Prioritas ke-1 memperoleh 100, prioritas terakhir memperoleh 100/N.
     * Prodi di luar daftar pilihan memperoleh nilai bawaan dari pengaturan.
     *
     * @param  array<int, int>  $priorityRanks
     */
    private function priorityValue(
        StudyProgram $program,
        array $priorityRanks,
        int $priorityCount,
        float $unselectedScore,
    ): float {
        if ($priorityCount === 0 || ! isset($priorityRanks[$program->id])) {
            return $unselectedScore;
        }

        return ($priorityCount - $priorityRanks[$program->id] + 1) / $priorityCount * 100;
    }

    /**
     * @return array<int, int> [studyProgramId => urutan prioritas]
     */
    private function priorityRanks(Assessment $assessment): array
    {
        return $assessment->priorities
            ->pluck('priority_order', 'study_program_id')
            ->map(fn ($order) => (int) $order)
            ->all();
    }
}
