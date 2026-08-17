<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Mata pelajaran rapor yang dapat ditetapkan sebagai mapel pendukung prodi.
 */
class Subject extends Model
{
    use HasFactory, RecordsActivity;

    protected $table = 'subjects';

    protected $fillable = [
        'code', 'name', 'education_level', 'group', 'sort_order', 'is_active',
    ];

    /** Jenjang asal mapel, dipakai sebagai pengelompokan pilihan di form admin. */
    public const EDUCATION_LEVELS = [
        'umum' => 'Umum (SMA & SMK)',
        'SMA' => 'SMA',
        'SMK' => 'SMK',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsToMany<StudyProgram> */
    public function studyPrograms(): BelongsToMany
    {
        return $this->belongsToMany(StudyProgram::class, 'study_program_subjects')
            ->withPivot('position')
            ->withTimestamps();
    }

    /** @param  Builder<Subject>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** @param  Builder<Subject>  $query */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('name');
    }

    /** Label siap tampil, mis. "Fisika (SMA · IPA)". */
    public function getDisplayNameAttribute(): string
    {
        $context = array_filter([
            $this->education_level === 'umum' ? null : $this->education_level,
            $this->group,
        ]);

        return $context === []
            ? $this->name
            : $this->name.' ('.implode(' · ', $context).')';
    }
}
