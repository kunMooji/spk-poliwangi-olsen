<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasFactory, Notifiable, RecordsActivity;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_MAHASISWA = 'mahasiswa';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Nilai bawaan kolom tidak terbaca oleh instance yang baru dibuat — tanpa
     * ini `is_active` bernilai null sampai model dimuat ulang dari basis data,
     * dan akun yang baru mendaftar langsung dianggap nonaktif.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Kata sandi sengaja tidak ikut dicatat — jejak perubahan tidak boleh
     * menyimpan hash kredensial. Peristiwa reset dicatat terpisah oleh
     * controller sebagai tindakan, bukan sebagai selisih nilai.
     *
     * @return array<int, string>
     */
    protected function activityAttributes(): array
    {
        return ['name', 'email', 'role', 'is_active'];
    }

    /** @return HasMany<Assessment> */
    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar ? Storage::disk('public')->url($this->avatar) : null;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isMahasiswa(): bool
    {
        return $this->role === self::ROLE_MAHASISWA;
    }

    /** @param  Builder<User>  $query */
    public function scopeMahasiswa(Builder $query): void
    {
        $query->where('role', self::ROLE_MAHASISWA);
    }

    /** @param  Builder<User>  $query */
    public function scopeAdmin(Builder $query): void
    {
        $query->where('role', self::ROLE_ADMIN);
    }

    /** @param  Builder<User>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
