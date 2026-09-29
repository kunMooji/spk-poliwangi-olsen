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
        // Yang diukur adalah pola minat, bukan besarnya skor.
        $student = ['R' => 20.0, 'I' => 40.0, 'A' => 10.0, 'S' => 0.0, 'E' => 0.0, 'C' => 0.0];
        $program = ['R' => 40.0, 'I' => 80.0, 'A' => 20.0, 'S' => 0.0, 'E' => 0.0, 'C' => 0.0];

        $this->assertSame(100.0, $this->riasec->compatibility($student, $program));
    }

    public function test_pola_yang_bergeser_seragam_tetap_seratus_persen(): void
    {
        // Kekebalan terhadap pergeseran adalah yang membedakan korelasi Pearson dari
        // cosine: responden yang menjawab konsisten lebih tinggi pada seluruh dimensi
        // tetap punya pola minat yang sama persis.
        $student = ['R' => 10.0, 'I' => 30.0, 'A' => 20.0, 'S' => 50.0, 'E' => 40.0, 'C' => 0.0];
        $program = ['R' => 60.0, 'I' => 80.0, 'A' => 70.0, 'S' => 100.0, 'E' => 90.0, 'C' => 50.0];

        $this->assertSame(100.0, $this->riasec->compatibility($student, $program));
    }

    public function test_pola_berlawanan_sempurna_menghasilkan_nol(): void
    {
        // Korelasi -1 dipetakan ke ujung bawah skala.
        $student = ['R' => 10.0, 'I' => 20.0, 'A' => 30.0, 'S' => 40.0, 'E' => 50.0, 'C' => 60.0];
        $program = ['R' => 60.0, 'I' => 50.0, 'A' => 40.0, 'S' => 30.0, 'E' => 20.0, 'C' => 10.0];

        $this->assertSame(0.0, $this->riasec->compatibility($student, $program));
    }

    public function test_pola_tidak_berkorelasi_menghasilkan_lima_puluh(): void
    {
        // Korelasi 0 adalah titik tengah skala, bukan ujung bawah — inilah alasan
        // C3 sekarang boleh dinormalisasi dengan batas tetap 0..100.
        $student = ['R' => 1.0, 'I' => -1.0, 'A' => 1.0, 'S' => -1.0, 'E' => 1.0, 'C' => -1.0];
        $program = ['R' => 1.0, 'I' => 1.0, 'A' => -1.0, 'S' => -1.0, 'E' => 0.0, 'C' => 0.0];

        $this->assertEqualsWithDelta(50.0, $this->riasec->compatibility($student, $program), 1e-9);
    }

    public function test_profil_datar_dinilai_netral_bukan_nol(): void
    {
        // Responden yang menjawab seragam pada seluruh butir tidak punya pola untuk
        // dicocokkan. Korelasinya tidak terdefinisi, dan menilainya 0 berarti
        // menyatakan "berlawanan" — sebuah klaim yang tidak didukung data.
        $datar = ['R' => 40.0, 'I' => 40.0, 'A' => 40.0, 'S' => 40.0, 'E' => 40.0, 'C' => 40.0];
        $program = ['R' => 60.0, 'I' => 95.0, 'A' => 55.0, 'S' => 30.0, 'E' => 45.0, 'C' => 80.0];

        $this->assertSame(50.0, $this->riasec->compatibility($datar, $program));
        $this->assertSame(50.0, $this->riasec->compatibility($program, $datar));
    }

    public function test_vektor_nol_tidak_menyebabkan_pembagian_nol(): void
    {
        $zero = ['R' => 0.0, 'I' => 0.0, 'A' => 0.0, 'S' => 0.0, 'E' => 0.0, 'C' => 0.0];
        $program = ['R' => 60.0, 'I' => 95.0, 'A' => 55.0, 'S' => 30.0, 'E' => 45.0, 'C' => 80.0];

        $this->assertSame(50.0, $this->riasec->compatibility($zero, $program));
        $this->assertSame(50.0, $this->riasec->compatibility($program, $zero));
    }

    public function test_hasil_selalu_berada_pada_skala_nol_seratus(): void
    {
        for ($i = 0; $i < 500; $i++) {
            $student = [];
            $program = [];
            foreach (['R', 'I', 'A', 'S', 'E', 'C'] as $dimension) {
                $student[$dimension] = (float) random_int(0, 10000) / 100;
                $program[$dimension] = (float) random_int(0, 10000) / 100;
            }

            $value = $this->riasec->compatibility($student, $program);

            $this->assertGreaterThanOrEqual(0.0, $value);
            $this->assertLessThanOrEqual(100.0, $value);
        }
    }
}
