<?php

namespace Tests\Unit;

use App\Services\ExplanationService;
use PHPUnit\Framework\TestCase;

class ExplanationServiceTest extends TestCase
{
    private ExplanationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ExplanationService;
    }

    /**
     * @return array<string, array{name: string, weight: float, type: string, source: string}>
     */
    private function snapshot(): array
    {
        return [
            'C1' => ['name' => 'Rerata Rapor Seluruh Mapel', 'weight' => 0.2, 'type' => 'benefit', 'source' => 'rapor_average'],
            'C7' => ['name' => 'Kesesuaian RIASEC', 'weight' => 0.5, 'type' => 'benefit', 'source' => 'riasec'],
            'C9' => ['name' => 'Serapan Kerja', 'weight' => 0.3, 'type' => 'benefit', 'source' => 'tracer'],
        ];
    }

    public function test_kontribusi_terurut_dari_penyumbang_terbesar(): void
    {
        $rows = $this->service->contributions(
            ['C1' => 1.0, 'C7' => 0.8, 'C9' => 0.1],
            $this->snapshot(),
        );

        // C7: 0.5 x 0.8 = 0.40 ; C1: 0.2 x 1.0 = 0.20 ; C9: 0.3 x 0.1 = 0.03
        $this->assertSame(['C7', 'C1', 'C9'], array_column($rows, 'code'));
        $this->assertEqualsWithDelta(0.40, $rows[0]['contribution'], 0.0001);
    }

    public function test_porsi_kontribusi_berjumlah_seratus_persen(): void
    {
        $rows = $this->service->contributions(
            ['C1' => 0.6, 'C7' => 0.9, 'C9' => 0.4],
            $this->snapshot(),
        );

        $this->assertEqualsWithDelta(100.0, array_sum(array_column($rows, 'share')), 0.2);
    }

    public function test_tingkat_kuat_sedang_dan_lemah_ditandai(): void
    {
        $rows = collect($this->service->contributions(
            ['C1' => 0.95, 'C7' => 0.50, 'C9' => 0.05],
            $this->snapshot(),
        ))->keyBy('code');

        $this->assertSame('kuat', $rows['C1']['level']);
        $this->assertSame('sedang', $rows['C7']['level']);
        $this->assertSame('lemah', $rows['C9']['level']);
    }

    public function test_kriteria_yang_hilang_dari_data_dianggap_nol(): void
    {
        $rows = collect($this->service->contributions(['C1' => 0.8], $this->snapshot()))->keyBy('code');

        $this->assertSame(0.0, $rows['C7']['normalized']);
        $this->assertSame(0.0, $rows['C9']['contribution']);
    }

    public function test_perbandingan_menunjuk_penyebab_selisih_terbesar(): void
    {
        $rows = $this->service->compare(
            subject: ['C1' => 0.9, 'C7' => 0.2, 'C9' => 0.5],
            against: ['C1' => 0.8, 'C7' => 1.0, 'C9' => 0.5],
            snapshot: $this->snapshot(),
        );

        // Selisih terbesar ada di C7: 0.5 x (0.2 - 1.0) = -0.40
        $this->assertSame('C7', $rows[0]['code']);
        $this->assertEqualsWithDelta(-0.40, $rows[0]['delta'], 0.0001);
        $this->assertLessThan(0, $rows[0]['delta']);
    }

    public function test_sorotan_memisahkan_keunggulan_dan_kelemahan(): void
    {
        $contributions = $this->service->contributions(
            ['C1' => 0.95, 'C7' => 0.90, 'C9' => 0.10],
            $this->snapshot(),
        );

        $highlights = $this->service->highlights($contributions);

        $this->assertSame(['Kesesuaian RIASEC', 'Rerata Rapor Seluruh Mapel'], $highlights['strengths']);
        $this->assertSame(['Serapan Kerja'], $highlights['weaknesses']);
    }

    public function test_sorotan_dibatasi_jumlahnya(): void
    {
        $contributions = $this->service->contributions(
            ['C1' => 0.9, 'C7' => 0.9, 'C9' => 0.9],
            $this->snapshot(),
        );

        $this->assertCount(2, $this->service->highlights($contributions, 2)['strengths']);
    }
}
