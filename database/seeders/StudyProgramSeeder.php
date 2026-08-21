<?php

namespace Database\Seeders;

use App\Models\StudyProgram;
use App\Models\Subject;
use Illuminate\Database\Seeder;

/**
 * Alternatif keputusan: 19 program studi Politeknik Negeri Banyuwangi,
 * dikelompokkan menurut lima jurusan yang menaunginya.
 *
 * Nama dan jurusan mengikuti data resmi. Seluruh prodi berjenjang Sarjana
 * Terapan (D4) kecuali Teknik Sipil yang juga membuka D3; kata "Sarjana Terapan"
 * tidak ikut disimpan pada `name` karena jenjang sudah punya kolom sendiri.
 *
 * PERHATIAN — profil RIASEC dan angka tracer study di bawah ini masih DATA
 * CONTOH, bukan data resmi Poliwangi. Keduanya langsung memengaruhi hasil
 * rekomendasi (C3 dan C5), sehingga harus diganti dengan data sebenarnya lewat
 * menu Program Studi dan Tracer Study sebelum sistem dipakai mengambil
 * kesimpulan penelitian.
 *
 * Bergantung pada SubjectSeeder — mapel pendukung dirujuk lewat kodenya.
 */
class StudyProgramSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            // — Jurusan Bisnis dan Informatika —
            [
                'code' => 'TRPL-D4',
                'name' => 'Teknologi Rekayasa Perangkat Lunak',
                'level' => 'D4',
                'department' => 'Bisnis dan Informatika',
                'description' => 'Berfokus pada rekayasa perangkat lunak, pemrograman, basis data, dan pengembangan aplikasi berskala industri.',
                'support' => ['matematika', 'informatika', 'rekayasa-perangkat-lunak'],
                'riasec' => [60, 95, 55, 30, 45, 80],
                'alumni' => 180, 'employed' => 160, 'year' => 2025,
            ],
            [
                'code' => 'BD-D4',
                'name' => 'Bisnis Digital',
                'level' => 'D4',
                'department' => 'Bisnis dan Informatika',
                'description' => 'Pengelolaan bisnis berbasis teknologi digital, pemasaran daring, dan analisis data bisnis.',
                'support' => ['matematika', 'ekonomi-akuntansi', 'bisnis-digital'],
                'riasec' => [30, 65, 68, 60, 95, 72],
                'alumni' => 90, 'employed' => 75, 'year' => 2025,
            ],
            [
                'code' => 'TRK-D4',
                'name' => 'Teknologi Rekayasa Komputer',
                'level' => 'D4',
                'department' => 'Bisnis dan Informatika',
                'description' => 'Perancangan perangkat keras, jaringan komputer, sistem tertanam, dan integrasi perangkat cerdas.',
                'support' => ['matematika', 'fisika', 'teknik-komputer-dan-jaringan', 'teknik-mekatronika'],
                'riasec' => [80, 90, 40, 25, 45, 75],
                'alumni' => 120, 'employed' => 102, 'year' => 2025,
            ],

            // — Jurusan Teknik Sipil —
            [
                'code' => 'TS-D3',
                'name' => 'Teknik Sipil',
                'level' => 'D3',
                'department' => 'Teknik Sipil',
                'description' => 'Menyiapkan pelaksana lapangan dan juru gambar konstruksi yang menguasai pekerjaan teknik sipil terapan.',
                'support' => ['matematika', 'fisika', 'teknik-konstruksi-dan-perumahan'],
                'riasec' => [92, 65, 40, 35, 45, 68],
                'alumni' => 140, 'employed' => 115, 'year' => 2025,
            ],
            [
                'code' => 'TRKJJ-D4',
                'name' => 'Teknologi Rekayasa Konstruksi Jalan dan Jembatan',
                'level' => 'D4',
                'department' => 'Teknik Sipil',
                'description' => 'Perancangan dan pelaksanaan konstruksi jalan raya, jembatan, serta prasarana transportasi darat.',
                'support' => ['matematika', 'fisika', 'teknik-konstruksi-dan-perumahan'],
                'riasec' => [95, 75, 40, 30, 50, 70],
                'alumni' => 110, 'employed' => 95, 'year' => 2025,
            ],
            [
                'code' => 'TRKBG-D4',
                'name' => 'Teknologi Rekayasa Konstruksi Bangunan Gedung',
                'level' => 'D4',
                'department' => 'Teknik Sipil',
                'description' => 'Perancangan struktur, pelaksanaan, dan perawatan konstruksi bangunan gedung bertingkat.',
                'support' => ['matematika', 'fisika', 'konstruksi-gedung-dan-sanitasi'],
                'riasec' => [93, 74, 50, 32, 50, 72],
                'alumni' => 105, 'employed' => 89, 'year' => 2025,
            ],
            [
                'code' => 'MK-D4',
                'name' => 'Manajemen Konstruksi',
                'level' => 'D4',
                'department' => 'Teknik Sipil',
                'description' => 'Perencanaan biaya, penjadwalan, pengendalian mutu, dan manajemen proyek konstruksi.',
                'support' => ['matematika', 'ekonomi-akuntansi', 'teknik-konstruksi-dan-perumahan'],
                'riasec' => [70, 70, 35, 50, 85, 82],
                'alumni' => 95, 'employed' => 80, 'year' => 2025,
            ],

            // — Jurusan Teknik Mesin —
            [
                'code' => 'TRM-D4',
                'name' => 'Teknologi Rekayasa Manufaktur',
                'level' => 'D4',
                'department' => 'Teknik Mesin',
                'description' => 'Proses produksi manufaktur modern, permesinan CNC, dan pengendalian mutu produk.',
                'support' => ['matematika', 'fisika', 'teknik-pemesinan'],
                'riasec' => [93, 80, 40, 25, 45, 65],
                'alumni' => 120, 'employed' => 103, 'year' => 2025,
            ],
            [
                'code' => 'TMK-D4',
                'name' => 'Teknik Manufaktur Kapal',
                'level' => 'D4',
                'department' => 'Teknik Mesin',
                'description' => 'Fabrikasi, perakitan, dan perawatan konstruksi kapal serta bangunan lepas pantai.',
                'support' => ['matematika', 'fisika', 'konstruksi-kapal-baja', 'teknika-kapal-penangkapan-ikan'],
                'riasec' => [95, 72, 40, 25, 40, 62],
                'alumni' => 85, 'employed' => 69, 'year' => 2025,
            ],
            [
                'code' => 'TRO-D4',
                'name' => 'Teknologi Rekayasa Otomotif',
                'level' => 'D4',
                'department' => 'Teknik Mesin',
                'description' => 'Perawatan, diagnosis, dan rekayasa sistem kendaraan bermotor beserta teknologi elektrifikasinya.',
                'support' => ['matematika', 'fisika', 'teknik-kendaraan-ringan'],
                'riasec' => [96, 76, 38, 28, 45, 62],
                'alumni' => 130, 'employed' => 113, 'year' => 2025,
            ],

            // — Jurusan Pariwisata —
            [
                'code' => 'MBP-D4',
                'name' => 'Manajemen Bisnis Pariwisata',
                'level' => 'D4',
                'department' => 'Pariwisata',
                'description' => 'Pengelolaan usaha jasa pariwisata, perhotelan, dan penyelenggaraan acara.',
                'support' => ['ekonomi-akuntansi', 'bahasa-inggris', 'usaha-layanan-wisata', 'bisnis-retail'],
                'riasec' => [30, 45, 70, 85, 92, 65],
                'alumni' => 145, 'employed' => 122, 'year' => 2025,
            ],
            [
                'code' => 'DP-D4',
                'name' => 'Destinasi Pariwisata',
                'level' => 'D4',
                'department' => 'Pariwisata',
                'description' => 'Perencanaan, pengembangan, dan pemanduan destinasi wisata berbasis potensi daerah.',
                'support' => ['geografi', 'bahasa-inggris', 'usaha-layanan-wisata'],
                'riasec' => [45, 50, 80, 88, 80, 50],
                'alumni' => 100, 'employed' => 79, 'year' => 2025,
            ],
            [
                'code' => 'PPH-D4',
                'name' => 'Pengelolaan Perhotelan',
                'level' => 'D4',
                'department' => 'Pariwisata',
                'description' => 'Operasional dan pengelolaan hotel, mulai dari kantor depan, tata graha, hingga tata boga.',
                'support' => ['bahasa-inggris', 'ekonomi-akuntansi', 'perhotelan'],
                'riasec' => [40, 40, 65, 90, 85, 70],
                'alumni' => 115, 'employed' => 98, 'year' => 2025,
            ],

            // — Jurusan Pertanian —
            [
                'code' => 'AGB-D4',
                'name' => 'Agribisnis',
                'level' => 'D4',
                'department' => 'Pertanian',
                'description' => 'Pengelolaan usaha pertanian mulai dari produksi, pascapanen, hingga pemasaran hasil pertanian.',
                'support' => ['biologi', 'ekonomi-akuntansi', 'agribisnis-tanaman-pangan-dan-hortikultura', 'usaha-pertanian-terpadu', 'bisnis-retail'],
                'riasec' => [78, 70, 35, 60, 75, 55],
                'alumni' => 110, 'employed' => 88, 'year' => 2025,
            ],
            [
                'code' => 'TPHT-D4',
                'name' => 'Teknologi Pengolahan Hasil Ternak',
                'level' => 'D4',
                'department' => 'Pertanian',
                'description' => 'Teknologi pengolahan, pengawetan, dan pengujian mutu produk hasil ternak.',
                'support' => ['biologi', 'kimia', 'agribisnis-pengolahan-hasil-pertanian', 'agribisnis-ternak-ruminansia'],
                'riasec' => [82, 75, 30, 55, 55, 60],
                'alumni' => 95, 'employed' => 74, 'year' => 2025,
            ],
            [
                'code' => 'PPA-D4',
                'name' => 'Pengembangan Produk Agroindustri',
                'level' => 'D4',
                'department' => 'Pertanian',
                'description' => 'Pengembangan produk olahan hasil pertanian, rekayasa proses, dan pengendalian mutu agroindustri.',
                'support' => ['biologi', 'kimia', 'agribisnis-pengolahan-hasil-pertanian', 'agribisnis-pengolahan-hasil-perikanan', 'kimia-analisis'],
                'riasec' => [80, 82, 45, 50, 60, 62],
                'alumni' => 90, 'employed' => 72, 'year' => 2025,
            ],
            [
                'code' => 'TBP-D4',
                'name' => 'Teknologi Budi Daya Perikanan',
                'level' => 'D4',
                'department' => 'Pertanian',
                'description' => 'Teknologi akuakultur: pembenihan, pembesaran, pengelolaan kualitas air, dan kesehatan ikan.',
                'support' => ['biologi', 'kimia', 'agribisnis-perikanan-air-tawar', 'agribisnis-perikanan-payau-dan-laut', 'nautika-kapal-penangkapan-ikan', 'teknika-kapal-penangkapan-ikan'],
                'riasec' => [85, 80, 30, 50, 55, 58],
                'alumni' => 100, 'employed' => 78, 'year' => 2025,
            ],
            [
                'code' => 'TPTP-D4',
                'name' => 'Teknologi Produksi Tanaman Pangan',
                'level' => 'D4',
                'department' => 'Pertanian',
                'description' => 'Budi daya tanaman pangan, pemuliaan benih, dan penerapan teknologi produksi pertanian.',
                'support' => ['biologi', 'kimia', 'agribisnis-tanaman-pangan-dan-hortikultura', 'agribisnis-perbenihan-tanaman'],
                'riasec' => [86, 78, 30, 52, 55, 58],
                'alumni' => 105, 'employed' => 82, 'year' => 2025,
            ],
            [
                'code' => 'TPT-D4',
                'name' => 'Teknologi Produksi Ternak',
                'level' => 'D4',
                'department' => 'Pertanian',
                'description' => 'Pemeliharaan, pembibitan, dan pengelolaan usaha peternakan secara terpadu.',
                'support' => ['biologi', 'kimia', 'agribisnis-ternak-ruminansia', 'agribisnis-ternak-unggas'],
                'riasec' => [87, 76, 28, 55, 58, 56],
                'alumni' => 92, 'employed' => 71, 'year' => 2025,
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
