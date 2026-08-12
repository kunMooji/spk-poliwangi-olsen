<?php

namespace Tests\Unit;

use App\Services\RiasecService;
use PHPUnit\Framework\TestCase;

class RiasecServiceTest extends TestCase
{
    private RiasecService $riasec;

    protected function setUp(): void
    {
        parent::setUp();
        $this->riasec = new RiasecService(likertMin: 1, likertMax: 5);
    }

    /**
     * @param  array<string, int>  $perDimension  jumlah butir per dimensi
     * @return array<int, array{dimension: string, score: int}>
     */
    private function answers(array $perDimension, int $score): array
    {
        $answers = [];

        foreach ($perDimension as $dimension => $count) {
            for ($i = 0; $i < $count; $i++) {
                $answers[] = ['dimension' => $dimension, 'score' => $score];
            }
        }

        return $answers;
    }

    public function test_skor_diakumulasi_per_dimensi(): void
    {
        $answers = [
            ['dimension' => 'R', 'score' => 5],
            ['dimension' => 'R', 'score' => 3],
            ['dimension' => 'I', 'score' => 4],
            ['dimension' => 'C', 'score' => 2],
        ];

        $scores = $this->riasec->scores($answers);

        $this->assertSame(8, $scores['R']);
        $this->assertSame(4, $scores['I']);
        $this->assertSame(2, $scores['C']);
        $this->assertSame(0, $scores['A']);
    }

    public function test_dimensi_tidak_dikenal_diabaikan(): void
    {
        $scores = $this->riasec->scores([
            ['dimension' => 'X', 'score' => 5],
            ['dimension' => 'R', 'score' => 5],
        ]);

        $this->assertSame(5, $scores['R']);
        $this->assertSame(['R', 'I', 'A', 'S', 'E', 'C'], array_keys($scores));
    }

    public function test_jumlah_butir_dihitung_per_dimensi(): void
    {
        $counts = $this->riasec->questionCounts($this->answers(['R' => 10, 'I' => 10], 3));

        $this->assertSame(10, $counts['R']);
        $this->assertSame(10, $counts['I']);
        $this->assertSame(0, $counts['A']);
    }

    public function test_jawaban_terendah_menghasilkan_nol_persen(): void
    {
        // 10 butir x skor 1 = 10, yang merupakan skor minimum -> 0%.
        // Tanpa mengurangi skor minimum, hasilnya akan keliru menjadi 20%.
        $counts = ['R' => 10, 'I' => 10, 'A' => 10, 'S' => 10, 'E' => 10, 'C' => 10];
        $scores = ['R' => 10, 'I' => 10, 'A' => 10, 'S' => 10, 'E' => 10, 'C' => 10];

        $percentages = $this->riasec->percentages($scores, $counts);

        foreach ($percentages as $value) {
            $this->assertSame(0.0, $value);
        }
    }

    public function test_konversi_persentase_pada_titik_tengah_dan_maksimum(): void
    {
        $counts = ['R' => 10, 'I' => 10, 'A' => 10, 'S' => 10, 'E' => 10, 'C' => 10];
        $scores = ['R' => 50, 'I' => 30, 'A' => 20, 'S' => 40, 'E' => 10, 'C' => 35];

        $percentages = $this->riasec->percentages($scores, $counts);

        $this->assertSame(100.0, $percentages['R']);  // (50-10)/40
        $this->assertSame(50.0, $percentages['I']);   // (30-10)/40
        $this->assertSame(25.0, $percentages['A']);   // (20-10)/40
        $this->assertSame(75.0, $percentages['S']);   // (40-10)/40
        $this->assertSame(0.0, $percentages['E']);
        $this->assertSame(62.5, $percentages['C']);   // (35-10)/40
    }

    public function test_dimensi_tanpa_butir_soal_bernilai_nol(): void
    {
        $percentages = $this->riasec->percentages(['R' => 40], ['R' => 0]);

        $this->assertSame(0.0, $percentages['R']);
    }

    public function test_kode_holland_mengambil_tiga_dimensi_tertinggi(): void
    {
        $percentages = ['R' => 55.0, 'I' => 90.0, 'A' => 25.0, 'S' => 60.0, 'E' => 42.5, 'C' => 75.0];

        $this->assertSame('ICS', $this->riasec->hollandCode($percentages));
        $this->assertSame('I', $this->riasec->dominantType($percentages));
    }

    public function test_vektor_identik_menghasilkan_kecocokan_seratus_persen(): void
    {
        $vector = ['R' => 60.0, 'I' => 95.0, 'A' => 55.0, 'S' => 30.0, 'E' => 45.0, 'C' => 80.0];

        $this->assertSame(100.0, $this->riasec->compatibility($vector, $vector));
    }

    public function test_vektor_sejajar_dengan_besaran_berbeda_tetap_seratus_persen(): void
    {
        // Cosine mengukur kemiripan pola minat, bukan besarnya skor.
        $student = ['R' => 20.0, 'I' => 40.0, 'A' => 10.0, 'S' => 0.0, 'E' => 0.0, 'C' => 0.0];
        $program = ['R' => 40.0, 'I' => 80.0, 'A' => 20.0, 'S' => 0.0, 'E' => 0.0, 'C' => 0.0];

        $this->assertSame(100.0, $this->riasec->compatibility($student, $program));
    }

    public function test_vektor_tegak_lurus_menghasilkan_nol_persen(): void
    {
        $student = ['R' => 100.0, 'I' => 0.0, 'A' => 0.0, 'S' => 0.0, 'E' => 0.0, 'C' => 0.0];
        $program = ['R' => 0.0, 'I' => 100.0, 'A' => 0.0, 'S' => 0.0, 'E' => 0.0, 'C' => 0.0];

        $this->assertSame(0.0, $this->riasec->compatibility($student, $program));
    }

    public function test_kecocokan_pada_sudut_empat_puluh_lima_derajat(): void
    {
        // cos 45° = 1/√2 = 0.70710678... -> 70.7107 %
        $student = ['R' => 1.0, 'I' => 1.0, 'A' => 0.0, 'S' => 0.0, 'E' => 0.0, 'C' => 0.0];
        $program = ['R' => 1.0, 'I' => 0.0, 'A' => 0.0, 'S' => 0.0, 'E' => 0.0, 'C' => 0.0];

        $this->assertEqualsWithDelta(70.7107, $this->riasec->compatibility($student, $program), 1e-4);
    }

    public function test_vektor_nol_tidak_menyebabkan_pembagian_nol(): void
    {
        $zero = ['R' => 0.0, 'I' => 0.0, 'A' => 0.0, 'S' => 0.0, 'E' => 0.0, 'C' => 0.0];
        $program = ['R' => 60.0, 'I' => 95.0, 'A' => 55.0, 'S' => 30.0, 'E' => 45.0, 'C' => 80.0];

        $this->assertSame(0.0, $this->riasec->compatibility($zero, $program));
        $this->assertSame(0.0, $this->riasec->compatibility($program, $zero));
    }
}
