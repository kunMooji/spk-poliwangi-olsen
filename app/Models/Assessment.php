<?php

namespace App\Models;

use App\Support\Riasec;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Satu sesi tes milik calon mahasiswa: biodata, nilai rapor, jawaban RIASEC,
 * dan hasil rekomendasi CoCoSo.
 */
class Assessment extends Model
{
    protected $table = 'assessments';

    protected $fillable = [
        'user_id', 'period_id', 'code',
        'full_name', 'gender', 'school_name', 'school_major', 'graduation_year', 'phone',
        'math_score', 'physics_score', 'chemistry_score', 'biology_score', 'indonesian_score', 'english_score',
        'score_r', 'score_i', 'score_a', 'score_s', 'score_e', 'score_c',
        'percent_r', 'percent_i', 'percent_a', 'percent_s', 'percent_e', 'percent_c',
        'holland_code', 'dominant_type',
        'primary_program_id', 'recommended_program_id', 'recommended_k_value', 'recommended_k_normal',
        'matches_preference', 'threshold_used', 'threshold_mode_used', 'lambda_used', 'weights_snapshot',
        'status', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'math_score' => 'float',
            'physics_score' => 'float',
            'chemistry_score' => 'float',
            'biology_score' => 'float',
            'indonesian_score' => 'float',
            'english_score' => 'float',
            'score_r' => 'integer',
            'score_i' => 'integer',
            'score_a' => 'integer',
            'score_s' => 'integer',
            'score_e' => 'integer',
            'score_c' => 'integer',
            'percent_r' => 'float',
            'percent_i' => 'float',
            'percent_a' => 'float',
            'percent_s' => 'float',
            'percent_e' => 'float',
            'percent_c' => 'float',
            'recommended_k_value' => 'float',
            'recommended_k_normal' => 'float',
            'matches_preference' => 'boolean',
            'threshold_used' => 'float',
            'lambda_used' => 'float',
            'weights_snapshot' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Assessment $assessment) {
            $assessment->code ??= 'TES-'.strtoupper(Str::random(8));
        });
    }

    /** @return BelongsTo<User, Assessment> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Period, Assessment> */
    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    /** @return HasMany<AssessmentPriority> */
    public function priorities(): HasMany
    {
        return $this->hasMany(AssessmentPriority::class)->orderBy('priority_order');
    }

    /** @return HasMany<AssessmentAnswer> */
    public function answers(): HasMany
    {
        return $this->hasMany(AssessmentAnswer::class);
    }

    /** @return HasMany<AssessmentResult> */
    public function results(): HasMany
    {
        return $this->hasMany(AssessmentResult::class)->orderBy('ranking');
    }

    /** @return BelongsTo<StudyProgram, Assessment> */
    public function primaryProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class, 'primary_program_id');
    }

    /** @return BelongsTo<StudyProgram, Assessment> */
    public function recommendedProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class, 'recommended_program_id');
    }

    /** @param  Builder<Assessment>  $query */
    public function scopeCompleted(Builder $query): void
    {
        $query->where('status', 'completed');
    }

    /**
     * Nilai rapor dikunci dengan `criteria.subject`.
     *
     * @return array<string, float>
     */
    public function subjectScores(): array
    {
        $scores = [];
        foreach (array_keys(Riasec::SUBJECTS) as $subject) {
            $scores[$subject] = (float) $this->{$subject.'_score'};
        }

        return $scores;
    }

    /**
     * Skor mentah Likert per dimensi RIASEC.
     *
     * @return array<string, int>
     */
    public function riasecScores(): array
    {
        $scores = [];
        foreach (Riasec::DIMENSIONS as $dimension) {
            $scores[$dimension] = (int) $this->{'score_'.strtolower($dimension)};
        }

        return $scores;
    }

    /**
     * Persentase RIASEC (0-100) per dimensi — sumber data bar chart.
     *
     * @return array<string, float>
     */
    public function riasecPercentages(): array
    {
        $percentages = [];
        foreach (Riasec::DIMENSIONS as $dimension) {
            $percentages[$dimension] = (float) $this->{'percent_'.strtolower($dimension)};
        }

        return $percentages;
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
