<?php

namespace App\Models\Concerns;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Mencatat perubahan data master ke `activity_logs`.
 *
 * Dipasang di tingkat model, bukan controller, supaya perubahan lewat jalur
 * mana pun tetap terekam — termasuk `Setting::set()` yang dipanggil dari luar
 * controller.
 *
 * Model pemakai boleh menimpa `activityAttributes()` dan `activityLabel()`.
 */
trait RecordsActivity
{
    protected static function bootRecordsActivity(): void
    {
        static::created(fn (Model $model) => $model->recordActivity('created'));
        static::updated(fn (Model $model) => $model->recordActivity('updated'));
        static::deleted(fn (Model $model) => $model->recordActivity('deleted'));
    }

    /**
     * Kolom yang layak dicatat. Timestamp tidak termasuk — ia sudah terwakili
     * oleh `activity_logs.created_at`.
     *
     * @return array<int, string>
     */
    protected function activityAttributes(): array
    {
        return $this->getFillable();
    }

    /** Penanda yang tetap terbaca setelah datanya dihapus. */
    protected function activityLabel(): string
    {
        return (string) ($this->getAttribute('name')
            ?? $this->getAttribute('code')
            ?? $this->getAttribute('key')
            ?? "#{$this->getKey()}");
    }

    public function recordActivity(string $action): void
    {
        // Seeder dan perintah konsol berjalan tanpa pengguna. Mencatatnya hanya
        // akan mengaburkan log yang tujuannya menelusuri tindakan admin.
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $changes = $this->activityChanges($action);

        // Penyimpanan yang tidak mengubah nilai apa pun tidak perlu dicatat.
        if ($action === 'updated' && $changes === []) {
            return;
        }

        ActivityLog::query()->create([
            'user_id' => $user->getKey(),
            'user_name' => $user->name,
            'action' => $action,
            'subject_type' => static::class,
            'subject_id' => $this->getKey(),
            'subject_label' => $this->activityLabel(),
            'changes' => $changes,
        ]);
    }

    /**
     * Selisih nilai dalam bentuk { kolom: { from: lama, to: baru } }.
     *
     * @return array<string, array{from: mixed, to: mixed}>
     */
    protected function activityChanges(string $action): array
    {
        $tracked = $this->activityAttributes();
        $changes = [];

        if ($action === 'updated') {
            foreach ($this->getChanges() as $field => $new) {
                if (! in_array($field, $tracked, true)) {
                    continue;
                }

                $changes[$field] = [
                    'from' => $this->getOriginal($field),
                    'to' => $new,
                ];
            }

            return $changes;
        }

        foreach ($tracked as $field) {
            $value = $this->getAttribute($field);

            if ($value === null) {
                continue;
            }

            $changes[$field] = $action === 'created'
                ? ['from' => null, 'to' => $value]
                : ['from' => $value, 'to' => null];
        }

        return $changes;
    }
}
