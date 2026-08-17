<?php

namespace Database\Seeders;

use App\Models\StudyProgram;
use App\Models\Subject;
use Illuminate\Database\Seeder;

/**
 * Data awal alternatif program studi.
 *
 * Mata pelajaran pendukung, profil RIASEC, dan tracer study di bawah ini adalah
 * data contoh yang siap dipakai untuk pengujian. Seluruhnya dapat disunting
 * admin lewat menu Program Studi dan Tracer Study.
 *
 * Bergantung pada SubjectSeeder — mapel pendukung dirujuk lewat kodenya.
 */
class StudyProgramSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'code' => 'TRPL-D4',
                'name' => 'Teknologi Rekayasa Perangkat Lunak',
                'level' => 'D4',
                'department' => 'Teknologi Informasi',
                'description' => 'Berfokus pada rekayasa perangkat lunak, pemrograman, basis data, dan pengembangan aplikasi berskala industri.',
                'support' => ['matematika', 'informatika'],
                'riasec' => [60, 95, 55, 30, 45, 80],
                'alumni' => 180, 'employed' => 160, 'year' => 2025,
            ],
            [
                'code' => 'TI-D3',
                'name' => 'Teknik Informatika',
                'level' => 'D3',
                'department' => 'Teknologi Informasi',
                'description' => 'Menyiapkan tenaga terampil di bidang pemrograman, jaringan komputer, dan pengelolaan sistem informasi.',
                'support' => ['matematika', 'informatika'],
                'riasec' => [65, 88, 50, 30, 40, 78],
                'alumni' => 150, 'employed' => 128, 'year' => 2025,
            ],
            [
                'code' => 'TS-D4',
                'name' => 'Teknik Sipil',
                'level' => 'D4',
                'department' => 'Teknik Sipil',
                'description' => 'Perancangan dan pelaksanaan konstruksi bangunan gedung, jalan, jembatan, serta bangunan air.',
                'support' => ['matematika', 'fisika'],
                'riasec' => [95, 75, 45, 35, 50, 70],
                'alumni' => 165, 'employed' => 144, 'year' => 2025,
            ],
            [
                'code' => 'TS-D3',
                'name' => 'Teknik Sipil',
                'level' => 'D3',
                'department' => 'Teknik Sipil',
                'description' => 'Menyiapkan pelaksana lapangan dan juru gambar konstruksi yang menguasai pekerjaan teknik sipil terapan.',
                'support' => ['matematika', 'fisika'],
                'riasec' => [92, 65, 40, 35, 45, 68],
                'alumni' => 140, 'employed' => 115, 'year' => 2025,
            ],
            [
                'code' => 'TM-D4',
                'name' => 'Teknik Mesin',
                'level' => 'D4',
                'department' => 'Teknik Mesin',
                'description' => 'Perancangan, perawatan, dan pengoperasian sistem permesinan serta konversi energi.',
                'support' => ['matematika', 'fisika'],
                'riasec' => [96, 78, 35, 25, 40, 60],
                'alumni' => 170, 'employed' => 150, 'year' => 2025,
            ],
            [
                'code' => 'TRM-D4',
                'name' => 'Teknologi Rekayasa Manufaktur',
                'level' => 'D4',
                'department' => 'Teknik Mesin',
                'description' => 'Proses produksi manufaktur modern, permesinan CNC, dan pengendalian mutu produk.',
                'support' => ['matematika', 'fisika'],
                'riasec' => [93, 80, 40, 25, 45, 65],
                'alumni' => 120, 'employed' => 103, 'year' => 2025,
            ],
            [
                'code' => 'TMK-D3',
                'name' => 'Teknik Manufaktur Kapal',
                'level' => 'D3',
                'department' => 'Teknik Mesin',
                'description' => 'Fabrikasi, perakitan, dan perawatan konstruksi kapal serta bangunan lepas pantai.',
                'support' => ['matematika', 'fisika'],
                'riasec' => [95, 72, 40, 25, 40, 62],
                'alumni' => 85, 'employed' => 69, 'year' => 2025,
            ],
            [
                'code' => 'AGB-D4',
                'name' => 'Agribisnis',
                'level' => 'D4',
                'department' => 'Pertanian',
                'description' => 'Pengelolaan usaha pertanian mulai dari produksi, pascapanen, hingga pemasaran hasil pertanian.',
                'support' => ['biologi', 'ekonomi-akuntansi'],
                'riasec' => [78, 70, 35, 60, 75, 55],
                'alumni' => 110, 'employed' => 88, 'year' => 2025,
            ],
            [
                'code' => 'TPHT-D4',
                'name' => 'Teknik Pengolahan Hasil Ternak',
                'level' => 'D4',
                'department' => 'Pertanian',
                'description' => 'Teknologi pengolahan, pengawetan, dan pengujian mutu produk hasil ternak.',
                'support' => ['biologi', 'kimia'],
                'riasec' => [82, 75, 30, 55, 55, 60],
                'alumni' => 95, 'employed' => 74, 'year' => 2025,
            ],
            [
                'code' => 'MBP-D4',
                'name' => 'Manajemen Bisnis Pariwisata',
                'level' => 'D4',
                'department' => 'Bisnis dan Pariwisata',
                'description' => 'Pengelolaan usaha jasa pariwisata, perhotelan, dan penyelenggaraan acara.',
                'support' => ['ekonomi-akuntansi', 'bahasa-inggris'],
                'riasec' => [30, 45, 70, 85, 92, 65],
                'alumni' => 145, 'employed' => 122, 'year' => 2025,
            ],
            [
                'code' => 'DP-D4',
                'name' => 'Destinasi Pariwisata',
                'level' => 'D4',
                'department' => 'Bisnis dan Pariwisata',
                'description' => 'Perencanaan, pengembangan, dan pemanduan destinasi wisata berbasis potensi daerah.',
                'support' => ['geografi', 'bahasa-inggris'],
                'riasec' => [45, 50, 80, 88, 80, 50],
                'alumni' => 100, 'employed' => 79, 'year' => 2025,
            ],
            [
                'code' => 'BD-D4',
                'name' => 'Bisnis Digital',
                'level' => 'D4',
                'department' => 'Bisnis dan Pariwisata',
                'description' => 'Pengelolaan bisnis berbasis teknologi digital, pemasaran daring, dan analisis data bisnis.',
                'support' => ['ekonomi-akuntansi', 'informatika'],
                'riasec' => [30, 65, 68, 60, 95, 72],
                'alumni' => 90, 'employed' => 75, 'year' => 2025,
            ],
        ];

        $subjectIds = Subject::query()->pluck('id', 'code');

        foreach ($rows as $row) {
            [$r, $i, $a, $s, $e, $c] = $row['riasec'];

            $program = StudyProgram::query()->updateOrCreate(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'level' => $row['level'],
                    'department' => $row['department'],
                    'description' => $row['description'],
                    'riasec_r' => $r,
                    'riasec_i' => $i,
                    'riasec_a' => $a,
                    'riasec_s' => $s,
                    'riasec_e' => $e,
                    'riasec_c' => $c,
                    'alumni_count' => $row['alumni'],
                    'employed_count' => $row['employed'],
                    'employment_rate' => round($row['employed'] / $row['alumni'], 3),
                    'tracer_year' => $row['year'],
                    'tracer_updated_at' => now(),
                    'is_active' => true,
                ]
            );

            // Mapel pendukung dipetakan lewat kode, bukan id, supaya seeder tetap
            // benar meskipun urutan penyisipan master mapel berubah.
            $sync = [];
            foreach ($row['support'] as $position => $code) {
                if ($subjectIds->has($code)) {
                    $sync[$subjectIds[$code]] = ['position' => $position + 1];
                }
            }

            $program->supportSubjects()->sync($sync);
        }
    }
}
