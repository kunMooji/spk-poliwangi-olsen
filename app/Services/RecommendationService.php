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
        // Mapel pendukung dimuat di muka: DecisionMatrixBuilder membacanya untuk
        // setiap prodi pada setiap kriteria.
        $programs = StudyProgram::query()->active()->with('supportSubjects')->orderBy('code')->get();

        if ($criteria->isEmpty()) {
            throw CalculationException::noActiveCriteria();
        }

        if ($programs->count() < 2) {
            throw CalculationException::notEnoughPrograms($programs->count());
        }

        $rawWeights = $criteria->pluck('weight', 'code')
            ->map(fn ($weight) => (float) $weight)
            ->all();

        $weights = $this->normalizeWeights($rawWeights);

        $types = $criteria->pluck('type', 'code')->all();
        $bounds = DecisionMatrixBuilder::boundsFor($criteria->pluck('source', 'code')->all());

        $lambda = (float) Setting::get('lambda');
        $epsilon = (float) Setting::get('epsilon');

        return DB::transaction(function () use ($assessment, $criteria, $programs, $rawWeights, $weights, $types, $bounds, $lambda, $epsilon) {
            $this->applyRiasecProfile($assessment);

            $assessment->load('priorities', 'subjectScores', 'raporSemesters');

            $matrix = $this->matrixBuilder->build($assessment, $programs, $criteria);
            $calculation = $this->cocoso->calculate($matrix, $weights, $types, $lambda, $epsilon, $bounds);

            $this->storeResults($assessment, $calculation);
            $this->applyRecommendation($assessment, $calculation, $criteria, $lambda, $rawWeights, $weights);

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
     * Minat sudah diperhitungkan sebagai kriteria C4 di dalam matriks keputusan;
     * menjadikannya juga sebagai aturan penimpa di tahap ini berarti menghitung
     * minat dua kali, dan membuat prodi pilihan pertama nyaris selalu terpilih
     * terlepas dari nilai rapor maupun kesesuaian kepribadiannya.
     *
     * Yang disimpan sebagai penanda keyakinan bukan lagi "ambang kelayakan",
     * melainkan margin peringkat 1 terhadap peringkat 2. Alasannya: `k_normal`
     * diskalakan terhadap K tertinggi sehingga prodi peringkat 1 selalu bernilai
     * tepat 100, dan ambang berapa pun tidak akan pernah mengikat padanya. K juga
     * tidak punya skala absolut — K_ia, K_ib, dan K_ic semuanya didefinisikan
     * relatif terhadap alternatif lain pada sesi yang sama. Jarak antara peringkat
     * 1 dan 2 justru bermakna dan sebanding antar sesi: lebar berarti rekomendasi
     * meyakinkan, tipis berarti keduanya praktis seri.
     *
     * @param  Collection<int, Criteria>  $criteria
     * @param  array<string, float>  $rawWeights  bobot apa adanya dari tabel kriteria
     * @param  array<string, float>  $weights  bobot setelah diskalakan agar berjumlah 1
     */
    private function applyRecommendation(
        Assessment $assessment,
        array $calculation,
        Collection $criteria,
        float $lambda,
        array $rawWeights,
        array $weights,
    ): void {
        $marginThreshold = (float) Setting::get('margin_threshold');

        $primaryId = $assessment->priorities->first()?->study_program_id;

        $recommendedId = array_search(1, $calculation['ranking'], true);
        $runnerUpId = array_search(2, $calculation['ranking'], true);

        $assessment->fill([
            'primary_program_id' => $primaryId,
            'recommended_program_id' => $recommendedId,
            'recommended_k_value' => $calculation['k'][$recommendedId] ?? null,
            'recommended_k_normal' => $calculation['k_normal'][$recommendedId] ?? null,
            'matches_preference' => $primaryId !== null && $recommendedId === $primaryId,
            'margin_to_runner_up' => $this->marginToRunnerUp($calculation, $recommendedId, $runnerUpId),
            'margin_threshold_used' => $marginThreshold,
            'lambda_used' => $lambda,
            // `weight` adalah bobot yang benar-benar dipakai CoCoSo — sudah
            // diskalakan agar berjumlah 1. `weight_raw` menyimpan angka asli dari
            // tabel kriteria supaya penskalaan itu tetap dapat ditelusuri.
            'weights_snapshot' => $criteria->mapWithKeys(fn (Criteria $criterion) => [
                $criterion->code => [
                    'name' => $criterion->name,
                    'weight' => $weights[$criterion->code],
                    'weight_raw' => $rawWeights[$criterion->code],
                    'type' => $criterion->type,
                    'source' => $criterion->source,
                ],
            ])->all(),
            'status' => 'completed',
            'completed_at' => now(),
        ])->save();
    }

    /**
     * Selisih skor relatif peringkat 1 terhadap peringkat 2, dalam poin 0-100.
     *
     * Karena `k_normal` peringkat 1 selalu 100, angka ini setara dengan
     * 100 - k_normal peringkat 2. Bernilai null bila hanya ada satu alternatif.
     */
    private function marginToRunnerUp(array $calculation, mixed $recommendedId, mixed $runnerUpId): ?float
    {
        if ($recommendedId === false || $runnerUpId === false) {
            return null;
        }

        return $calculation['k_normal'][$recommendedId] - $calculation['k_normal'][$runnerUpId];
    }

    /**
     * Skalakan bobot kriteria agar berjumlah tepat 1.
     *
     * CoCoSo mencampur dua agregasi yang memperlakukan bobot secara berbeda:
     * S_i = Sum(w_j * r_ij) tumbuh linear terhadap total bobot, sedangkan
     * P_i = Sum(r_ij ^ w_j) tidak. Ketika Sum(w) != 1, keduanya bergeser pada
     * skala yang tidak sebanding, sehingga K_ib = S/min(S) + P/min(P) dan
     * K_ic yang mencampur keduanya kehilangan makna — hasilnya tetap keluar
     * berupa angka, hanya saja peringkatnya tidak lagi mencerminkan bobot yang
     * dimaksud admin. Karena itu penskalaan dilakukan di sini, bukan diserahkan
     * kepada kedisiplinan admin mengisi formulir.
     *
     * Penskalaan mempertahankan seluruh perbandingan antar bobot, jadi maksud
     * admin tidak berubah: 0.50 dan 0.25 tetap berbanding 2:1 setelah menjadi
     * 0.4 dan 0.2. Bila totalnya sudah 1, hasilnya sama persis dengan masukan.
     *
     * @param  array<string, float>  $weights
     * @return array<string, float>
     */
    private function normalizeWeights(array $weights): array
    {
        $total = array_sum($weights);

        if ($total <= 0.0) {
            throw CalculationException::zeroTotalWeight();
        }

        foreach ($weights as $code => $weight) {
            if ($weight < 0.0) {
                throw CalculationException::negativeWeight($code);
            }
        }

        return array_map(fn (float $weight) => $weight / $total, $weights);
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
