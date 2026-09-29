<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\RiasecQuestion;
use App\Support\Riasec;

/**
 * Reliabilitas konsistensi internal instrumen RIASEC (Cronbach's alpha).
 *
 * Menjawab pertanyaan yang selalu muncul atas instrumen buatan sendiri: apakah
 * butir-butir dalam satu dimensi benar-benar mengukur hal yang sama? Tanpa angka
 * ini, panjang instrumen hanya bisa diperdebatkan lewat dugaan.
 *
 *   alpha = k/(k-1) x (1 - Sum(varian tiap butir) / varian skor total)
 *
 * dengan k = jumlah butir pada dimensi tersebut. Variannya varian populasi,
 * bukan sampel: yang dihitung adalah sebaran responden yang benar-benar
 * terkumpul, bukan penaksiran terhadap populasi yang lebih besar.
 *
 * Hanya butir yang aktif SAAT INI yang dipakai, dan hanya responden yang
 * menjawab seluruh butir itu. Instrumen ini pernah berubah — 42 butir menjadi
 * 24 — sehingga mencampur responden dari dua versi akan menghitung varian atas
 * kolom yang isinya tidak sebanding.
 */
final class ReliabilityService
{
    /**
     * Jumlah responden minimum sebelum angkanya layak dilaporkan.
     *
     * Alpha bisa dihitung dari 2 responden, tetapi angkanya tidak stabil dan
     * menyesatkan bila dikutip. Ambang 30 adalah rujukan lazim untuk pelaporan
     * reliabilitas; di bawah itu hasilnya tetap ditampilkan namun ditandai belum
     * memadai, supaya tidak diam-diam masuk ke laporan.
     */
    public const MINIMUM_RESPONDENTS = 30;

    /**
     * @return array{
     *     respondents: int,
     *     sufficient: bool,
     *     minimum: int,
     *     dimensions: array<string, array{items: int, alpha: float|null, interpretation: string}>,
     *     overall: array{items: int, alpha: float|null, interpretation: string}|null
     * }
     */
    public function riasec(): array
    {
        $questions = RiasecQuestion::query()->active()->get();
        $questionIds = $questions->pluck('id')->all();

        $responses = $this->responses($questionIds);

        $dimensions = [];

        foreach (Riasec::DIMENSIONS as $dimension) {
            $ids = $questions->where('dimension', $dimension)->pluck('id')->all();
            $alpha = $this->alpha($responses, $ids);

            $dimensions[$dimension] = [
                'items' => count($ids),
                'alpha' => $alpha,
                'interpretation' => $this->interpret($alpha),
            ];
        }

        $overallAlpha = $this->alpha($responses, $questionIds);

        return [
            'respondents' => count($responses),
            'sufficient' => count($responses) >= self::MINIMUM_RESPONDENTS,
            'minimum' => self::MINIMUM_RESPONDENTS,
            'dimensions' => $dimensions,
            'overall' => [
                'items' => count($questionIds),
                'alpha' => $overallAlpha,
                'interpretation' => $this->interpret($overallAlpha),
            ],
        ];
    }

    /**
     * Matriks jawaban responden × butir, hanya untuk responden yang lengkap.
     *
     * @param  array<int, int>  $questionIds
     * @return array<int, array<int, float>>
     */
    private function responses(array $questionIds): array
    {
        if ($questionIds === []) {
            return [];
        }

        $rows = [];

        Assessment::query()
            ->completed()
            ->with(['answers' => fn ($query) => $query->whereIn('riasec_question_id', $questionIds)])
            ->chunk(200, function ($assessments) use (&$rows, $questionIds) {
                foreach ($assessments as $assessment) {
                    $scores = $assessment->answers->pluck('score', 'riasec_question_id');

                    // Responden yang butirnya tidak lengkap dilewati: mengisi
                    // kekosongan dengan rerata akan menekan varian dan menaikkan
                    // alpha secara semu.
                    if ($scores->count() !== count($questionIds)) {
                        continue;
                    }

                    // Dikunci pada ID butir, bukan posisi, supaya alpha per dimensi
                    // dapat mengambil sebagian kolom tanpa salah sejajar.
                    $row = [];
                    foreach ($questionIds as $id) {
                        $row[$id] = (float) $scores[$id];
                    }

                    $rows[] = $row;
                }
            });

        return $rows;
    }

    /**
     * @param  array<int, array<int, float>>  $responses  baris = responden, kunci kolom = id butir
     * @param  array<int, int>  $itemIds  id butir yang ikut dihitung
     */
    private function alpha(array $responses, array $itemIds): ?float
    {
        $itemCount = count($itemIds);

        // Alpha tidak terdefinisi untuk butir tunggal (pembagi k-1 menjadi nol),
        // dan tidak bermakna tanpa sebaran antar responden.
        if ($itemCount < 2 || count($responses) < 2) {
            return null;
        }

        $columns = array_fill_keys($itemIds, []);
        $totals = [];

        foreach ($responses as $scores) {
            $total = 0.0;

            foreach ($itemIds as $id) {
                $columns[$id][] = $scores[$id];
                $total += $scores[$id];
            }

            $totals[] = $total;
        }

        $itemVariance = 0.0;
        foreach ($columns as $column) {
            $itemVariance += $this->variance($column);
        }

        $totalVariance = $this->variance($totals);

        // Seluruh responden menjawab identik: tidak ada apa pun untuk diukur.
        if ($totalVariance <= 0.0) {
            return null;
        }

        $alpha = $itemCount / ($itemCount - 1) * (1 - $itemVariance / $totalVariance);

        // Alpha bisa bernilai negatif ketika butir saling berlawanan arah. Itu
        // hasil yang sah dan informatif, jadi tidak dijepit ke nol.
        return round($alpha, 4);
    }

    /** @param  array<int, float>  $values */
    private function variance(array $values): float
    {
        $count = count($values);

        if ($count === 0) {
            return 0.0;
        }

        $mean = array_sum($values) / $count;
        $sum = 0.0;

        foreach ($values as $value) {
            $sum += ($value - $mean) ** 2;
        }

        return $sum / $count;
    }

    /** Tafsir lazim atas nilai alpha (George & Mallery). */
    private function interpret(?float $alpha): string
    {
        return match (true) {
            $alpha === null => 'belum dapat dihitung',
            $alpha >= 0.9 => 'sangat baik',
            $alpha >= 0.8 => 'baik',
            $alpha >= 0.7 => 'memadai',
            $alpha >= 0.6 => 'dipertanyakan',
            $alpha >= 0.5 => 'lemah',
            default => 'tidak dapat diterima',
        };
    }
}
