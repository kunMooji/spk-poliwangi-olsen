<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rerata nilai rapor seluruh mapel pada satu semester (komponen pertama SNBP).
 */
class AssessmentRaporSemester extends Model
{
    protected $table = 'assessment_rapor_semesters';

    protected $fillable = ['assessment_id', 'semester', 'average_score'];

    protected function casts(): array
    {
        return [
            'semester' => 'integer',
            'average_score' => 'float',
        ];
    }

    /** @return BelongsTo<Assessment, AssessmentRaporSemester> */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }
}
