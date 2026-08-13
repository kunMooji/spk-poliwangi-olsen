<?php

namespace Database\Seeders;

use App\Models\Period;
use Illuminate\Database\Seeder;

class PeriodSeeder extends Seeder
{
    /**
     * Satu gelombang aktif sebagai titik mulai.
     *
     * Tanpa ini sesi tes tidak tertandai gelombang mana pun dan penyaringan
     * rekap per gelombang tidak menghasilkan apa-apa.
     */
    public function run(): void
    {
        $year = (int) now()->year;
        $academicYear = now()->month >= 7
            ? $year.'/'.($year + 1)
            : ($year - 1).'/'.$year;

        Period::query()->updateOrCreate(
            ['name' => 'Gelombang 1', 'academic_year' => $academicYear],
            [
                'starts_at' => now()->startOfYear(),
                'ends_at' => null,
                'description' => 'Gelombang bawaan yang dibuat otomatis saat pemasangan sistem.',
                'is_active' => true,
            ]
        );
    }
}
