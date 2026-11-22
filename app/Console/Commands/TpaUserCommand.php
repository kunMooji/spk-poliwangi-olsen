<?php

namespace App\Console\Commands;

use App\Models\TpaUser;
use Illuminate\Console\Command;

/**
 * Membuat atau mengganti kata sandi akun area latihan SKD (/logintpa).
 * Kata sandi diketik saat perintah dijalankan, jadi tidak pernah tersimpan
 * di repositori (repo GitHub bersifat publik).
 */
class TpaUserCommand extends Command
{
    protected $signature = 'tpa:user {username : Nama pengguna untuk login /logintpa}';

    protected $description = 'Buat akun latihan SKD atau ganti kata sandinya';

    public function handle(): int
    {
        $username = strtolower(trim($this->argument('username')));

        if (! preg_match('/^[a-z0-9_.]{3,30}$/', $username)) {
            $this->error('Username hanya boleh huruf kecil, angka, titik, atau garis bawah (3-30 karakter).');

            return self::FAILURE;
        }

        $password = $this->secret('Kata sandi');

        if (! is_string($password) || strlen($password) < 6) {
            $this->error('Kata sandi minimal 6 karakter.');

            return self::FAILURE;
        }

        if ($this->secret('Ulangi kata sandi') !== $password) {
            $this->error('Kata sandi tidak sama.');

            return self::FAILURE;
        }

        $user = TpaUser::updateOrCreate(['username' => $username], ['password' => $password]);

        $this->info(($user->wasRecentlyCreated ? 'Akun dibuat: ' : 'Kata sandi diperbarui: ').$username);

        return self::SUCCESS;
    }
}
