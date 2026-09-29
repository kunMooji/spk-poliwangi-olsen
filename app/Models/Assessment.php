<?php

namespace App\Models;

use App\Models\Setting;
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
        'full_name', 'gender', 'school_name', 'education_level', 'school_major', 'graduation_year', 'phone',
        'rapor_average',
        'score_r', 'score_i', 'score_a', 'score_s', 'score_e', 'score_c',
        'percent_r', 'percent_i', 'percent_a', 'percent_s', 'percent_e', 'percent_c',
        'holland_code', 'dominant_type',
        'primary_program_id', 'recommended_program_id', 'recommended_k_value', 'recommended_k_normal',
        'matches_preference', 'margin_to_runner_up', 'margin_threshold_used', 'lambda_used', 'weights_snapshot',
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
            'margin_to_runner_up' => 'float',
            'margin_threshold_used' => 'float',
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

    /**
     * Kelompok prodi yang skornya terlalu berdekatan untuk dianggap berbeda.
     *
     * Dua prodi bersebelahan yang selisih `k_normal`-nya di bawah ambang
     * `tie_threshold` dimasukkan ke satu kelompok, dan kelompok itu bisa
     * memanjang: bila #9 dekat #10 dan #10 dekat #11, ketiganya satu kelompok.
     *
     * Ini penanda, bukan pengubah peringkat. Urutannya tetap apa adanya dari
     * CoCoSo; yang ditambahkan hanya keterangan bahwa selisihnya berada di bawah
     * presisi yang bisa dipertanggungjawabkan — pada data nyata ada pasangan yang
     * hanya berselisih 0,003 poin, dan urutan di antaranya ditentukan pembulatan.
     *
     * @return array<int, array{from: int, to: int, size: int}> [studyProgramId => rentang peringkat sekelompok]
     */
    public function tieGroups(): array
    {
        $threshold = (float) Setting::get('tie_threshold');
        $sorted = $this->results->sortBy('ranking')->values();

        if ($sorted->count() < 2 || $threshold <= 0.0) {
            return [];
        }

        // Pengelompokan berantai: sebuah prodi masuk kelompok berjalan selama
        // jaraknya ke prodi tepat di atasnya masih di bawah ambang.
        $groups = [];
        $current = [$sorted[0]];

        for ($i = 1; $i < $sorted->count(); $i++) {
            $previous = $sorted[$i - 1];
            $result = $sorted[$i];

            if ((float) $previous->k_normal - (float) $result->k_normal < $threshold) {
                $current[] = $result;

                continue;
            }

            $groups[] = $current;
            $current = [$result];
        }

        $groups[] = $current;

        $map = [];

        foreach ($groups as $group) {
            if (count($group) < 2) {
                continue;
            }

            $ranks = array_map(fn ($result) => (int) $result->ranking, $group);
            $meta = ['from' => min($ranks), 'to' => max($ranks), 'size' => count($group)];

            foreach ($group as $result) {
                $map[$result->study_program_id] = $meta;
            }
        }

        return $map;
    }

    /**
     * Apakah jarak prodi peringkat 1 terhadap peringkat 2 cukup lebar untuk
     * disebut meyakinkan?
     *
     * Mengembalikan null bila sesi ini dihitung sebelum margin dicatat, sehingga
     * pemanggil dapat membedakan "tipis" dari "tidak diketahui".
     */
    public function hasConfidentRecommendation(): ?bool
    {
        if ($this->margin_to_runner_up === null || $this->margin_threshold_used === null) {
            return null;
        }

        return $this->margin_to_runner_up >= $this->margin_threshold_used;
    }
}
