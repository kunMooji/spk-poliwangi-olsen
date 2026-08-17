<?php

namespace Database\Seeders;

use App\Models\Criteria;
use Illuminate\Database\Seeder;

/**
 * Kriteria C1..C5. Total bobot bawaan = 1.000000.
 *
 * Pembagian bobot: nilai rapor 0.45, RIASEC 0.35, tracer study 0.15, minat 0.05.
 *
 * Blok rapor 0.45 dibagi mengikuti SNBP: komponen pertama (rerata seluruh mapel)
 * 0.25 atau 55,6% dari blok — memenuhi syarat paling sedikit 50% — dan komponen
 * kedua (mapel pendukung prodi) 0.20 atau 44,4%, di bawah batas 50%. Rasio itu
 * dihitung terhadap blok rapor saja; RIASEC, prioritas, dan tracer study adalah
 * tambahan sistem ini yang tidak dikenal SNBP.
 *
 * C1 bernilai sama untuk seluruh program studi dalam satu sesi, sehingga tidak
 * membedakan peringkat — pembeda nilai rapor antar prodi ada pada C2. Lihat
 * catatan rancangan di DecisionMatrixBuilder mengenai konsekuensi kolom konstan
 * ini terhadap normalisasi.
 *
 * C4 (urutan prioritas) sengaja diberi bobot terkecil. Berbeda dengan kriteria
 * lain, nilainya bukan atribut yang melekat pada program studi melainkan
 * pernyataan keinginan calon mahasiswa itu sendiri. Bobot besar membuat sistem
 * mengembalikan pilihan yang sudah disebutkan responden alih-alih memberi
 * informasi baru. Pada 0.05, C4 hanya sanggup membalikkan keputusan yang sudah
 * nyaris seri — berperan sebagai pemecah seri, bukan penggerak hasil.
 *
 * Minat sendiri tetap tertimbang besar lewat C3, yang mengukurnya dengan
 * instrumen RIASEC alih-alih menanyakan preferensi secara langsung.
 */
class CriteriaSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['code' => 'C1', 'name' => 'Rerata Rapor Seluruh Mapel', 'weight' => 0.25, 'source' => 'rapor_average', 'unit' => 'Skala 0-100'],
            ['code' => 'C2', 'name' => 'Nilai Mapel Pendukung Prodi', 'weight' => 0.20, 'source' => 'support_subject', 'unit' => 'Skala 0-100'],
            ['code' => 'C3', 'name' => 'Kesesuaian Kepribadian RIASEC', 'weight' => 0.35, 'source' => 'riasec', 'unit' => 'Persentase 0-100%'],
            ['code' => 'C4', 'name' => 'Minat Urutan Prioritas Prodi', 'weight' => 0.05, 'source' => 'priority', 'unit' => 'Skor 0-100'],
            ['code' => 'C5', 'name' => 'Prospek Kerja (Tracer Study)', 'weight' => 0.15, 'source' => 'tracer', 'unit' => 'Rasio 0.00-1.00'],
        ];

        $descriptions = [
            'rapor_average' => 'Rerata nilai rapor seluruh mata pelajaran, semua semester kecuali semester terakhir (komponen pertama SNBP).',
            'support_subject' => 'Rerata nilai rapor pada mata pelajaran pendukung yang ditetapkan untuk masing-masing program studi (komponen kedua SNBP).',
            'riasec' => 'Cosine similarity antara vektor RIASEC calon mahasiswa dan profil RIASEC program studi.',
            'priority' => 'Konversi urutan pilihan prodi: prioritas pertama memperoleh skor tertinggi.',
            'tracer' => 'Rasio alumni yang terserap dunia kerja berdasarkan data tracer study.',
        ];

        foreach ($rows as $index => $row) {
            Criteria::query()->updateOrCreate(
                ['code' => $row['code']],
                $row + [
                    'type' => 'benefit',
                    'description' => $descriptions[$row['source']],
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]
            );
        }
    }
}
