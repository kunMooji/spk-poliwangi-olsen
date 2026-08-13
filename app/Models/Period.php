<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Gelombang penerimaan mahasiswa baru.
 *
 * Sesi tes menyalin gelombang yang aktif saat tes dibuat. Setelah tersimpan,
 * penandaan itu tidak ikut berubah meski gelombang aktif berganti.
 */
class Period extends Model
{
    use RecordsActivity;

    protected $table = 'periods';

    protected $fillable = [
        'name', 'academic_year', 'starts_at', 'ends_at', 'description', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<Assessment> */
    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    /** @param  Builder<Period>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Gelombang yang sedang berjalan.
     *
     * Dipanggil saat sesi tes dibuat. Mengembalikan null bila admin belum
     * membuka gelombang mana pun — tes tetap dapat dikerjakan, hanya saja
     * tidak tertandai gelombang.
     */
    public static function current(): ?self
    {
        return static::query()->active()->latest('starts_at')->first();
    }

    /** Rentang tanggal untuk ditampilkan; gelombang tanpa tanggal tetap sah. */
    public function getRangeLabelAttribute(): string
    {
        if (! $this->starts_at && ! $this->ends_at) {
            return 'Tanpa batas tanggal';
        }

        $start = $this->starts_at?->translatedFormat('d M Y') ?? '—';
        $end = $this->ends_at?->translatedFormat('d M Y') ?? 'seterusnya';

        return "{$start} s.d. {$end}";
    }
}
