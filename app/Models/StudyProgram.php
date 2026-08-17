<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use App\Support\Riasec;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Alternatif keputusan pada perhitungan CoCoSo.
 */
class StudyProgram extends Model
{
    use HasFactory, RecordsActivity;

    protected $table = 'study_programs';

    protected $fillable = [
        'code', 'name', 'level', 'department', 'description',
        'riasec_r', 'riasec_i', 'riasec_a', 'riasec_s', 'riasec_e', 'riasec_c',
        'employment_rate', 'alumni_count', 'employed_count', 'tracer_year', 'tracer_updated_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'riasec_r' => 'integer',
            'riasec_i' => 'integer',
            'riasec_a' => 'integer',
            'riasec_s' => 'integer',
            'riasec_e' => 'integer',
            'riasec_c' => 'integer',
            'employment_rate' => 'float',
            'alumni_count' => 'integer',
            'employed_count' => 'integer',
            'tracer_year' => 'integer',
            'tracer_updated_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<AssessmentResult> */
    public function results(): HasMany
    {
        return $this->hasMany(AssessmentResult::class);
    }

    /** @return HasMany<AssessmentPriority> */
    public function priorities(): HasMany
    {
        return $this->hasMany(AssessmentPriority::class);
    }

    /**
     * Mata pelajaran pendukung prodi — paling banyak dua, sesuai aturan SNBP.
     *
     * @return BelongsToMany<Subject>
     */
    public function supportSubjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'study_program_subjects')
            ->withPivot('position')
            ->withTimestamps()
            ->orderBy('study_program_subjects.position');
    }

    /** @param  Builder<StudyProgram>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Vektor profil RIASEC prodi dengan urutan baku R,I,A,S,E,C.
     *
     * @return array<string, float>
     */
    public function riasecVector(): array
    {
        $vector = [];
        foreach (Riasec::DIMENSIONS as $dimension) {
            $vector[$dimension] = (float) $this->{'riasec_'.strtolower($dimension)};
        }

        return $vector;
    }

    /** Tiga dimensi RIASEC tertinggi, mis. "ICR". */
    public function getHollandCodeAttribute(): string
    {
        return Riasec::hollandCode($this->riasecVector());
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->level.' '.$this->name);
    }

    /** Persentase alumni terserap kerja, siap tampil. */
    public function getEmploymentPercentAttribute(): float
    {
        return round($this->employment_rate * 100, 2);
    }
}
