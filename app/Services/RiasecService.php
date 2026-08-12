<?php

namespace App\Services;

use App\Support\Riasec;

/**
 * Konversi jawaban kuesioner skala Likert menjadi profil kepribadian RIASEC,
 * serta perhitungan kesesuaian kepribadian terhadap program studi (kriteria C7).
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

    /**
     * Kriteria C7 — kesesuaian kepribadian terhadap program studi, dihitung sebagai
     * cosine similarity antara vektor RIASEC calon mahasiswa dan profil RIASEC prodi:
     *
     *   cos θ = (a · b) / (‖a‖ · ‖b‖),   hasil dikalikan 100.
     *
     * Cosine dipilih karena mengukur kemiripan *pola minat*, bukan besar skornya —
     * responden yang menjawab konsisten rendah tetap dinilai cocok selama urutan
     * minatnya sejalan dengan profil prodi.
     *
     * @param  array<string, float>  $studentVector
     * @param  array<string, float>  $programVector
     */
    public function compatibility(array $studentVector, array $programVector): float
    {
        $dotProduct = 0.0;
        $studentNorm = 0.0;
        $programNorm = 0.0;

        foreach (Riasec::DIMENSIONS as $dimension) {
            $a = (float) ($studentVector[$dimension] ?? 0);
            $b = (float) ($programVector[$dimension] ?? 0);

            $dotProduct += $a * $b;
            $studentNorm += $a * $a;
            $programNorm += $b * $b;
        }

        // Vektor nol tidak punya arah, sehingga kemiripannya tidak terdefinisi.
        if ($studentNorm <= 0.0 || $programNorm <= 0.0) {
            return 0.0;
        }

        $similarity = $dotProduct / (sqrt($studentNorm) * sqrt($programNorm));

        return round(min(max($similarity, 0.0), 1.0) * 100, 4);
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
