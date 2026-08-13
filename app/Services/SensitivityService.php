<?php

namespace App\Services;

/**
 * Analisis sensitivitas hasil CoCoSo.
 *
 * Menjawab dua pertanyaan yang selalu muncul pada sistem pendukung keputusan:
 *
 *   1. Seberapa besar peringkat bergantung pada nilai λ yang dipilih?
 *   2. Kriteria mana yang paling menentukan? Bila bobotnya digeser, apakah
 *      rekomendasi teratas ikut berpindah?
 *
 * Seluruh perhitungan memakai matriks keputusan yang sudah tersimpan pada sesi
 * tes, sehingga tidak ada nilai baru yang dikarang dan hasil aslinya tidak
 * pernah tersentuh — kelas ini murni membaca.
 */
final class SensitivityService
{
    /** Besar pergeseran bobot yang diuji, relatif terhadap bobot aslinya. */
    public const DEFAULT_SHIFTS = [-0.5, -0.25, 0.25, 0.5];

    /** Nilai λ yang diuji pada sweep. */
    public const DEFAULT_LAMBDAS = [0.0, 0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8, 0.9, 1.0];

    public function __construct(private readonly CocosoService $cocoso) {}

    /**
     * @param  array<string|int, array<string, float>>  $matrix
     * @param  array<string, float>  $weights
     * @param  array<string, string>  $types
     * @param  array<string, array{min: float, max: float}>  $bounds  batas normalisasi tetap,
     *                                                               wajib sama dengan yang dipakai
     *                                                               saat perhitungan asli
     * @return array{
     *     baseline: array{winner: string|int, ranking: array<string|int, int>, k_normal: array<string|int, float>},
     *     lambda: array<int, array{lambda: float, winner: string|int, stable: bool, margin: float}>,
     *     weights: array<int, array{code: string, shift: float, weight: float, winner: string|int, stable: bool, rank_of_baseline_winner: int}>,
     *     summary: array{total: int, stable: int, ratio: float, lambda_stable: bool, critical: array<int, string>}
     * }
     */
    public function analyze(
        array $matrix,
        array $weights,
        array $types = [],
        float $lambda = 0.5,
        float $epsilon = 1e-6,
        array $bounds = [],
    ): array {
        $baseline = $this->cocoso->calculate($matrix, $weights, $types, $lambda, $epsilon, $bounds);
        $baselineWinner = array_search(1, $baseline['ranking'], true);

        $lambdaScenarios = $this->sweepLambda($matrix, $weights, $types, $epsilon, $baselineWinner, $bounds);
        $weightScenarios = $this->shiftWeights($matrix, $weights, $types, $lambda, $epsilon, $baselineWinner, $bounds);

        $scenarios = array_merge($lambdaScenarios, $weightScenarios);
        $stable = count(array_filter($scenarios, fn (array $row) => $row['stable']));
        $total = count($scenarios);

        return [
            'baseline' => [
                'winner' => $baselineWinner,
                'ranking' => $baseline['ranking'],
                'k_normal' => $baseline['k_normal'],
            ],
            'lambda' => $lambdaScenarios,
            'weights' => $weightScenarios,
            'summary' => [
                'total' => $total,
                'stable' => $stable,
                'ratio' => $total > 0 ? round($stable / $total * 100, 1) : 100.0,
                'lambda_stable' => ! in_array(false, array_column($lambdaScenarios, 'stable'), true),
                // Kriteria yang pergeseran bobotnya sanggup memindahkan peringkat 1.
                'critical' => array_values(array_unique(
                    array_column(array_filter($weightScenarios, fn (array $row) => ! $row['stable']), 'code')
                )),
            ],
        ];
    }

    /**
     * λ hanya memengaruhi strategi K_ic, namun pengaruhnya terhadap K_i tetap
     * perlu diperiksa: bila peringkat 1 bertahan pada seluruh λ, pilihan λ = 0.5
     * tidak menentukan hasil.
     *
     * @param  array<string|int, array<string, float>>  $matrix
     * @param  array<string, float>  $weights
     * @param  array<string, string>  $types
     * @param  array<string, array{min: float, max: float}>  $bounds
     * @return array<int, array{lambda: float, winner: string|int, stable: bool, margin: float}>
     */
    private function sweepLambda(
        array $matrix,
        array $weights,
        array $types,
        float $epsilon,
        string|int|false $baselineWinner,
        array $bounds = [],
    ): array {
        $rows = [];

        foreach (self::DEFAULT_LAMBDAS as $value) {
            $result = $this->cocoso->calculate($matrix, $weights, $types, $value, $epsilon, $bounds);
            $winner = array_search(1, $result['ranking'], true);

            $rows[] = [
                'lambda' => $value,
                'winner' => $winner,
                'stable' => $winner === $baselineWinner,
                'margin' => $this->margin($result['k_normal'], $result['ranking']),
            ];
        }

        return $rows;
    }

    /**
     * Geser bobot satu kriteria, lalu skalakan ulang bobot kriteria lain secara
     * proporsional agar totalnya tetap sama seperti semula. Tanpa penskalaan ini
     * yang berubah bukan hanya kepentingan relatif kriteria, tapi juga skala S_i,
     * sehingga skenarionya tidak lagi setara dengan perhitungan asli.
     *
     * @param  array<string|int, array<string, float>>  $matrix
     * @param  array<string, float>  $weights
     * @param  array<string, string>  $types
     * @param  array<string, array{min: float, max: float}>  $bounds
     * @return array<int, array{code: string, shift: float, weight: float, winner: string|int, stable: bool, rank_of_baseline_winner: int}>
     */
    private function shiftWeights(
        array $matrix,
        array $weights,
        array $types,
        float $lambda,
        float $epsilon,
        string|int|false $baselineWinner,
        array $bounds = [],
    ): array {
        $rows = [];
        $total = array_sum($weights);

        foreach (array_keys($weights) as $code) {
            foreach (self::DEFAULT_SHIFTS as $shift) {
                $adjusted = $this->rebalance($weights, $code, $shift, $total);

                if ($adjusted === null) {
                    continue;
                }

                $result = $this->cocoso->calculate($matrix, $adjusted, $types, $lambda, $epsilon, $bounds);
                $winner = array_search(1, $result['ranking'], true);

                $rows[] = [
                    'code' => $code,
                    'shift' => $shift,
                    'weight' => round($adjusted[$code], 6),
                    'winner' => $winner,
                    'stable' => $winner === $baselineWinner,
                    'rank_of_baseline_winner' => $baselineWinner === false
                        ? 0
                        : ($result['ranking'][$baselineWinner] ?? 0),
                ];
            }
        }

        return $rows;
    }

    /**
     * @param  array<string, float>  $weights
     * @return array<string, float>|null  null bila skenario tidak masuk akal
     */
    private function rebalance(array $weights, string $code, float $shift, float $total): ?array
    {
        $original = (float) $weights[$code];
        $target = $original * (1 + $shift);

        if ($original <= 0.0 || $target <= 0.0 || $target >= $total) {
            return null;
        }

        $othersTotal = $total - $original;

        if ($othersTotal <= 0.0) {
            return null;
        }

        $scale = ($total - $target) / $othersTotal;

        $adjusted = [];
        foreach ($weights as $key => $weight) {
            $adjusted[$key] = $key === $code ? $target : (float) $weight * $scale;
        }

        return $adjusted;
    }

    /**
     * Jarak nilai peringkat 1 terhadap peringkat 2 pada skala 0-100.
     * Semakin kecil, semakin rapuh keunggulannya.
     *
     * @param  array<string|int, float>  $kNormal
     * @param  array<string|int, int>  $ranking
     */
    private function margin(array $kNormal, array $ranking): float
    {
        $first = array_search(1, $ranking, true);
        $second = array_search(2, $ranking, true);

        if ($first === false || $second === false) {
            return 0.0;
        }

        return round($kNormal[$first] - $kNormal[$second], 2);
    }
}
