<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use App\Support\Riasec;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Alternatif keputusan pada perhitungan CoCoSo.
 */
class StudyProgram extends Model
{
    use HasFactory, RecordsActivity;

    protected $table = 'study_programs';

    /**
     * Situs resmi jurusan/prodi, dikunci pada `code` prodi. Prodi yang tidak
     * tercantum di sini (mis. Teknologi Rekayasa Otomotif, Pariwisata) memang
     * belum punya tautan, jadi tidak menampilkan aksi apa pun.
     */
    public const WEBSITES = [
        // Jurusan Bisnis dan Informatika — satu situs untuk ketiga prodi.
        'TRPL-D4' => 'https://jbi.poliwangi.ac.id/',
        'BD-D4' => 'https://jbi.poliwangi.ac.id/',
        'TRK-D4' => 'https://jbi.poliwangi.ac.id/',
        // Jurusan Teknik Sipil — satu situs untuk keempat prodi.
        'TS-D3' => 'https://sipil.poliwangi.ac.id/',
        'TRKJJ-D4' => 'https://sipil.poliwangi.ac.id/',
        'TRKBG-D4' => 'https://sipil.poliwangi.ac.id/',
        'MK-D4' => 'https://sipil.poliwangi.ac.id/',
        // Jurusan Teknik Mesin — hanya dua prodi yang punya situs.
        'TRM-D4' => 'https://trm.poliwangi.ac.id/',
        'TMK-D4' => 'https://tmk.poliwangi.ac.id/',
        // Jurusan Pertanian — hanya dua prodi yang punya situs.
        'AGB-D4' => 'https://agb.poliwangi.ac.id/',
        'TPHT-D4' => 'https://tpht.poliwangi.ac.id/',
    ];

    protected $fillable = [
        'code', 'name', 'level', 'department', 'description',
        'riasec_r', 'riasec_i', 'riasec_a', 'riasec_s', 'riasec_e', 'riasec_c',
        'employment_rate', 'alumni_count', 'employed_count', 'tracer_year', 'tracer_updated_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'riasec_r' => 'integer',
            'riasec_i' => 'integer',
            'riasec_a' => 'integer',
            'riasec_s' => 'integer',
            'riasec_e' => 'integer',
            'riasec_c' => 'integer',
            'employment_rate' => 'float',
            'alumni_count' => 'integer',
            'employed_count' => 'integer',
            'tracer_year' => 'integer',
            'tracer_updated_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<AssessmentResult> */
    public function results(): HasMany
    {
        return $this->hasMany(AssessmentResult::class);
    }

    /** @return HasMany<AssessmentPriority> */
    public function priorities(): HasMany
    {
        return $this->hasMany(AssessmentPriority::class);
    }

    /**
     * Mata pelajaran pendukung prodi — paling banyak dua, sesuai aturan SNBP.
     *
     * @return BelongsToMany<Subject>
     */
    public function supportSubjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'study_program_subjects')
            ->withPivot('position')
            ->withTimestamps()
            ->orderBy('study_program_subjects.position');
    }

    /** @param  Builder<StudyProgram>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Vektor profil RIASEC prodi dengan urutan baku R,I,A,S,E,C.
     *
     * @return array<string, float>
     */
    public function riasecVector(): array
    {
        $vector = [];
        foreach (Riasec::DIMENSIONS as $dimension) {
            $vector[$dimension] = (float) $this->{'riasec_'.strtolower($dimension)};
        }

        return $vector;
    }

    /** Tiga dimensi RIASEC tertinggi, mis. "ICR". */
    public function getHollandCodeAttribute(): string
    {
        return Riasec::hollandCode($this->riasecVector());
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->level.' '.$this->name);
    }

    /** Alamat situs prodi, atau null bila prodi ini belum punya tautan. */
    public function getWebsiteUrlAttribute(): ?string
    {
        return self::WEBSITES[$this->code] ?? null;
    }

    /** Persentase alumni terserap kerja, siap tampil. */
    public function getEmploymentPercentAttribute(): float
    {
        return round($this->employment_rate * 100, 2);
    }
}
