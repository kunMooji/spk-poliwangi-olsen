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
    public function __construct(private readonly RiasecService $riasec) {}

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
