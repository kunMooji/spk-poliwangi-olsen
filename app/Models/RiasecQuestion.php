<?php

namespace App\Models;

use App\Support\Riasec;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Butir pernyataan kuesioner minat bakat RIASEC (dijawab dengan skala Likert 1-5).
 */
class RiasecQuestion extends Model
{
    protected $table = 'riasec_questions';

    protected $fillable = ['statement', 'dimension', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<AssessmentAnswer> */
    public function answers(): HasMany
    {
        return $this->hasMany(AssessmentAnswer::class);
    }

    /** @param  Builder<RiasecQuestion>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** @param  Builder<RiasecQuestion>  $query */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }

    public function getDimensionNameAttribute(): string
    {
        return Riasec::name($this->dimension);
    }
}
