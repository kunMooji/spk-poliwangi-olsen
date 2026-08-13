<?php

namespace App\Services;

use App\Exceptions\CalculationException;
use App\Models\Assessment;
use App\Models\AssessmentResult;
use App\Models\Criteria;
use App\Models\Setting;
use App\Models\StudyProgram;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Orkestrator perhitungan: RIASEC -> matriks keputusan -> CoCoSo -> rekomendasi.
 *
 * Parameter algoritma (bobot, threshold, lambda) di-snapshot ke baris assessment
 * agar hasil tes yang sudah selesai tidak ikut berubah ketika admin memperbarui
 * bobot atau threshold di kemudian hari.
 */
final class RecommendationService
{
    public function __construct(
        private readonly RiasecService $riasec,
        private readonly DecisionMatrixBuilder $matrixBuilder,
        private readonly CocosoService $cocoso,
    ) {}

    /**
     * Hitung ulang seluruh rekomendasi untuk satu sesi tes.
     *
     * @return array Rincian lengkap tahapan CoCoSo (lihat CocosoService::calculate)
     */
    public function calculate(Assessment $assessment): array
    {
        $criteria = Criteria::query()->active()->ordered()->get();
        $programs = StudyProgram::query()->active()->orderBy('code')->get();

        if ($criteria->isEmpty()) {
            throw CalculationException::noActiveCriteria();
        }

        if ($programs->count() < 2) {
            throw CalculationException::notEnoughPrograms($programs->count());
        }

        $weights = $criteria->pluck('weight', 'code')
            ->map(fn ($weight) => (float) $weight)
            ->all();

        if (array_sum($weights) <= 0.0) {
            throw CalculationException::zeroTotalWeight();
        }

        $types = $criteria->pluck('type', 'code')->all();
        $bounds = DecisionMatrixBuilder::boundsFor($criteria->pluck('source', 'code')->all());

        $lambda = (float) Setting::get('lambda');
        $epsilon = (float) Setting::get('epsilon');

        return DB::transaction(function () use ($assessment, $criteria, $programs, $weights, $types, $bounds, $lambda, $epsilon) {
            $this->applyRiasecProfile($assessment);

            $assessment->load('priorities');

            $matrix = $this->matrixBuilder->build($assessment, $programs, $criteria);
            $calculation = $this->cocoso->calculate($matrix, $weights, $types, $lambda, $epsilon, $bounds);

            $this->storeResults($assessment, $calculation);
            $this->applyRecommendation($assessment, $calculation, $criteria, $lambda);

            return $calculation;
        });
    }

    /**
     * Akumulasi jawaban Likert menjadi skor, persentase, dan kode Holland.
     */
    private function applyRiasecProfile(Assessment $assessment): void
    {
        $answers = $assessment->answers()->get();

        if ($answers->isEmpty()) {
            throw CalculationException::noAnswers();
        }

        $scores = $this->riasec->scores($answers);

        // Jumlah butir diambil dari jawaban yang benar-benar terkumpul, bukan dari
        // daftar soal aktif saat ini, supaya persentase tetap benar meskipun admin
        // menonaktifkan atau menambah pernyataan setelah tes dikerjakan.
        $counts = $this->riasec->questionCounts($answers);

        $percentages = $this->riasec->percentages($scores, $counts);

        $attributes = [
            'holland_code' => $this->riasec->hollandCode($percentages),
            'dominant_type' => $this->riasec->dominantType($percentages),
        ];

        foreach ($scores as $dimension => $score) {
            $attributes['score_'.strtolower($dimension)] = $score;
        }

        foreach ($percentages as $dimension => $percent) {
            $attributes['percent_'.strtolower($dimension)] = $percent;
        }

        $assessment->fill($attributes)->save();
    }

    /**
     * Simpan jejak perhitungan per alternatif, menimpa hasil perhitungan sebelumnya.
     */
    private function storeResults(Assessment $assessment, array $calculation): void
    {
        $assessment->results()->delete();

        $rows = [];
        $now = now();

        foreach ($calculation['alternatives'] as $programId) {
            $rows[] = [
                'assessment_id' => $assessment->id,
                'study_program_id' => $programId,
                'matrix' => json_encode($this->roundAll($calculation['matrix'][$programId])),
                'normalized' => json_encode($this->roundAll($calculation['normalized'][$programId], 8)),
                's_value' => $calculation['s'][$programId],
                'p_value' => $calculation['p'][$programId],
                'k_a' => $calculation['k_a'][$programId],
                'k_b' => $calculation['k_b'][$programId],
                'k_c' => $calculation['k_c'][$programId],
                'k_value' => $calculation['k'][$programId],
                'k_normal' => $calculation['k_normal'][$programId],
                'ranking' => $calculation['ranking'][$programId],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        AssessmentResult::query()->insert($rows);
    }

    /**
     * Terapkan aturan rekomendasi dan simpan snapshot parameter algoritma.
     *
     * Aturan: rekomendasi mengikuti peringkat CoCoSo apa adanya — prodi dengan
     * K tertinggi.
     *
     * Prioritas calon mahasiswa sengaja tidak lagi dipakai untuk menimpa hasil.
     * Minat sudah diperhitungkan sebagai kriteria C8 di dalam matriks keputusan;
     * menjadikannya juga sebagai aturan penimpa di tahap ini berarti menghitung
     * minat dua kali, dan membuat prodi pilihan pertama nyaris selalu terpilih
     * terlepas dari nilai rapor maupun kesesuaian kepribadiannya.
     *
     * Ambang batas tetap disimpan, namun perannya kini sebatas penanda kelayakan
     * yang ditampilkan pada hasil — bukan penentu prodi mana yang direkomendasikan.
     *
     * @param  Collection<int, Criteria>  $criteria
     */
    private function applyRecommendation(
        Assessment $assessment,
        array $calculation,
        Collection $criteria,
        float $lambda,
    ): void {
        $threshold = (float) Setting::get('threshold');
        $mode = (string) Setting::get('threshold_mode');

        $primaryId = $assessment->priorities->first()?->study_program_id;

        $recommendedId = array_search(1, $calculation['ranking'], true);

        $assessment->fill([
            'primary_program_id' => $primaryId,
            'recommended_program_id' => $recommendedId,
            'recommended_k_value' => $calculation['k'][$recommendedId] ?? null,
            'recommended_k_normal' => $calculation['k_normal'][$recommendedId] ?? null,
            'matches_preference' => $primaryId !== null && $recommendedId === $primaryId,
            'threshold_used' => $threshold,
            'threshold_mode_used' => $mode,
            'lambda_used' => $lambda,
            'weights_snapshot' => $criteria->mapWithKeys(fn (Criteria $criterion) => [
                $criterion->code => [
                    'name' => $criterion->name,
                    'weight' => (float) $criterion->weight,
                    'type' => $criterion->type,
                    'source' => $criterion->source,
                ],
            ])->all(),
            'status' => 'completed',
            'completed_at' => now(),
        ])->save();
    }

    /**
     * @param  array<string, float>  $values
     * @return array<string, float>
     */
    private function roundAll(array $values, int $precision = 4): array
    {
        return array_map(fn ($value) => round((float) $value, $precision), $values);
    }
}
