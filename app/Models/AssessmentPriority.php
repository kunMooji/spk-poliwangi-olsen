<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Urutan prioritas prodi pilihan calon mahasiswa (dasar perhitungan C8).
 */
class AssessmentPriority extends Model
{
    protected $table = 'assessment_priorities';

    protected $fillable = ['assessment_id', 'study_program_id', 'priority_order'];

    protected function casts(): array
    {
        return ['priority_order' => 'integer'];
    }

    /** @return BelongsTo<Assessment, AssessmentPriority> */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    /** @return BelongsTo<StudyProgram, AssessmentPriority> */
    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class);
    }
}
