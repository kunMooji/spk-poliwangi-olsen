<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Parameter algoritma yang dapat diubah admin (threshold, lambda, epsilon, dll).
 *
 * @property string $key
 * @property string|null $value
 * @property string $type
 */
class Setting extends Model
{
    protected $table = 'settings';

    protected $fillable = ['key', 'value', 'type', 'label', 'description'];

    public const CACHE_KEY = 'settings.all';

    /** Nilai bawaan bila baris pengaturan belum ada di database. */
    public const DEFAULTS = [
        'threshold' => 70.0,
        'threshold_mode' => 'normal',
        'lambda' => 0.5,
        'epsilon' => 0.000001,
        'unselected_priority_score' => 0.0,
        'likert_min' => 1,
        'likert_max' => 5,
        'min_priorities' => 3,
    ];

    protected static function booted(): void
    {
        static::saved(fn () => static::forgetCache());
        static::deleted(fn () => static::forgetCache());
    }

    /**
     * Seluruh pengaturan sebagai map key => value (sudah di-cast), dengan cache.
     *
     * @return array<string, mixed>
     */
    public static function values(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $data = static::DEFAULTS;

            foreach (static::query()->get() as $row) {
                $data[$row->key] = $row->castedValue();
            }

            return $data;
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::values()[$key] ?? $default ?? (static::DEFAULTS[$key] ?? null);
    }

    public static function set(string $key, mixed $value): void
    {
        $stored = is_bool($value) ? ($value ? '1' : '0') : (string) $value;

        $row = static::query()->where('key', $key)->first();

        if ($row) {
            $row->update(['value' => $stored]);

            return;
        }

        static::query()->create([
            'key' => $key,
            'value' => $stored,
            'type' => match (true) {
                is_bool($value) => 'boolean',
                is_int($value) => 'integer',
                is_float($value) => 'float',
                default => 'string',
            },
            'label' => $key,
        ]);
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function castedValue(): mixed
    {
        return match ($this->type) {
            'integer' => (int) $this->value,
            'float' => (float) $this->value,
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            default => $this->value,
        };
    }
}
