<?php

namespace App\Services\Tpa;

use App\Models\TpaBankQuestion;
use App\Models\TpaQuestion;
use App\Models\TpaUser;
use Illuminate\Support\Collection;

/**
 * Memilih soal bank untuk satu tryout. Urutan prioritas:
 *  1. soal yang belum pernah dikerjakan, di level yang dipilih atau level tetangganya;
 *  2. soal yang dulu dijawab salah/kosong (mengulang kesalahan = latihan yang baik);
 *  3. soal lain yang paling lama tidak muncul.
 */
class BankPicker
{
    /**
     * @return Collection<int, TpaBankQuestion>
     */
    public function pick(TpaUser $user, string $category, int $difficulty, int $count): Collection
    {
        $target = TpaBankQuestion::levelFor($difficulty);
        $history = $this->history($user, $category);

        return TpaBankQuestion::where('category', $category)->get()
            ->sortBy(function (TpaBankQuestion $q) use ($target, $history) {
                $distance = abs($q->level - $target);
                $seen = $history->get($q->id);

                return [
                    $distance > 1 ? 1 : 0,                      // level jauh paling akhir
                    $seen ? 1 : 0,                              // yang belum pernah muncul dulu
                    $seen && ! $seen['last_correct'] ? 0 : 1,   // lalu yang dulu salah
                    $distance,                                  // level paling dekat
                    $seen['last_seen'] ?? 0,                    // yang paling lama tidak muncul
                    random_int(0, PHP_INT_MAX),
                ];
            })
            ->take($count)
            ->shuffle()
            ->values();
    }

    /**
     * Stok per level untuk pengguna: total soal dan yang belum pernah dikerjakan.
     *
     * @return array<int, array{total:int, unseen:int}>
     */
    public function stock(TpaUser $user, string $category): array
    {
        $seenIds = $this->history($user, $category)->keys()->all();
        $questions = TpaBankQuestion::where('category', $category)->get(['id', 'level']);

        $stock = [];
        foreach (array_keys(TpaBankQuestion::LEVELS) as $level) {
            $atLevel = $questions->where('level', $level);
            $stock[$level] = [
                'total' => $atLevel->count(),
                'unseen' => $atLevel->whereNotIn('id', $seenIds)->count(),
            ];
        }

        return $stock;
    }

    /**
     * Ringkasan bank untuk dasbor: jumlah soal tersedia dan jumlah soal berbeda
     * yang sudah dijawab pengguna (soal yang hanya muncul tanpa dijawab tidak dihitung).
     *
     * @return array{total:int, answered:int}
     */
    public function progress(TpaUser $user, string $category): array
    {
        $bankIds = TpaBankQuestion::where('category', $category)->pluck('id');

        $answered = TpaQuestion::query()
            ->whereIn('tpa_bank_question_id', $bankIds)
            ->whereNotNull('answered_at')
            ->whereHas('tryout', fn ($q) => $q->where('tpa_user_id', $user->id))
            ->distinct()
            ->count('tpa_bank_question_id');

        return ['total' => $bankIds->count(), 'answered' => $answered];
    }

    /** @return Collection<int, array{last_seen:int, last_correct:bool}> */
    private function history(TpaUser $user, string $category): Collection
    {
        return TpaQuestion::query()
            ->whereNotNull('tpa_bank_question_id')
            ->whereHas('tryout', fn ($q) => $q->where('tpa_user_id', $user->id)->where('category', $category))
            ->orderBy('id')
            ->get(['tpa_bank_question_id', 'is_correct', 'created_at'])
            ->groupBy('tpa_bank_question_id')
            ->map(fn ($rows) => [
                'last_seen' => $rows->last()->created_at->getTimestamp(),
                'last_correct' => (bool) $rows->last()->is_correct,
            ]);
    }
}
