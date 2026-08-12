<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Kriteria penilaian C1..C9 beserta bobot keputusan CoCoSo.
 */
class Criteria extends Model
{
    protected $table = 'criteria';

    protected $fillable = [
        'code', 'name', 'weight', 'type', 'source', 'subject',
        'unit', 'description', 'sort_order', 'is_active',
    ];

    /** Sumber nilai x_ij, dipakai DecisionMatrixBuilder. */
    public const SOURCES = [
        'subject_score' => 'Nilai Rapor',
        'riasec' => 'Kesesuaian RIASEC',
        'priority' => 'Prioritas Minat',
        'tracer' => 'Tracer Study',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'float',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @param  Builder<Criteria>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** @param  Builder<Criteria>  $query */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('code');
    }

    public function isBenefit(): bool
    {
        return $this->type === 'benefit';
    }

    public function getSourceLabelAttribute(): string
    {
        return self::SOURCES[$this->source] ?? $this->source;
    }

    /** Total bobot seluruh kriteria aktif — idealnya bernilai 1. */
    public static function totalActiveWeight(): float
    {
        return (float) static::query()->active()->sum('weight');
    }
}
