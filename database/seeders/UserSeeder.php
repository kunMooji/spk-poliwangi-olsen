<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@poliwangi.ac.id'],
            [
                'name' => 'Administrator SPK',
                'password' => 'password',
                'role' => User::ROLE_ADMIN,
                'email_verified_at' => now(),
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'dewiratna@gmail.com'],
            [
                'name' => 'Dewi Ratna',
                'password' => 'password',
                'role' => User::ROLE_MAHASISWA,
                'email_verified_at' => now(),
            ]
        );
    }
}
