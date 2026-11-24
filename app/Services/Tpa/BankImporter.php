<?php

namespace App\Services\Tpa;

use App\Models\TpaBankQuestion;
use InvalidArgumentException;

/**
 * Mengimpor/memperbarui soal bank dari file JSON. Aman dijalankan berulang:
 * soal dicocokkan lewat "code", jadi perbaikan isi soal cukup dengan
 * mengedit JSON lalu mengimpor ulang (php artisan tpa:import-bank).
 *
 * Format tiap soal:
 * {"code":"tiu-001","category":"tiu","sub_topic":"...","level":1-4,"question":"...",
 *  "options":{"A":"...","B":"...","C":"...","D":"...","E":"..."},"answer":"C","hint":"...","explanation":"..."}
 * Untuk TKP, "answer" diganti "scores":{"A":3,"B":5,"C":1,"D":2,"E":4}.
 */
class BankImporter
{
    private const KEYS = ['A', 'B', 'C', 'D', 'E'];

    public function import(string $path): int
    {
        $items = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        foreach ($items as $i => $item) {
            $this->validate($item, $i);

            $isTkp = $item['category'] === 'tkp';
            $options = array_map(fn ($key) => [
                'key' => $key,
                'text' => $item['options'][$key],
                'score' => $isTkp ? (int) $item['scores'][$key] : ($key === $item['answer'] ? 5 : 0),
            ], self::KEYS);

            TpaBankQuestion::updateOrCreate(['code' => $item['code']], [
                'category' => $item['category'],
                'sub_topic' => $item['sub_topic'],
                'level' => $item['level'],
                'question' => $item['question'],
                'options' => $options,
                'correct_key' => $isTkp ? null : $item['answer'],
                'hint' => $item['hint'],
                'explanation' => $item['explanation'],
            ]);
        }

        return count($items);
    }

    private function validate(array $item, int $index): void
    {
        $where = 'soal #'.($index + 1).' ('.($item['code'] ?? 'tanpa code').')';

        foreach (['code', 'category', 'sub_topic', 'level', 'question', 'options', 'hint', 'explanation'] as $field) {
            if (blank($item[$field] ?? null)) {
                throw new InvalidArgumentException("Field {$field} kosong pada {$where}.");
            }
        }

        if (! array_key_exists($item['category'], config('tpa.categories'))) {
            throw new InvalidArgumentException("Kategori tidak dikenal pada {$where}.");
        }

        if (! in_array($item['level'], [1, 2, 3, 4], true)) {
            throw new InvalidArgumentException("Level harus 1-4 pada {$where}.");
        }

        if (array_keys($item['options']) !== self::KEYS) {
            throw new InvalidArgumentException("Opsi harus A-E berurutan pada {$where}.");
        }

        if ($item['category'] === 'tkp') {
            $scores = array_map('intval', array_values($item['scores'] ?? []));
            sort($scores);
            if ($scores !== [1, 2, 3, 4, 5]) {
                throw new InvalidArgumentException("Skor TKP harus 1-5 unik pada {$where}.");
            }
        } elseif (! in_array($item['answer'] ?? null, self::KEYS, true)) {
            throw new InvalidArgumentException("Jawaban harus A-E pada {$where}.");
        }
    }
}
