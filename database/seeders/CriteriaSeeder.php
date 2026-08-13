<?php

namespace Database\Seeders;

use App\Models\Criteria;
use Illuminate\Database\Seeder;

/**
 * Kriteria C1..C9. Total bobot bawaan = 1.000000.
 *
 * Pembagian bobot: nilai rapor 0.45, RIASEC 0.35, tracer study 0.15, minat 0.05.
 *
 * C8 (urutan prioritas) sengaja diberi bobot terkecil. Berbeda dengan kriteria
 * lain, nilainya bukan atribut yang melekat pada program studi melainkan
 * pernyataan keinginan calon mahasiswa itu sendiri. Bobot besar membuat sistem
 * mengembalikan pilihan yang sudah disebutkan responden alih-alih memberi
 * informasi baru. Pada 0.05, C8 hanya sanggup membalikkan keputusan yang sudah
 * nyaris seri — berperan sebagai pemecah seri, bukan penggerak hasil.
 *
 * Minat sendiri tetap tertimbang besar lewat C7, yang mengukurnya dengan
 * instrumen RIASEC alih-alih menanyakan preferensi secara langsung.
 */
class CriteriaSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['code' => 'C1', 'name' => 'Nilai Rapor Matematika', 'weight' => 0.10, 'source' => 'subject_score', 'subject' => 'math', 'unit' => 'Skala 0-100'],
            ['code' => 'C2', 'name' => 'Nilai Rapor Fisika', 'weight' => 0.07, 'source' => 'subject_score', 'subject' => 'physics', 'unit' => 'Skala 0-100'],
            ['code' => 'C3', 'name' => 'Nilai Rapor Kimia', 'weight' => 0.08, 'source' => 'subject_score', 'subject' => 'chemistry', 'unit' => 'Skala 0-100'],
            ['code' => 'C4', 'name' => 'Nilai Rapor Biologi', 'weight' => 0.08, 'source' => 'subject_score', 'subject' => 'biology', 'unit' => 'Skala 0-100'],
            ['code' => 'C5', 'name' => 'Nilai Rapor Bahasa Indonesia', 'weight' => 0.05, 'source' => 'subject_score', 'subject' => 'indonesian', 'unit' => 'Skala 0-100'],
            ['code' => 'C6', 'name' => 'Nilai Rapor Bahasa Inggris', 'weight' => 0.07, 'source' => 'subject_score', 'subject' => 'english', 'unit' => 'Skala 0-100'],
            ['code' => 'C7', 'name' => 'Kesesuaian Kepribadian RIASEC', 'weight' => 0.35, 'source' => 'riasec', 'subject' => null, 'unit' => 'Persentase 0-100%'],
            ['code' => 'C8', 'name' => 'Minat Urutan Prioritas Prodi', 'weight' => 0.05, 'source' => 'priority', 'subject' => null, 'unit' => 'Skor 0-100'],
            ['code' => 'C9', 'name' => 'Prospek Kerja (Tracer Study)', 'weight' => 0.15, 'source' => 'tracer', 'subject' => null, 'unit' => 'Rasio 0.00-1.00'],
        ];

        $descriptions = [
            'subject_score' => 'Nilai rapor dikalikan bobot relevansi mata pelajaran pada masing-masing program studi.',
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
