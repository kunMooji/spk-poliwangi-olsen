<?php

namespace Tests\Unit;

use App\Services\ReliabilityService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class ReliabilityServiceTest extends TestCase
{
    /**
     * @param  array<int, array<int, float>>  $responses
     * @param  array<int, int>  $itemIds
     */
    private function alpha(array $responses, array $itemIds): ?float
    {
        $method = new ReflectionMethod(ReliabilityService::class, 'alpha');

        return $method->invoke(new ReliabilityService(), $responses, $itemIds);
    }

    public function test_cocok_dengan_hitungan_manual(): void
    {
        // Contoh baku 3 butir × 5 responden, dihitung tangan:
        //   butir 1: 3,3,2,4,2 -> rerata 2.8, varian populasi 0.56
        //   butir 2: 3,3,2,3,3 -> rerata 2.8, varian populasi 0.16
        //   butir 3: 2,3,2,4,2 -> rerata 2.6, varian populasi 0.64
        //   jumlah varian butir = 1.36
        //   skor total: 8,9,6,11,7 -> rerata 8.2, varian populasi 2.96
        //   alpha = 3/2 x (1 - 1.36/2.96) = 1.5 x 0.540540... = 0.810810...
        $responses = [
            [1 => 3.0, 2 => 3.0, 3 => 2.0],
            [1 => 3.0, 2 => 3.0, 3 => 3.0],
            [1 => 2.0, 2 => 2.0, 3 => 2.0],
            [1 => 4.0, 2 => 3.0, 3 => 4.0],
            [1 => 2.0, 2 => 3.0, 3 => 2.0],
        ];

        $this->assertEqualsWithDelta(0.8108, $this->alpha($responses, [1, 2, 3]), 1e-4);
    }

    public function test_butir_yang_sejalan_sempurna_menghasilkan_alpha_satu(): void
    {
        $responses = [
            [1 => 1.0, 2 => 1.0, 3 => 1.0],
            [1 => 3.0, 2 => 3.0, 3 => 3.0],
            [1 => 5.0, 2 => 5.0, 3 => 5.0],
        ];

        $this->assertEqualsWithDelta(1.0, $this->alpha($responses, [1, 2, 3]), 1e-9);
    }

    public function test_butir_berlawanan_arah_menghasilkan_alpha_negatif(): void
    {
        // Alpha negatif adalah hasil yang sah dan informatif — pertanda butir
        // tidak mengukur hal yang sama — jadi tidak boleh dijepit ke nol.
        $responses = [
            [1 => 1.0, 2 => 5.0],
            [1 => 2.0, 2 => 4.0],
            [1 => 4.0, 2 => 1.0],
            [1 => 5.0, 2 => 2.0],
        ];

        $this->assertLessThan(0.0, $this->alpha($responses, [1, 2]));
    }

    public function test_dua_butir_yang_saling_meniadakan_sempurna_tidak_terdefinisi(): void
    {
        // Ketika kenaikan satu butir selalu diimbangi penurunan butir lain
        // sebesar itu juga, skor totalnya konstan. Varian total nol, sehingga
        // alpha tidak terdefinisi — bukan nol, bukan negatif.
        $responses = [
            [1 => 1.0, 2 => 5.0],
            [1 => 2.0, 2 => 4.0],
            [1 => 4.0, 2 => 2.0],
            [1 => 5.0, 2 => 1.0],
        ];

        $this->assertNull($this->alpha($responses, [1, 2]));
    }

    public function test_memilih_sebagian_butir_tidak_salah_sejajar(): void
    {
        // Kolom 1 dan 2 sejalan sempurna; kolom 3 acak. Alpha atas [1,2] harus
        // tetap 1.0 meski matriksnya memuat kolom ketiga.
        $responses = [
            [1 => 1.0, 2 => 1.0, 3 => 5.0],
            [1 => 3.0, 2 => 3.0, 3 => 1.0],
            [1 => 5.0, 2 => 5.0, 3 => 4.0],
        ];

        $this->assertEqualsWithDelta(1.0, $this->alpha($responses, [1, 2]), 1e-9);
    }

    public function test_tidak_terdefinisi_saat_data_belum_memadai(): void
    {
        $satuResponden = [[1 => 3.0, 2 => 4.0]];
        $this->assertNull($this->alpha($satuResponden, [1, 2]));

        $butirTunggal = [[1 => 3.0], [1 => 5.0]];
        $this->assertNull($this->alpha($butirTunggal, [1]));

        // Seluruh responden menjawab identik: tidak ada sebaran untuk diukur.
        $seragam = [[1 => 3.0, 2 => 3.0], [1 => 3.0, 2 => 3.0]];
        $this->assertNull($this->alpha($seragam, [1, 2]));
    }
}
