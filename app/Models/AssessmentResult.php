<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Jejak perhitungan CoCoSo untuk satu alternatif prodi dalam satu sesi tes.
 *
 * @property array<string, float> $matrix Nilai x_ij mentah per kode kriteria
 * @property array<string, float> $normalized Nilai r_ij ternormalisasi per kode kriteria
 */
class AssessmentResult extends Model
{
    protected $table = 'assessment_results';

    protected $fillable = [
        'assessment_id', 'study_program_id', 'matrix', 'normalized',
        's_value', 'p_value', 'k_a', 'k_b', 'k_c', 'k_value', 'k_normal', 'ranking',
    ];

    protected function casts(): array
    {
        return [
            'matrix' => 'array',
            'normalized' => 'array',
            's_value' => 'float',
            'p_value' => 'float',
            'k_a' => 'float',
            'k_b' => 'float',
            'k_c' => 'float',
            'k_value' => 'float',
            'k_normal' => 'float',
            'ranking' => 'integer',
        ];
    }

    /** @return BelongsTo<Assessment, AssessmentResult> */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    /** @return BelongsTo<StudyProgram, AssessmentResult> */
    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class);
    }
}
