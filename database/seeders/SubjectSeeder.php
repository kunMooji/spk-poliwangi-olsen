<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;

/**
 * Master mata pelajaran mengikuti struktur Kurikulum Merdeka.
 *
 * SMA berisi mapel umum yang diterima seluruh peserta didik, ditambah kelompok
 * pilihan IPA, IPS, dan Bahasa. Ketiganya bukan mata pelajaran melainkan nama
 * kelompok, sehingga muncul sebagai `group` dan bukan sebagai baris.
 *
 * SMK diisi pada tingkat konsentrasi keahlian, bukan rumpun. Di Kurikulum
 * Merdeka nama konsentrasi itulah yang tercantum di rapor sebagai Mata Pelajaran
 * Konsentrasi Keahlian, sedangkan rumpunnya terlalu umum untuk dijadikan mapel
 * pendukung sebuah program studi. Rumpun dipakai sebagai `group` agar admin
 * mudah menemukannya.
 *
 * Daftar ini titik awal, bukan batas: admin menambah sendiri mapel yang belum
 * tercakup lewat menu Mata Pelajaran.
 */
class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->rows() as $index => $row) {
            Subject::query()->updateOrCreate(
                ['code' => $row['code']],
                $row + [
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * @return array<int, array{code: string, name: string, education_level: string, group: string}>
     */
    private function rows(): array
    {
        return [
            ...$this->general(),
            ...$this->seniorHigh(),
            ...$this->vocational(),
        ];
    }

    /**
     * Mapel umum — ditempuh peserta didik SMA maupun SMK.
     *
     * @return array<int, array{code: string, name: string, education_level: string, group: string}>
     */
    private function general(): array
    {
        return $this->map('umum', 'Umum', [
            'Bahasa Indonesia',
            'Bahasa Inggris',
            'Matematika',
            'Pendidikan Pancasila',
            'PJOK',
        ]);
    }

    /**
     * @return array<int, array{code: string, name: string, education_level: string, group: string}>
     */
    private function seniorHigh(): array
    {
        return [
            ...$this->map('SMA', 'Umum', [
                'Informatika',
                'Prakarya',
                'Seni Musik',
                'Seni Rupa',
                'Seni Tari',
            ]),
            ...$this->map('SMA', 'IPA', [
                'Biologi',
                'Fisika',
                'Kimia',
            ]),
            ...$this->map('SMA', 'IPS', [
                'Ekonomi & Akuntansi',
                'Geografi',
                'Sejarah',
                'Sosiologi',
            ]),
            ...$this->map('SMA', 'Bahasa', [
                'Antropologi',
                'Bahasa Arab',
                'Bahasa Indonesia Tingkat Lanjut',
                'Bahasa Inggris Tingkat Lanjut',
                'Bahasa Jepang',
                'Bahasa Jerman',
                'Bahasa Korea',
                'Bahasa Mandarin',
                'Bahasa Prancis',
            ]),
        ];
    }

    /**
     * Konsentrasi keahlian SMK, dikelompokkan menurut rumpunnya.
     *
     * Struktur resminya tiga tingkat: rumpun -> program keahlian -> konsentrasi
     * keahlian. Yang disimpan sebagai mata pelajaran adalah tingkat terdalam,
     * karena itulah yang tercantum di rapor. Tingkat program keahlian sengaja
     * tidak disimpan: ia hanya perantara, dan rumpun sudah cukup untuk menyaring
     * pilihan pada form maupun panel admin.
     *
     * @return array<int, array{code: string, name: string, education_level: string, group: string}>
     */
    private function vocational(): array
    {
        $byGroup = [
            'Agribisnis dan Agriteknologi' => [
                'Agribisnis Ikan Hias',
                'Agribisnis Perikanan Air Tawar',
                'Agribisnis Perikanan Payau dan Laut',
                'Agribisnis Rumput Laut',
                'Agribisnis Perbenihan Tanaman',
                'Agribisnis Tanaman Pangan dan Hortikultura',
                'Agribisnis Tanaman Perkebunan',
                'Agribisnis Ternak Ruminansia',
                'Agribisnis Ternak Unggas',
                'Kesehatan Hewan',
                'Agribisnis Pengolahan Hasil Perikanan',
                'Agribisnis Pengolahan Hasil Pertanian',
                'Usaha Pertanian Terpadu',
            ],
            'Bisnis dan Manajemen' => [
                'Akuntansi',
                'Layanan Perbankan',
                'Layanan Perbankan Syariah',
                'Manajemen Perkantoran',
                'Bisnis Digital',
                'Bisnis Retail',
            ],
            'Energi dan Pertambangan' => [
                'Teknik Energi Surya, Hidro, dan Angin',
                'Geologi Pertambangan',
                'Informasi Geospasial',
                'Teknik Instalasi Tenaga Listrik',
                'Teknik Kelistrikan Pesawat Udara',
                'Teknik Pemanasan, Tata Udara, dan Pendinginan',
                'Teknik Pembangkit Tenaga Listrik',
            ],
            'Kemaritiman' => [
                'Nautika Kapal Penangkapan Ikan',
                'Teknika Kapal Penangkapan Ikan',
            ],
            'Kesehatan dan Pekerjaan Sosial' => [
                'Layanan Penunjang Keperawatan dan Caregiving',
                'Layanan Penunjang Laboratorium Medik',
                'Layanan Penunjang Kefarmasian Klinis dan Komunitas',
            ],
            'Pariwisata' => [
                'Spa dan Beauty Therapy',
                'Tata Kecantikan Kulit dan Rambut',
                'Kuliner',
                'Perhotelan',
                'Usaha Layanan Wisata',
            ],
            'Seni dan Ekonomi Kreatif' => [
                'Animasi',
                'Produksi dan Siaran Program Radio',
                'Produksi dan Siaran Program Televisi',
                'Produksi Film',
                'Desain dan Produksi Busana',
                'Kriya Kreatif Batik dan Tekstil',
                'Kriya Kreatif Kayu dan Rotan',
                'Kriya Kreatif Keramik',
                'Kriya Kreatif Kulit dan Imitasi',
                'Kriya Kreatif Logam dan Perhiasan',
                'Desain Komunikasi Visual',
                'Teknik Grafika',
                // Bernama sama dengan mapel seni di SMA, dibedakan sebagai
                // konsentrasi keahlian agar kodenya tidak berbenturan.
                'Seni Musik (Konsentrasi Keahlian)',
                'Seni Tari (Konsentrasi Keahlian)',
            ],
            'Teknologi Informasi' => [
                'Rekayasa Perangkat Lunak',
                'Sistem Informasi, Jaringan, dan Aplikasi',
                'Teknik Komputer dan Jaringan',
            ],
            'Teknologi Konstruksi dan Bangunan' => [
                'Desain Pemodelan dan Informasi Bangunan',
                'Desain dan Teknik Furnitur',
                'Desain Interior dan Teknik Furnitur',
                'Konstruksi Gedung dan Sanitasi',
                'Teknik Konstruksi dan Perumahan',
                'Teknik Perawatan Gedung',
            ],
            'Teknologi Manufaktur dan Rekayasa' => [
                'Analisis Pengujian Laboratorium',
                'Kimia Analisis',
                'Teknik Audio Video',
                'Teknik Elektronika Industri',
                'Teknik Elektronika Komunikasi',
                'Teknik Elektronika Pesawat Udara',
                'Teknik Instrumentasi Medik',
                'Teknik Mekatronika',
                'Teknik Otomasi Industri',
                'Desain Rancang Bangun Kapal',
                'Konstruksi Kapal Baja',
                'Konstruksi Kapal Non Baja',
                'Desain Gambar Mesin',
                'Teknik Mekanik Industri',
                'Teknik Pemesinan',
                'Teknik Pengecoran Logam',
                'Teknik Alat Berat',
                'Teknik Kendaraan Ringan',
                'Teknik Ototronik',
                'Teknik Sepeda Motor',
                'Teknik Fabrikasi Logam dan Manufaktur',
                'Airframe Powerplant',
                'Electrical Avionic',
            ],
        ];

        $rows = [];
        foreach ($byGroup as $group => $names) {
            $rows = [...$rows, ...$this->map('SMK', $group, $names)];
        }

        return $rows;
    }

    /**
     * @param  array<int, string>  $names
     * @return array<int, array{code: string, name: string, education_level: string, group: string}>
     */
    private function map(string $level, string $group, array $names): array
    {
        return array_map(fn (string $name) => [
            'code' => $this->code($name),
            'name' => $name,
            'education_level' => $level,
            'group' => $group,
        ], $names);
    }

    /**
     * Kode disamakan dengan yang dihasilkan `SubjectRequest`, sehingga mapel
     * bawaan dan mapel tambahan admin memakai aturan penamaan yang sama.
     */
    private function code(string $name): string
    {
        return str($name)->slug()->value();
    }
}
