<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Nilai rapor pada satu mata pelajaran pendukung (komponen kedua SNBP).
 *
 * `score` null berarti responden tidak menempuh mapel tersebut.
 */
class AssessmentSubjectScore extends Model
{
    protected $table = 'assessment_subject_scores';

    protected $fillable = ['assessment_id', 'subject_id', 'score'];

    protected function casts(): array
    {
        return ['score' => 'float'];
    }

    /** @return BelongsTo<Assessment, AssessmentSubjectScore> */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    /** @return BelongsTo<Subject, AssessmentSubjectScore> */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
