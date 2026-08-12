<?php

namespace Tests\Unit;

use App\Services\CocosoService;
use App\Services\SensitivityService;
use PHPUnit\Framework\TestCase;

class SensitivityServiceTest extends TestCase
{
    private SensitivityService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new SensitivityService(new CocosoService);
    }

    /**
     * Dua alternatif yang saling berkebalikan: A unggul di C1, B unggul di C2.
     * Bobot C1 lebih besar sehingga A menang pada perhitungan asli.
     *
     * @return array<string, array<string, float>>
     */
    private function contrastingMatrix(): array
    {
        return [
            'A' => ['C1' => 10.0, 'C2' => 0.0],
            'B' => ['C1' => 0.0, 'C2' => 10.0],
        ];
    }

    /**
     * Alternatif yang unggul di seluruh kriteria — peringkatnya tidak boleh
     * goyah oleh pergeseran bobot mana pun.
     *
     * @return array<string, array<string, float>>
     */
    private function dominantMatrix(): array
    {
        return [
            'A' => ['C1' => 90.0, 'C2' => 95.0],
            'B' => ['C1' => 40.0, 'C2' => 30.0],
            'C' => ['C1' => 20.0, 'C2' => 25.0],
        ];
    }

    public function test_baseline_sesuai_dengan_perhitungan_cocoso(): void
    {
        $analysis = $this->service->analyze($this->contrastingMatrix(), ['C1' => 0.6, 'C2' => 0.4]);

        $this->assertSame('A', $analysis['baseline']['winner']);
        $this->assertSame(1, $analysis['baseline']['ranking']['A']);
    }

    public function test_sweep_lambda_mencakup_nol_sampai_satu(): void
    {
        $analysis = $this->service->analyze($this->dominantMatrix(), ['C1' => 0.5, 'C2' => 0.5]);

        $this->assertCount(11, $analysis['lambda']);
        $this->assertSame(0.0, $analysis['lambda'][0]['lambda']);
        $this->assertSame(1.0, $analysis['lambda'][10]['lambda']);
    }

    public function test_alternatif_unggul_mutlak_bertahan_di_seluruh_skenario(): void
    {
        $analysis = $this->service->analyze($this->dominantMatrix(), ['C1' => 0.5, 'C2' => 0.5]);

        $this->assertSame('A', $analysis['baseline']['winner']);
        $this->assertSame(100.0, $analysis['summary']['ratio']);
        $this->assertTrue($analysis['summary']['lambda_stable']);
        $this->assertSame([], $analysis['summary']['critical']);
    }

    public function test_pergeseran_bobot_yang_membalik_peringkat_tercatat_sebagai_kritis(): void
    {
        $analysis = $this->service->analyze($this->contrastingMatrix(), ['C1' => 0.6, 'C2' => 0.4]);

        $this->assertNotSame([], $analysis['summary']['critical']);
        $this->assertLessThan(100.0, $analysis['summary']['ratio']);

        $flipped = array_filter($analysis['weights'], fn (array $row) => ! $row['stable']);

        $this->assertNotEmpty($flipped);

        foreach ($flipped as $row) {
            $this->assertSame('B', $row['winner']);
            $this->assertSame(2, $row['rank_of_baseline_winner']);
        }
    }

    public function test_total_bobot_tetap_setelah_diskalakan_ulang(): void
    {
        $weights = ['C1' => 0.5, 'C2' => 0.3, 'C3' => 0.2];

        $matrix = [
            'A' => ['C1' => 80.0, 'C2' => 70.0, 'C3' => 60.0],
            'B' => ['C1' => 60.0, 'C2' => 90.0, 'C3' => 50.0],
        ];

        $analysis = $this->service->analyze($matrix, $weights);

        // Setiap skenario memakai bobot baru pada kriteria yang digeser, dan
        // bobot itu harus tetap berada di rentang yang masuk akal.
        foreach ($analysis['weights'] as $row) {
            $this->assertGreaterThan(0.0, $row['weight']);
            $this->assertLessThan(array_sum($weights), $row['weight']);
        }

        $this->assertCount(count($weights) * count(SensitivityService::DEFAULT_SHIFTS), $analysis['weights']);
    }

    public function test_selisih_ke_peringkat_dua_dihitung_pada_skala_nol_seratus(): void
    {
        $analysis = $this->service->analyze($this->dominantMatrix(), ['C1' => 0.5, 'C2' => 0.5]);

        foreach ($analysis['lambda'] as $row) {
            $this->assertGreaterThan(0.0, $row['margin']);
            $this->assertLessThanOrEqual(100.0, $row['margin']);
        }
    }
}
