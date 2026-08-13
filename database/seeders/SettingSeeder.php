<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'key' => 'threshold',
                'value' => '70',
                'type' => 'float',
                'label' => 'Ambang Batas Kelayakan',
                'description' => 'Nilai minimum agar sebuah prodi ditandai layak dipertimbangkan. Bersifat penanda pada lembar hasil — tidak menentukan prodi mana yang direkomendasikan, karena rekomendasi mengikuti peringkat CoCoSo.',
            ],
            [
                'key' => 'threshold_mode',
                'value' => 'normal',
                'type' => 'string',
                'label' => 'Mode Pembanding Ambang Batas',
                'description' => '"normal" membandingkan terhadap K ternormalisasi (0-100), "raw" membandingkan terhadap nilai K asli.',
            ],
            [
                'key' => 'lambda',
                'value' => '0.5',
                'type' => 'float',
                'label' => 'Nilai Lambda (λ) Strategi Kompromi Kic',
                'description' => 'Rentang 0 sampai 1. Nilai 0.5 memberi bobot seimbang antara Si dan Pi.',
            ],
            [
                'key' => 'epsilon',
                'value' => '0.000001',
                'type' => 'float',
                'label' => 'Epsilon Pengaman Normalisasi',
                'description' => 'Batas bawah nilai ternormalisasi agar Pi tidak nol dan pembagian pada Kib tidak error.',
            ],
            [
                'key' => 'unselected_priority_score',
                'value' => '0',
                'type' => 'float',
                'label' => 'Skor C8 untuk Prodi yang Tidak Dipilih',
                'description' => 'Nilai minat yang diberikan pada prodi di luar daftar prioritas calon mahasiswa.',
            ],
            [
                'key' => 'likert_min',
                'value' => '1',
                'type' => 'integer',
                'label' => 'Skala Likert Minimum',
                'description' => 'Nilai terendah pada kuesioner RIASEC.',
            ],
            [
                'key' => 'likert_max',
                'value' => '5',
                'type' => 'integer',
                'label' => 'Skala Likert Maksimum',
                'description' => 'Nilai tertinggi pada kuesioner RIASEC.',
            ],
            [
                'key' => 'min_priorities',
                'value' => '3',
                'type' => 'integer',
                'label' => 'Jumlah Minimal Prioritas Prodi',
                'description' => 'Berapa prodi minimal yang wajib diurutkan calon mahasiswa.',
            ],
        ];

        foreach ($rows as $row) {
            Setting::query()->updateOrCreate(['key' => $row['key']], $row);
        }

        Setting::forgetCache();
    }
}
