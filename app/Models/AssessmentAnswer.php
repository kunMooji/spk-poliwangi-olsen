<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Jawaban skala Likert 1-5 atas satu pernyataan kuesioner RIASEC.
 *
 * Kolom `dimension` sengaja diduplikasi dari pertanyaan agar akumulasi skor
 * per dimensi bisa dihitung tanpa join.
 */
class AssessmentAnswer extends Model
{
    protected $table = 'assessment_answers';

    protected $fillable = ['assessment_id', 'riasec_question_id', 'dimension', 'score'];

    protected function casts(): array
    {
        return ['score' => 'integer'];
    }

    /** @return BelongsTo<Assessment, AssessmentAnswer> */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    /** @return BelongsTo<RiasecQuestion, AssessmentAnswer> */
    public function question(): BelongsTo
    {
        return $this->belongsTo(RiasecQuestion::class, 'riasec_question_id');
    }
}
