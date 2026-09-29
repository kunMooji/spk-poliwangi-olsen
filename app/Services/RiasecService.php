<?php

namespace App\Services;

use App\Support\Riasec;

/**
 * Konversi jawaban kuesioner skala Likert menjadi profil kepribadian RIASEC,
 * serta perhitungan kesesuaian kepribadian terhadap program studi (kriteria C3).
 *
 * Kelas ini tidak menyentuh database supaya bisa diuji dengan angka langsung.
 */
final class RiasecService
{
    public function __construct(
        private readonly int $likertMin = 1,
        private readonly int $likertMax = 5,
    ) {}

    /**
     * Akumulasi skor Likert per dimensi.
     *
     * @param  iterable<object|array{dimension: string, score: int|float}>  $answers
     * @return array<string, int>
     */
    public function scores(iterable $answers): array
    {
        $scores = array_fill_keys(Riasec::DIMENSIONS, 0);

        foreach ($answers as $answer) {
            $dimension = strtoupper((string) (is_array($answer) ? $answer['dimension'] : $answer->dimension));

            if (! array_key_exists($dimension, $scores)) {
                continue;
            }

            $scores[$dimension] += (int) (is_array($answer) ? $answer['score'] : $answer->score);
        }

        return $scores;
    }

    /**
     * Jumlah butir soal per dimensi — dipakai sebagai pembagi saat konversi persentase.
     *
     * @param  iterable<object|array{dimension: string}>  $questions
     * @return array<string, int>
     */
    public function questionCounts(iterable $questions): array
    {
        $counts = array_fill_keys(Riasec::DIMENSIONS, 0);

        foreach ($questions as $question) {
            $dimension = strtoupper((string) (is_array($question) ? $question['dimension'] : $question->dimension));

            if (array_key_exists($dimension, $counts)) {
                $counts[$dimension]++;
            }
        }

        return $counts;
    }

    /**
     * Ubah skor mentah menjadi persentase 0-100 per dimensi.
     *
     *   persen = (skor - skorMinimum) / (skorMaksimum - skorMinimum) × 100
     *
     * dengan skorMinimum = jumlahSoal × likertMin dan skorMaksimum = jumlahSoal × likertMax.
     * Pengurangan skorMinimum penting: tanpa itu, responden yang menjawab 1 pada
     * seluruh butir akan tetap memperoleh 20% dan bukan 0%.
     *
     * @param  array<string, int>  $scores
     * @param  array<string, int>  $questionCounts
     * @return array<string, float>
     */
    public function percentages(array $scores, array $questionCounts): array
    {
        $percentages = [];

        foreach (Riasec::DIMENSIONS as $dimension) {
            $count = (int) ($questionCounts[$dimension] ?? 0);
            $lowest = $count * $this->likertMin;
            $highest = $count * $this->likertMax;
            $range = $highest - $lowest;

            if ($count === 0 || $range <= 0) {
                $percentages[$dimension] = 0.0;

                continue;
            }

            $score = (float) ($scores[$dimension] ?? 0);
            $percent = ($score - $lowest) / $range * 100;

            $percentages[$dimension] = round(min(max($percent, 0.0), 100.0), 2);
        }

        return $percentages;
    }

    /**
     * Kode Holland: tiga dimensi dengan persentase tertinggi, mis. "IRC".
     *
     * @param  array<string, float>  $percentages
     */
    public function hollandCode(array $percentages, int $length = 3): string
    {
        return Riasec::hollandCode($percentages, $length);
    }

    /**
     * Dimensi paling dominan.
     *
     * @param  array<string, float>  $percentages
     */
    public function dominantType(array $percentages): string
    {
        return substr($this->hollandCode($percentages, 1), 0, 1);
    }

    /** Nilai C3 ketika kesesuaian tidak terdefinisi: tepat di tengah skala. */
    public const NEUTRAL_COMPATIBILITY = 50.0;

    /**
     * Kriteria C3 — kesesuaian kepribadian terhadap program studi, dihitung sebagai
     * korelasi Pearson antara vektor RIASEC calon mahasiswa dan profil RIASEC prodi,
     * lalu dipetakan ke skala 0-100:
     *
     *   r = Σ(a_i - ā)(b_i - b̄) / sqrt( Σ(a_i - ā)² · Σ(b_i - b̄)² ),  r ∈ [-1, 1]
     *   nilai = (r + 1) / 2 × 100
     *
     * Artinya: 100 = pola minat searah sempurna, 50 = tidak berkorelasi,
     * 0 = berlawanan sempurna.
     *
     * **Mengapa bukan cosine similarity.** Cosine tanpa pemusatan hanya bekerja pada
     * vektor non-negatif, sehingga sudut antar profil RIASEC tidak pernah lebar dan
     * hasilnya selalu terkumpul di pita sempit yang tinggi — pada data nyata sistem
     * ini rentangnya cuma 7 sampai 28 poin, dan yang terendah pun masih di atas 60.
     * Pita sempit itu jadi masalah serius begitu dinormalisasi: batas min-max sampel
     * merentangkannya paksa ke 0..1 penuh, sehingga selisih kecocokan yang sebenarnya
     * sepele tampil seolah selisih terburuk lawan terbaik, dan C3 mendominasi hasil
     * jauh melebihi bobotnya (terukur 79% dari seluruh daya pisah, padahal bobotnya
     * 0.35). Pemusatan pada Pearson membuat korelasi negatif benar-benar mungkin,
     * rentangnya melebar ke 46-82 poin, dan skalanya punya tafsir absolut sehingga
     * bisa dinormalisasi dengan batas tetap 0..100 seperti kriteria lain.
     *
     * Sifat yang tetap dipertahankan dari cosine: yang diukur adalah *pola* minat,
     * bukan besar skornya. Responden yang menjawab konsisten rendah tetap dinilai
     * cocok selama urutan minatnya sejalan dengan profil prodi — bahkan lebih tegas,
     * karena Pearson kebal terhadap pergeseran maupun penskalaan.
     *
     * @param  array<string, float>  $studentVector
     * @param  array<string, float>  $programVector
     */
    public function compatibility(array $studentVector, array $programVector): float
    {
        $count = count(Riasec::DIMENSIONS);

        $studentMean = 0.0;
        $programMean = 0.0;

        foreach (Riasec::DIMENSIONS as $dimension) {
            $studentMean += (float) ($studentVector[$dimension] ?? 0);
            $programMean += (float) ($programVector[$dimension] ?? 0);
        }

        $studentMean /= $count;
        $programMean /= $count;

        $covariance = 0.0;
        $studentVariance = 0.0;
        $programVariance = 0.0;

        foreach (Riasec::DIMENSIONS as $dimension) {
            $a = (float) ($studentVector[$dimension] ?? 0) - $studentMean;
            $b = (float) ($programVector[$dimension] ?? 0) - $programMean;

            $covariance += $a * $b;
            $studentVariance += $a * $a;
            $programVariance += $b * $b;
        }

        // Profil yang seluruh dimensinya bernilai sama tidak punya pola apa pun untuk
        // dicocokkan — termasuk responden yang menjawab seragam pada semua butir.
        // Korelasinya tidak terdefinisi, jadi dinilai netral alih-alih nol; nol berarti
        // "berlawanan setengah", sebuah pernyataan yang tidak didukung data.
        if ($studentVariance <= 0.0 || $programVariance <= 0.0) {
            return self::NEUTRAL_COMPATIBILITY;
        }

        $correlation = $covariance / sqrt($studentVariance * $programVariance);

        // Dijepit terhadap galat pembulatan floating point, bukan terhadap data.
        $correlation = min(max($correlation, -1.0), 1.0);

        return round(($correlation + 1) / 2 * 100, 4);
    }

    public function likertMin(): int
    {
        return $this->likertMin;
    }

    public function likertMax(): int
    {
        return $this->likertMax;
    }
}
