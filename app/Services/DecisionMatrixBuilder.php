<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\Criteria;
use App\Models\Setting;
use App\Models\StudyProgram;
use App\Models\Subject;
use App\Support\Rapor;
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
     * `rapor_average` (C1) — rerata rapor bernilai persis sama untuk seluruh
     * prodi pada satu sesi tes, sehingga kolomnya konstan dan `max - min = 0`.
     * Dengan batas sampel, CocosoService::normalize memberi 1.0 kepada semua
     * alternatif dan nilai rapor 95 tak terbedakan dari 55. Batas tetap 0..100
     * membuat rapor terbaca pada skala aslinya. Perlu dicatat: kriteria ini
     * memang tidak mengubah urutan peringkat — ia menambah konstanta yang sama
     * ke S_i dan P_i setiap alternatif. Itu konsekuensi wajar karena SNBP
     * memeringkat siswa untuk satu prodi, sedangkan sistem ini memeringkat prodi
     * untuk satu siswa. Pembeda nilai rapor antar prodi ada pada C2.
     *
     * `support_subject` (C2) — rerata nilai pada mapel pendukung prodi, berskala
     * 0..100 sama seperti rapor aslinya. Batas sampel akan merentangkan selisih
     * kecil antar prodi menjadi 0..1 penuh, sehingga beda beberapa poin terbaca
     * seolah beda terburuk lawan terbaik.
     *
     * `tracer` (C5) — rasio keterserapan kerja bermakna apa adanya: 0.80 berarti
     * 80% alumni terserap, bukan "terburuk di antara prodi lain". Batas sampel
     * merentangkan selisih nyata yang kecil (mis. 0.78..0.89) menjadi 0..1 penuh,
     * sehingga beda 11 poin diperlakukan seolah beda terburuk lawan terbaik dan
     * C5 mendominasi hasil jauh melebihi bobotnya.
     *
     * `riasec` (C3) dan `priority` (C4) sengaja tidak masuk daftar ini. Cosine
     * similarity tidak punya tafsir absolut — 69 bukan berarti "69% cocok" —
     * dan urutan prioritas hanya bermakna relatif terhadap pilihan lain pada
     * sesi tes yang sama. Untuk keduanya, perbandingan antar alternatif justru
     * tafsir yang benar, sehingga min-max sampel dipertahankan.
     */
    private const FIXED_BOUNDS = [
        'rapor_average' => ['min' => 0.0, 'max' => 100.0],
        'support_subject' => ['min' => 0.0, 'max' => 100.0],
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
        $raporAverage = (float) $assessment->rapor_average;
        $lowestSemesterAverage = (float) $assessment->raporSemesters->min('average_score');
        $subjectScores = $assessment->subjectScoreMap();
        $studentVector = $assessment->riasecPercentages();
        $priorityRanks = $this->priorityRanks($assessment);
        $priorityCount = count($priorityRanks);
        $unselectedScore = (float) Setting::get('unselected_priority_score');

        $matrix = [];

        foreach ($programs as $program) {
            $row = [];
            $isPriority = isset($priorityRanks[$program->id]);

            foreach ($criteria as $criterion) {
                $row[$criterion->code] = $this->resolve(
                    $criterion,
                    $program,
                    $raporAverage,
                    $lowestSemesterAverage,
                    $isPriority,
                    $subjectScores,
                    $studentVector,
                    $priorityRanks,
                    $priorityCount,
                    $unselectedScore,
                    $assessment->education_level,
                    $assessment->school_major,
                );
            }

            $matrix[$program->id] = $row;
        }

        return $matrix;
    }

    /**
     * @param  array<int, float|null>  $subjectScores
     * @param  array<string, float>  $studentVector
     * @param  array<int, int>  $priorityRanks
     */
    private function resolve(
        Criteria $criterion,
        StudyProgram $program,
        float $raporAverage,
        float $lowestSemesterAverage,
        bool $isPriority,
        array $subjectScores,
        array $studentVector,
        array $priorityRanks,
        int $priorityCount,
        float $unselectedScore,
        ?string $educationLevel,
        ?string $schoolMajor,
    ): float {
        return match ($criterion->source) {
            // C1 — rerata rapor seluruh mapel. Sama untuk setiap prodi, karena ini
            // atribut calon mahasiswa dan bukan atribut alternatif.
            'rapor_average' => $raporAverage,

            // C2 — rerata nilai pada mapel pendukung prodi terkait. Inilah kolom yang
            // membedakan nilai rapor antar alternatif.
            'support_subject' => $this->supportSubjectValue(
                $program,
                $raporAverage,
                $lowestSemesterAverage,
                $isPriority,
                $subjectScores,
                $educationLevel,
                $schoolMajor,
            ),

            // C3 — cosine similarity vektor RIASEC.
            'riasec' => $this->riasec->compatibility($studentVector, $program->riasecVector()),

            // C4 — konversi urutan prioritas menjadi skor 0-100.
            'priority' => $this->priorityValue($program, $priorityRanks, $priorityCount, $unselectedScore),

            // C5 — rasio alumni terserap kerja (0.00 - 1.00).
            'tracer' => (float) $program->employment_rate,

            default => 0.0,
        };
    }

    /**
     * Rerata nilai responden pada mata pelajaran pendukung prodi.
     *
     * Formulir hanya menanyakan mapel pendukung milik prodi yang responden
     * jadikan prioritas — bukan gabungan seluruh prodi aktif seperti dulu.
     * Konsekuensinya, C2 punya dua jalur nilai yang sengaja berbeda:
     *
     * **Prodi prioritas** — dihitung dari nilai asli, dengan mapel yang
     * relevan tetapi kosong (responden melewatkan isiannya) jatuh ke rerata
     * rapor. Ini "data hilang secara tidak sengaja": responden memang ditanya,
     * hanya lupa mengisi, sehingga wajar diperlakukan netral.
     *
     * **Prodi non-prioritas** — nilainya diganti nilai semester terendah
     * responden, bukan rerata. Ini bukan "data hilang", tapi "memang tidak
     * dievaluasi": responden tidak pernah ditanya mapel pendukung prodi ini
     * sama sekali. Menerapkan kriteria maximin (Wald) — mengambil skenario
     * paling konservatif saat tidak ada informasi — mencegah prodi yang tidak
     * dipertimbangkan responden diam-diam diuntungkan rerata yang belum tentu
     * benar, sehingga prodi prioritas tidak kalah bersaing hanya karena
     * lemah di satu mapel spesifik. Penaltinya otomatis proporsional: kalau
     * nilai responden stabil antarsemester, semester terendah hampir sama
     * dengan reratanya; kalau timpang, penaltinya lebih terasa.
     *
     * Yang dirata-ratakan untuk prodi prioritas hanya mapel yang benar-benar
     * ditempuh oleh jenjang dan jurusan responden — lihat Rapor::appliesTo().
     *
     * @param  array<int, float|null>  $subjectScores
     */
    private function supportSubjectValue(
        StudyProgram $program,
        float $raporAverage,
        float $lowestSemesterAverage,
        bool $isPriority,
        array $subjectScores,
        ?string $educationLevel,
        ?string $schoolMajor,
    ): float {
        if (! $isPriority) {
            return $lowestSemesterAverage;
        }

        $subjects = $program->supportSubjects
            ->filter(fn (Subject $subject) => Rapor::appliesTo($subject, $educationLevel, $schoolMajor));

        if ($subjects->isEmpty()) {
            return $raporAverage;
        }

        $total = 0.0;

        foreach ($subjects as $subject) {
            $total += $subjectScores[$subject->id] ?? $raporAverage;
        }

        return $total / $subjects->count();
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
