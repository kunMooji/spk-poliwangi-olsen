<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Jejak perubahan data master.
 *
 * Hanya ditulis, tidak pernah diubah — catatan yang dapat disunting tidak lagi
 * berguna sebagai bukti telusur.
 */
class ActivityLog extends Model
{
    protected $table = 'activity_logs';

    protected $fillable = [
        'user_id', 'user_name', 'action',
        'subject_type', 'subject_id', 'subject_label', 'changes',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
        ];
    }

    /** Nama Indonesia untuk tiap jenis data master yang dicatat. */
    public const SUBJECT_LABELS = [
        Criteria::class => 'Kriteria',
        Setting::class => 'Pengaturan',
        StudyProgram::class => 'Program Studi',
        RiasecQuestion::class => 'Pernyataan RIASEC',
        Period::class => 'Gelombang',
        User::class => 'Akun Pengguna',
    ];

    public const ACTION_LABELS = [
        'created' => 'Ditambahkan',
        'updated' => 'Diubah',
        'deleted' => 'Dihapus',
    ];

    /** @return BelongsTo<User, ActivityLog> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getSubjectLabelNameAttribute(): string
    {
        return self::SUBJECT_LABELS[$this->subject_type] ?? class_basename($this->subject_type);
    }

    public function getActionLabelAttribute(): string
    {
        return self::ACTION_LABELS[$this->action] ?? $this->action;
    }

    /** @param  Builder<ActivityLog>  $query */
    public function scopeLatestFirst(Builder $query): void
    {
        $query->orderByDesc('created_at')->orderByDesc('id');
    }
}
