<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TpaTryout extends Model
{
    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_FINISHED = 'finished';

    protected $fillable = [
        'tpa_user_id', 'category', 'difficulty', 'source', 'status', 'score', 'max_score',
        'correct_count', 'total_time_ms', 'started_at', 'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(TpaUser::class, 'tpa_user_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(TpaQuestion::class)->orderBy('number');
    }

    public function isFinished(): bool
    {
        return $this->status === self::STATUS_FINISHED;
    }

    public function isTkp(): bool
    {
        return $this->category === 'tkp';
    }

    public function categoryLabel(): string
    {
        return config("tpa.categories.{$this->category}.short", strtoupper($this->category));
    }

    /** Persentase ketepatan: TWK/TIU = benar/total, TKP = skor/skor maksimum. */
    public function accuracy(): float
    {
        return $this->max_score > 0 ? round($this->score / $this->max_score * 100, 1) : 0.0;
    }

    public function averageSecondsPerQuestion(): float
    {
        $count = config('tpa.questions_per_tryout');

        return $count > 0 ? round($this->total_time_ms / 1000 / $count, 1) : 0.0;
    }
}
