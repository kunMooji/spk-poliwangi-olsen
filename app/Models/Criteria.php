<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Kriteria penilaian C1..C9 beserta bobot keputusan CoCoSo.
 */
class Criteria extends Model
{
    use RecordsActivity;

    protected $table = 'criteria';

    protected $fillable = [
        'code', 'name', 'weight', 'type', 'source',
        'unit', 'description', 'sort_order', 'is_active',
    ];

    /** Sumber nilai x_ij, dipakai DecisionMatrixBuilder. */
    public const SOURCES = [
        'rapor_average' => 'Rerata Rapor Seluruh Mapel',
        'support_subject' => 'Nilai Mapel Pendukung Prodi',
        'riasec' => 'Kesesuaian RIASEC',
        'priority' => 'Prioritas Minat',
        'tracer' => 'Tracer Study',
    ];

    /**
     * Sumber yang menyusun blok nilai rapor.
     *
     * SNBP mengatur komposisi 50:50 di antara kedua komponen ini saja; kriteria
     * RIASEC, prioritas, dan tracer study adalah tambahan sistem ini dan tidak
     * ikut diperhitungkan dalam rasio tersebut.
     */
    public const RAPOR_SOURCES = ['rapor_average', 'support_subject'];

    protected function casts(): array
    {
        return [
            'weight' => 'float',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @param  Builder<Criteria>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** @param  Builder<Criteria>  $query */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('code');
    }

    public function isBenefit(): bool
    {
        return $this->type === 'benefit';
    }

    public function getSourceLabelAttribute(): string
    {
        return self::SOURCES[$this->source] ?? $this->source;
    }

    /** Total bobot seluruh kriteria aktif — idealnya bernilai 1. */
    public static function totalActiveWeight(): float
    {
        return (float) static::query()->active()->sum('weight');
    }

    /**
     * Porsi komponen pertama SNBP terhadap seluruh blok rapor, dalam persen.
     *
     * SNBP mensyaratkan rerata seluruh mapel memegang paling sedikit 50% dan
     * mapel pendukung paling banyak 50%. Rasionya dihitung terhadap blok rapor
     * saja, bukan terhadap total seluruh kriteria.
     *
     * Mengembalikan null bila belum ada kriteria rapor aktif, sehingga pemanggil
     * dapat membedakan "belum diatur" dari "komposisinya nol persen".
     */
    public static function raporComponentShare(): ?float
    {
        // Dijumlahkan per sumber, bukan diambil satu baris, karena admin boleh
        // membuat lebih dari satu kriteria dengan sumber yang sama.
        $weights = static::query()->active()
            ->whereIn('source', self::RAPOR_SOURCES)
            ->groupBy('source')
            ->pluck(DB::raw('SUM(weight) as total'), 'source');

        $total = (float) $weights->sum();

        if ($total <= 0.0) {
            return null;
        }

        return (float) $weights->get('rapor_average', 0) / $total * 100;
    }

    protected function activityLabel(): string
    {
        return trim("{$this->code} — {$this->name}");
    }
}
