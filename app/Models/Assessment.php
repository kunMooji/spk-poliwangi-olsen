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
        'rapor_average',
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
            'rapor_average' => 'float',
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

    /**
     * Rerata rapor per semester — komponen pertama SNBP.
     *
     * @return HasMany<AssessmentRaporSemester>
     */
    public function raporSemesters(): HasMany
    {
        return $this->hasMany(AssessmentRaporSemester::class)->orderBy('semester');
    }

    /**
     * Nilai pada mapel pendukung — komponen kedua SNBP.
     *
     * @return HasMany<AssessmentSubjectScore>
     */
    public function subjectScores(): HasMany
    {
        return $this->hasMany(AssessmentSubjectScore::class);
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
     * Peta nilai mapel pendukung: [subject_id => nilai].
     *
     * Mapel yang dikosongkan responden tetap muncul dengan nilai null, bukan
     * dibuang, supaya pemanggil dapat membedakan "tidak menempuh mapel ini"
     * dari "mapel tidak ditanyakan".
     *
     * @return array<int, float|null>
     */
    public function subjectScoreMap(): array
    {
        return $this->subjectScores
            ->mapWithKeys(fn (AssessmentSubjectScore $row) => [
                $row->subject_id => $row->score === null ? null : (float) $row->score,
            ])
            ->all();
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
