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
     * `riasec` (C3) — sejak kesesuaian dihitung dengan korelasi Pearson alih-alih
     * cosine, skalanya punya tafsir absolut: 100 searah sempurna, 50 tidak
     * berkorelasi, 0 berlawanan. Batas tetap 0..100 karena itu tepat, dan justru
     * wajib. Selama C3 memakai batas sampel, rentangnya SELALU terentang ke 0..1
     * penuh berapa pun sebaran aslinya, sehingga daya pisahnya selalu persis
     * sebesar bobotnya — terukur 79% dari seluruh daya pisah pada data nyata,
     * padahal bobotnya 0.35. Kriteria berbatas tetap hanya menyumbang sebesar
     * rentang nyatanya, jadi mencampur kedua perlakuan membuat angka pada kolom
     * `weight` berhenti mewakili pengaruh yang sebenarnya.
     *
     * `priority` (C4) — nilainya memang dibangkitkan pada skala 0..100 oleh
     * priorityValue(), jadi batas tetap itu sekadar menyatakan skala yang sudah
     * ada. Manfaatnya pada ketahanan: bila admin mengubah
     * `unselected_priority_score` menjadi mis. 30, batas sampel akan merentangkan
     * 30..100 kembali menjadi 0..1 penuh dan diam-diam mengembalikan pengaruh C4
     * ke ukuran semula.
     */
    private const FIXED_BOUNDS = [
        'rapor_average' => ['min' => 0.0, 'max' => 100.0],
        'support_subject' => ['min' => 0.0, 'max' => 100.0],
        'riasec' => ['min' => 0.0, 'max' => 100.0],
        'priority' => ['min' => 0.0, 'max' => 100.0],
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
        $subjectScores = $assessment->subjectScoreMap();
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
                    $raporAverage,
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
                $subjectScores,
                $educationLevel,
                $schoolMajor,
            ),

            // C3 — korelasi Pearson vektor RIASEC, dipetakan ke skala 0..100.
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
     * Dihitung dengan cara yang sama untuk setiap prodi, dipilih responden atau
     * tidak. Yang dirata-ratakan hanya mapel yang benar-benar ditempuh oleh
     * jenjang dan jurusan responden — lihat Rapor::appliesTo() — sehingga jumlah
     * mapel penilai per responden tetap paling banyak dua sesuai aturan SNBP.
     * Mapel yang relevan tetapi dikosongkan responden jatuh ke rerata rapor:
     * pertanyaannya diajukan, hanya tidak dijawab, jadi diperlakukan netral.
     *
     * **Catatan atas perilaku sebelumnya.** Prodi non-prioritas dulu langsung
     * diberi nilai semester terendah responden dengan alasan maximin (Wald) —
     * bertindak konservatif saat tidak ada informasi. Alasan itu bertumpu pada
     * premis bahwa formulir hanya menanyakan mapel pendukung prodi prioritas.
     * Premisnya tidak benar: AssessmentController memuat Rapor::supportSubjects(),
     * yaitu gabungan mapel pendukung SELURUH prodi aktif, dan
     * StoreAssessmentRequest menyimpan nilainya untuk semua mapel itu. Pada data
     * nyata sistem ini, 10 sampai 17 prodi non-prioritas per sesi memang punya
     * nilai yang sudah terkumpul. Maximin karena itu bukan menghadapi ketiadaan
     * informasi, melainkan membuang informasi yang ada dan menggantinya dengan
     * penalti tetap — yang membuat C2 sebagian besar mengukur "apakah prodi ini
     * dipilih responden", bukan prestasi akademik responden pada prodi itu, dan
     * menghitung minat dua kali bersama C4.
     *
     * @param  array<int, float|null>  $subjectScores
     */
    private function supportSubjectValue(
        StudyProgram $program,
        float $raporAverage,
        array $subjectScores,
        ?string $educationLevel,
        ?string $schoolMajor,
    ): float {
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
