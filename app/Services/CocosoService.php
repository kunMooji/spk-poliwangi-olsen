<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * Implementasi metode Combined Compromise Solution (CoCoSo).
 *
 * Kelas ini murni matematis — tidak menyentuh Eloquent, request, maupun session —
 * sehingga bisa diuji langsung dengan angka dan dibandingkan dengan hitungan manual.
 *
 * Tahapan (Yazdani dkk., 2019):
 *   1. Normalisasi matriks keputusan
 *        benefit : r_ij = (x_ij - min_j) / (max_j - min_j)
 *        cost    : r_ij = (max_j - x_ij) / (max_j - min_j)
 *   2. S_i = Σ_j ( w_j · r_ij )                      -> weighted sum
 *   3. P_i = Σ_j ( r_ij ^ w_j )                      -> weighted product
 *   4. K_ia = (P_i + S_i) / Σ_i (P_i + S_i)
 *      K_ib = S_i / min(S) + P_i / min(P)
 *      K_ic = (λ·S_i + (1-λ)·P_i) / (λ·max(S) + (1-λ)·max(P))
 *   5. K_i  = (K_ia · K_ib · K_ic)^(1/3) + ⅓ (K_ia + K_ib + K_ic)
 */
final class CocosoService
{
    /**
     * Batas bawah penyebut agar tidak terjadi pembagian dengan nol.
     */
    private const DENOMINATOR_FLOOR = 1e-12;

    /**
     * Jalankan seluruh tahapan CoCoSo.
     *
     * @param  array<string|int, array<string, float>>  $matrix  x_ij: [kunciAlternatif => [kodeKriteria => nilai]]
     * @param  array<string, float>  $weights  w_j:  [kodeKriteria => bobot]
     * @param  array<string, string>  $types  [kodeKriteria => 'benefit'|'cost']
     * @return array{
     *     alternatives: array<int, string|int>,
     *     criteria: array<int, string>,
     *     matrix: array<string|int, array<string, float>>,
     *     min: array<string, float>,
     *     max: array<string, float>,
     *     normalized: array<string|int, array<string, float>>,
     *     s: array<string|int, float>,
     *     p: array<string|int, float>,
     *     k_a: array<string|int, float>,
     *     k_b: array<string|int, float>,
     *     k_c: array<string|int, float>,
     *     k: array<string|int, float>,
     *     k_normal: array<string|int, float>,
     *     ranking: array<string|int, int>,
     *     lambda: float,
     *     epsilon: float
     * }
     */
    public function calculate(
        array $matrix,
        array $weights,
        array $types = [],
        float $lambda = 0.5,
        float $epsilon = 1e-6,
    ): array {
        $alternatives = array_keys($matrix);
        $criteria = array_keys($weights);

        if ($alternatives === []) {
            throw new InvalidArgumentException('Matriks keputusan tidak boleh kosong.');
        }

        if ($criteria === []) {
            throw new InvalidArgumentException('Daftar kriteria beserta bobotnya tidak boleh kosong.');
        }

        if ($lambda < 0 || $lambda > 1) {
            throw new InvalidArgumentException('Nilai lambda harus berada pada rentang 0 sampai 1.');
        }

        $epsilon = max($epsilon, 0.0);

        // --- Tahap 1: normalisasi -------------------------------------------------
        [$min, $max] = $this->columnBounds($matrix, $alternatives, $criteria);
        $normalized = $this->normalize($matrix, $alternatives, $criteria, $min, $max, $types, $epsilon);

        // --- Tahap 2 & 3: S_i dan P_i ---------------------------------------------
        $s = [];
        $p = [];

        foreach ($alternatives as $alternative) {
            $sumS = 0.0;
            $sumP = 0.0;

            foreach ($criteria as $code) {
                $r = $normalized[$alternative][$code];
                $w = (float) $weights[$code];

                $sumS += $w * $r;
                $sumP += $r ** $w;
            }

            $s[$alternative] = $sumS;
            $p[$alternative] = $sumP;
        }

        // --- Tahap 4: tiga strategi agregasi kompromi ------------------------------
        $totalSP = 0.0;
        foreach ($alternatives as $alternative) {
            $totalSP += $p[$alternative] + $s[$alternative];
        }
        $totalSP = max($totalSP, self::DENOMINATOR_FLOOR);

        $minS = max(min($s), self::DENOMINATOR_FLOOR);
        $minP = max(min($p), self::DENOMINATOR_FLOOR);
        $denominatorC = max($lambda * max($s) + (1 - $lambda) * max($p), self::DENOMINATOR_FLOOR);

        $kA = [];
        $kB = [];
        $kC = [];
        $k = [];

        foreach ($alternatives as $alternative) {
            $a = ($p[$alternative] + $s[$alternative]) / $totalSP;
            $b = $s[$alternative] / $minS + $p[$alternative] / $minP;
            $c = ($lambda * $s[$alternative] + (1 - $lambda) * $p[$alternative]) / $denominatorC;

            $kA[$alternative] = $a;
            $kB[$alternative] = $b;
            $kC[$alternative] = $c;

            // --- Tahap 5: nilai akhir K_i -----------------------------------------
            $k[$alternative] = ($a * $b * $c) ** (1 / 3) + ($a + $b + $c) / 3;
        }

        return [
            'alternatives' => $alternatives,
            'criteria' => $criteria,
            'matrix' => $matrix,
            'min' => $min,
            'max' => $max,
            'normalized' => $normalized,
            's' => $s,
            'p' => $p,
            'k_a' => $kA,
            'k_b' => $kB,
            'k_c' => $kC,
            'k' => $k,
            'k_normal' => $this->toPercentageScale($k),
            'ranking' => $this->rank($k),
            'lambda' => $lambda,
            'epsilon' => $epsilon,
        ];
    }

    /**
     * Nilai terkecil dan terbesar tiap kolom kriteria.
     *
     * @param  array<string|int, array<string, float>>  $matrix
     * @param  array<int, string|int>  $alternatives
     * @param  array<int, string>  $criteria
     * @return array{0: array<string, float>, 1: array<string, float>}
     */
    private function columnBounds(array $matrix, array $alternatives, array $criteria): array
    {
        $min = [];
        $max = [];

        foreach ($criteria as $code) {
            $column = [];
            foreach ($alternatives as $alternative) {
                $column[] = (float) ($matrix[$alternative][$code] ?? 0.0);
            }

            $min[$code] = min($column);
            $max[$code] = max($column);
        }

        return [$min, $max];
    }

    /**
     * Normalisasi matriks keputusan.
     *
     * Dua pengaman yang wajib ada:
     *   - Kolom konstan (max == min) membuat penyebut nol. Seluruh alternatif
     *     diberi nilai 1.0 karena kriteria tersebut tidak membedakan apa pun.
     *   - Nilai 0 hasil normalisasi membuat P_i kolaps dan min(S)/min(P) pada K_ib
     *     menjadi nol. Karena itu hasil normalisasi dijaga minimal sebesar epsilon.
     *
     * @param  array<string|int, array<string, float>>  $matrix
     * @param  array<int, string|int>  $alternatives
     * @param  array<int, string>  $criteria
     * @param  array<string, float>  $min
     * @param  array<string, float>  $max
     * @param  array<string, string>  $types
     * @return array<string|int, array<string, float>>
     */
    private function normalize(
        array $matrix,
        array $alternatives,
        array $criteria,
        array $min,
        array $max,
        array $types,
        float $epsilon,
    ): array {
        $normalized = [];

        foreach ($alternatives as $alternative) {
            foreach ($criteria as $code) {
                $range = $max[$code] - $min[$code];

                if ($range == 0.0) {
                    $normalized[$alternative][$code] = 1.0;

                    continue;
                }

                $value = (float) ($matrix[$alternative][$code] ?? 0.0);

                $r = ($types[$code] ?? 'benefit') === 'cost'
                    ? ($max[$code] - $value) / $range
                    : ($value - $min[$code]) / $range;

                $normalized[$alternative][$code] = max($r, $epsilon);
            }
        }

        return $normalized;
    }

    /**
     * Skalakan nilai K ke rentang 0-100 relatif terhadap K tertinggi.
     *
     * K mentah tidak berada pada rentang tetap (K_ib saja sudah bernilai >= 2) dan
     * besarannya ikut bergeser mengikuti jumlah alternatif, sehingga tidak layak
     * dibandingkan langsung dengan threshold. Versi ternormalisasi inilah yang
     * dipakai sebagai pembanding threshold bawaan.
     *
     * @param  array<string|int, float>  $k
     * @return array<string|int, float>
     */
    private function toPercentageScale(array $k): array
    {
        $maxK = max(max($k), self::DENOMINATOR_FLOOR);

        $scaled = [];
        foreach ($k as $alternative => $value) {
            $scaled[$alternative] = $value / $maxK * 100;
        }

        return $scaled;
    }

    /**
     * Peringkat 1..m berdasarkan K terbesar. Nilai seri diurutkan stabil
     * mengikuti urutan alternatif pada matriks.
     *
     * @param  array<string|int, float>  $k
     * @return array<string|int, int>
     */
    private function rank(array $k): array
    {
        $order = array_keys($k);

        // Posisi awal tiap alternatif, dipakai sebagai pemecah nilai seri agar
        // peringkat selalu sama untuk input yang sama.
        $originalPosition = array_flip($order);

        usort($order, fn ($left, $right) => $k[$right] <=> $k[$left]
            ?: $originalPosition[$left] <=> $originalPosition[$right]);

        $ranking = [];
        foreach ($order as $position => $alternative) {
            $ranking[$alternative] = $position + 1;
        }

        return $ranking;
    }
}
