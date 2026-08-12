<?php

namespace Tests\Unit;

use App\Services\CocosoService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CocosoServiceTest extends TestCase
{
    private CocosoService $cocoso;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cocoso = new CocosoService;
    }

    /**
     * Contoh acuan yang dipakai berulang di berkas ini.
     *
     * Kolom C1 : min 10, maks 30  -> r = 0 ; 0.5 ; 1
     * Kolom C2 : min 20, maks 100 -> r = 1 ; 0.375 ; 0
     *
     * @return array{0: array<string, array<string, float>>, 1: array<string, float>}
     */
    private function sample(): array
    {
        $matrix = [
            'A1' => ['C1' => 10.0, 'C2' => 100.0],
            'A2' => ['C1' => 20.0, 'C2' => 50.0],
            'A3' => ['C1' => 30.0, 'C2' => 20.0],
        ];

        $weights = ['C1' => 0.6, 'C2' => 0.4];

        return [$matrix, $weights];
    }

    public function test_normalisasi_benefit_menghasilkan_nilai_yang_benar(): void
    {
        [$matrix, $weights] = $this->sample();

        $result = $this->cocoso->calculate($matrix, $weights, ['C1' => 'benefit', 'C2' => 'benefit']);

        $this->assertEqualsWithDelta(1e-6, $result['normalized']['A1']['C1'], 1e-12);
        $this->assertEqualsWithDelta(0.5, $result['normalized']['A2']['C1'], 1e-12);
        $this->assertEqualsWithDelta(1.0, $result['normalized']['A3']['C1'], 1e-12);

        $this->assertEqualsWithDelta(1.0, $result['normalized']['A1']['C2'], 1e-12);
        $this->assertEqualsWithDelta(0.375, $result['normalized']['A2']['C2'], 1e-12);
        $this->assertEqualsWithDelta(1e-6, $result['normalized']['A3']['C2'], 1e-12);
    }

    public function test_normalisasi_cost_membalik_arah_penilaian(): void
    {
        [$matrix, $weights] = $this->sample();

        $result = $this->cocoso->calculate($matrix, $weights, ['C1' => 'cost', 'C2' => 'benefit']);

        // Pada kriteria cost, nilai terkecil justru yang terbaik.
        $this->assertEqualsWithDelta(1.0, $result['normalized']['A1']['C1'], 1e-12);
        $this->assertEqualsWithDelta(0.5, $result['normalized']['A2']['C1'], 1e-12);
        $this->assertEqualsWithDelta(1e-6, $result['normalized']['A3']['C1'], 1e-12);
    }

    public function test_kolom_konstan_tidak_menyebabkan_pembagian_nol(): void
    {
        $matrix = [
            'A1' => ['C1' => 80.0, 'C2' => 10.0],
            'A2' => ['C1' => 80.0, 'C2' => 20.0],
            'A3' => ['C1' => 80.0, 'C2' => 30.0],
        ];

        $result = $this->cocoso->calculate($matrix, ['C1' => 0.5, 'C2' => 0.5]);

        foreach (['A1', 'A2', 'A3'] as $alternative) {
            $this->assertSame(1.0, $result['normalized'][$alternative]['C1']);
            $this->assertTrue(is_finite($result['k'][$alternative]));
        }

        // C1 tidak membedakan apa pun, sehingga peringkat murni ditentukan C2.
        $this->assertSame(1, $result['ranking']['A3']);
        $this->assertSame(3, $result['ranking']['A1']);
    }

    public function test_epsilon_mencegah_pembagian_nol_saat_satu_alternatif_terburuk_di_semua_kriteria(): void
    {
        // Tanpa epsilon: A2 ternormalisasi 0 pada seluruh kriteria sehingga
        // S = P = 0, lalu S/min(S) dan P/min(P) pada K_ib menjadi pembagian nol.
        $matrix = [
            'A1' => ['C1' => 100.0, 'C2' => 100.0],
            'A2' => ['C1' => 10.0, 'C2' => 10.0],
        ];

        $result = $this->cocoso->calculate($matrix, ['C1' => 0.5, 'C2' => 0.5]);

        foreach (['A1', 'A2'] as $alternative) {
            $this->assertGreaterThan(0.0, $result['s'][$alternative]);
            $this->assertGreaterThan(0.0, $result['p'][$alternative]);
            $this->assertTrue(is_finite($result['k'][$alternative]));
            $this->assertFalse(is_nan($result['k'][$alternative]));
        }

        $this->assertSame(1, $result['ranking']['A1']);
    }

    public function test_nilai_s_dihitung_sebagai_penjumlahan_berbobot(): void
    {
        [$matrix, $weights] = $this->sample();

        $result = $this->cocoso->calculate($matrix, $weights);

        // S_A1 = 0.6(1e-6) + 0.4(1)     = 0.4000006
        // S_A2 = 0.6(0.5) + 0.4(0.375)  = 0.45
        // S_A3 = 0.6(1)   + 0.4(1e-6)   = 0.6000004
        $this->assertEqualsWithDelta(0.4000006, $result['s']['A1'], 1e-9);
        $this->assertEqualsWithDelta(0.45, $result['s']['A2'], 1e-9);
        $this->assertEqualsWithDelta(0.6000004, $result['s']['A3'], 1e-9);
    }

    public function test_nilai_p_dihitung_sebagai_penjumlahan_pangkat_bobot(): void
    {
        [$matrix, $weights] = $this->sample();

        $result = $this->cocoso->calculate($matrix, $weights);

        // P_A1 = (1e-6)^0.6 + 1^0.4 = 10^-3.6 + 1 = 1.000251188643...
        $this->assertEqualsWithDelta(1.000251188643, $result['p']['A1'], 1e-9);

        // P_A3 = 1^0.6 + (1e-6)^0.4 = 1 + 10^-2.4 = 1.003981071706...
        $this->assertEqualsWithDelta(1.003981071706, $result['p']['A3'], 1e-9);

        // P bersifat penjumlahan, bukan perkalian: nilainya harus melebihi 1
        // ketika salah satu suku sudah bernilai 1.
        $this->assertGreaterThan(1.0, $result['p']['A2']);
    }

    public function test_total_k_ia_selalu_sama_dengan_satu(): void
    {
        [$matrix, $weights] = $this->sample();

        $result = $this->cocoso->calculate($matrix, $weights);

        // K_ia adalah proporsi terhadap total (P+S), sehingga jumlahnya wajib 1.
        $this->assertEqualsWithDelta(1.0, array_sum($result['k_a']), 1e-12);
    }

    public function test_k_ib_bernilai_tepat_dua_pada_alternatif_dengan_s_dan_p_terkecil(): void
    {
        [$matrix, $weights] = $this->sample();

        $result = $this->cocoso->calculate($matrix, $weights);

        // A1 memegang S terkecil sekaligus P terkecil, sehingga
        // K_ib = S/min(S) + P/min(P) = 1 + 1 = 2.
        $this->assertSame('A1', array_search(min($result['s']), $result['s'], true));
        $this->assertSame('A1', array_search(min($result['p']), $result['p'], true));
        $this->assertEqualsWithDelta(2.0, $result['k_b']['A1'], 1e-12);
    }

    public function test_k_ic_tidak_pernah_melebihi_satu(): void
    {
        [$matrix, $weights] = $this->sample();

        $result = $this->cocoso->calculate($matrix, $weights);

        // Pembilang λS_i + (1-λ)P_i selalu <= penyebut λmax(S) + (1-λ)max(P).
        foreach ($result['k_c'] as $value) {
            $this->assertLessThanOrEqual(1.0, $value);
            $this->assertGreaterThan(0.0, $value);
        }
    }

    public function test_nilai_akhir_k_menggabungkan_ketiga_strategi_kompromi(): void
    {
        [$matrix, $weights] = $this->sample();

        $result = $this->cocoso->calculate($matrix, $weights);

        foreach (array_keys($matrix) as $alternative) {
            $a = $result['k_a'][$alternative];
            $b = $result['k_b'][$alternative];
            $c = $result['k_c'][$alternative];

            $expected = ($a * $b * $c) ** (1 / 3) + ($a + $b + $c) / 3;

            $this->assertEqualsWithDelta($expected, $result['k'][$alternative], 1e-12);
        }
    }

    public function test_lambda_mengubah_bobot_antara_s_dan_p(): void
    {
        [$matrix, $weights] = $this->sample();

        $condongKeS = $this->cocoso->calculate($matrix, $weights, [], 1.0);
        $condongKeP = $this->cocoso->calculate($matrix, $weights, [], 0.0);

        // λ = 1 -> K_ic murni berbasis S, sehingga pemilik S terbesar bernilai 1.
        $this->assertEqualsWithDelta(1.0, $condongKeS['k_c']['A3'], 1e-12);

        // λ = 0 -> K_ic murni berbasis P, sehingga pemilik P terbesar bernilai 1.
        $this->assertEqualsWithDelta(1.0, $condongKeP['k_c']['A2'], 1e-12);
    }

    public function test_peringkat_diurutkan_menurun_berdasarkan_nilai_k(): void
    {
        [$matrix, $weights] = $this->sample();

        $result = $this->cocoso->calculate($matrix, $weights);

        $this->assertSame([1, 2, 3], array_values(array_unique(array_values($result['ranking']))));

        $sortedByK = $result['k'];
        arsort($sortedByK);

        $expectedOrder = array_keys($sortedByK);
        foreach ($expectedOrder as $position => $alternative) {
            $this->assertSame($position + 1, $result['ranking'][$alternative]);
        }
    }

    public function test_k_normal_menskalakan_nilai_tertinggi_menjadi_seratus(): void
    {
        [$matrix, $weights] = $this->sample();

        $result = $this->cocoso->calculate($matrix, $weights);

        $this->assertEqualsWithDelta(100.0, max($result['k_normal']), 1e-12);

        $best = array_search(1, $result['ranking'], true);
        $this->assertEqualsWithDelta(100.0, $result['k_normal'][$best], 1e-12);
    }

    public function test_matriks_kosong_ditolak(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->cocoso->calculate([], ['C1' => 1.0]);
    }

    public function test_kriteria_kosong_ditolak(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->cocoso->calculate(['A1' => ['C1' => 1.0]], []);
    }

    public function test_lambda_di_luar_rentang_ditolak(): void
    {
        [$matrix, $weights] = $this->sample();

        $this->expectException(InvalidArgumentException::class);

        $this->cocoso->calculate($matrix, $weights, [], 1.5);
    }
}
