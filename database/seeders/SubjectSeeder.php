<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;

/**
 * Master mata pelajaran mengikuti struktur Kurikulum Merdeka.
 *
 * SMA berisi mapel umum yang diterima seluruh peserta didik, ditambah kelompok
 * pilihan IPA dan IPS. "IPA" dan "IPS" sendiri bukan mata pelajaran melainkan
 * nama kelompok, sehingga muncul sebagai `group` dan bukan sebagai baris.
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
        ];
    }

    /**
     * Konsentrasi keahlian SMK, dikelompokkan menurut rumpunnya.
     *
     * @return array<int, array{code: string, name: string, education_level: string, group: string}>
     */
    private function vocational(): array
    {
        $byGroup = [
            'Agribisnis dan Agriteknologi' => [
                'Agribisnis Perikanan',
                'Agribisnis Tanaman',
                'Agribisnis Ternak',
                'Agriteknologi Pengolahan Hasil Pertanian',
                'Usaha Pertanian Terpadu',
            ],
            'Bisnis dan Manajemen' => [
                'Akuntansi dan Keuangan Lembaga',
                'Manajemen Perkantoran dan Layanan Bisnis',
                'Pemasaran',
            ],
            'Energi dan Pertambangan' => [
                'Teknik Energi Terbarukan',
                'Teknik Geologi Pertambangan',
                'Teknik Geospasial',
            ],
            'Seni dan Ekonomi Kreatif' => [
                'Animasi',
                'Broadcasting dan Perfilman',
                'Busana',
                'Desain dan Produksi Kriya',
                'Desain Komunikasi Visual',
                'Seni Pertunjukan',
            ],
            'Teknologi Informasi' => [
                'Rekayasa Perangkat Lunak',
                'Sistem Informasi, Jaringan, dan Aplikasi',
                'Teknik Komputer dan Jaringan',
            ],
            'Teknologi Konstruksi dan Bangunan' => [
                'Desain Pemodelan dan Informasi Bangunan',
                'Teknik Furnitur',
                'Teknik Konstruksi dan Perumahan',
                'Teknik Perawatan Gedung',
            ],
            'Teknologi Manufaktur dan Rekayasa' => [
                'Kimia Analisis',
                'Teknik Elektronika',
                'Teknik Konstruksi Kapal',
                'Teknik Mesin',
                'Teknik Otomotif',
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
