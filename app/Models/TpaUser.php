<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Akun area latihan SKD (/logintpa), dipakai oleh guard "tpa".
 */
class TpaUser extends Authenticatable
{
    protected $fillable = ['username', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }

    public function tryouts(): HasMany
    {
        return $this->hasMany(TpaTryout::class);
    }
}
