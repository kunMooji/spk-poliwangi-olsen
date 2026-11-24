<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TpaQuestion extends Model
{
    protected $fillable = [
        'tpa_tryout_id', 'tpa_bank_question_id', 'number', 'sub_topic', 'question', 'options', 'correct_key', 'hint',
        'explanation', 'user_answer', 'is_correct', 'points', 'time_spent_ms', 'hint_used', 'answered_at',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_correct' => 'boolean',
            'hint_used' => 'boolean',
            'answered_at' => 'datetime',
        ];
    }

    public function tryout(): BelongsTo
    {
        return $this->belongsTo(TpaTryout::class, 'tpa_tryout_id');
    }

    /** Poin untuk sebuah pilihan: TKP memakai skor per opsi, TWK/TIU 5/0. */
    public function pointsFor(?string $key): int
    {
        if ($key === null) {
            return 0;
        }

        if ($this->correct_key === null) {
            foreach ($this->options as $option) {
                if ($option['key'] === $key) {
                    return (int) $option['score'];
                }
            }

            return 0;
        }

        return $key === $this->correct_key ? 5 : 0;
    }

    /** Kunci "terbaik": TWK/TIU = kunci jawaban, TKP = opsi bernilai tertinggi. */
    public function bestKey(): ?string
    {
        if ($this->correct_key !== null) {
            return $this->correct_key;
        }

        return collect($this->options)->sortByDesc('score')->first()['key'] ?? null;
    }
}
