<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Soal di bank latihan SKD. Level 1-4 = mudah, sedang, sulit, sangat sulit.
 */
class TpaBankQuestion extends Model
{
    public const LEVELS = [1 => 'Mudah', 2 => 'Sedang', 3 => 'Sulit', 4 => 'Sangat sulit'];

    protected $fillable = [
        'code', 'category', 'sub_topic', 'level', 'question', 'options', 'correct_key', 'hint', 'explanation',
    ];

    protected function casts(): array
    {
        return ['options' => 'array'];
    }

    /** Petakan slider kesulitan 10-100% ke level bank (selaras dengan rubrik di config/tpa.php). */
    public static function levelFor(int $difficulty): int
    {
        return match (true) {
            $difficulty <= 30 => 1,
            $difficulty <= 60 => 2,
            $difficulty <= 80 => 3,
            default => 4,
        };
    }
}
