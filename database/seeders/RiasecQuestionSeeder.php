<?php

namespace Database\Seeders;

use App\Models\RiasecQuestion;
use App\Support\Riasec;
use Illuminate\Database\Seeder;

/**
 * 30 pernyataan kuesioner RIASEC — 5 butir per dimensi, skala Likert 1-5.
 *
 * Urutan tampil diselang-seling antar dimensi (R, I, A, S, E, C, R, I, ...)
 * agar responden tidak menebak pola dan menjawab seragam per blok.
 */
class RiasecQuestionSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            'R' => [
                'Saya senang membongkar dan memperbaiki peralatan elektronik atau mesin.',
                'Saya lebih suka bekerja menggunakan alat dan tangan daripada duduk di belakang meja.',
                'Saya merasa puas ketika berhasil membuat sesuatu dengan tangan saya sendiri.',
                'Saya tertarik pada pekerjaan yang melibatkan konstruksi atau bangunan.',
                'Saya senang melakukan praktik di bengkel atau laboratorium teknik.',
            ],
            'I' => [
                'Saya senang memecahkan soal-soal yang menantang logika saya.',
                'Saya tertarik melakukan percobaan ilmiah di laboratorium.',
                'Saya senang menganalisis data untuk menemukan pola atau kesimpulan.',
                'Saya sering bertanya "mengapa" dan mencari tahu penyebab suatu peristiwa.',
                'Saya tertarik mempelajari teori dan konsep yang rumit.',
            ],
            'A' => [
                'Saya senang menggambar, mendesain, atau membuat karya visual.',
                'Saya tertarik pada kegiatan seni seperti musik, teater, atau menulis.',
                'Saya suka menuangkan ide baru yang belum pernah dilakukan orang lain.',
                'Saya menikmati pekerjaan yang memberi kebebasan berekspresi.',
                'Saya tertarik membuat konten kreatif seperti video, foto, atau animasi.',
            ],
            'S' => [
                'Saya senang membantu teman yang mengalami kesulitan belajar.',
                'Saya merasa nyaman menjelaskan sesuatu kepada orang lain.',
                'Saya tertarik pada kegiatan sosial dan pengabdian masyarakat.',
                'Saya senang bekerja dalam tim daripada bekerja sendirian.',
                'Saya tertarik pada pekerjaan yang melibatkan pelayanan kepada orang banyak.',
            ],
            'E' => [
                'Saya senang memimpin kelompok dalam mengerjakan suatu tugas.',
                'Saya tertarik memulai usaha atau bisnis sendiri.',
                'Saya percaya diri saat berbicara di depan banyak orang.',
                'Saya senang meyakinkan orang lain agar menyetujui gagasan saya.',
                'Saya berani mengambil keputusan meskipun ada risikonya.',
            ],
            'C' => [
                'Saya senang bekerja dengan data dan angka yang tersusun rapi.',
                'Saya terbiasa membuat jadwal dan mengikuti rencana yang sudah dibuat.',
                'Saya teliti dalam memeriksa dokumen atau laporan.',
                'Saya merasa nyaman mengerjakan tugas dengan prosedur yang jelas.',
                'Saya cermat memperhatikan detail kecil agar tidak terjadi kesalahan.',
            ],
        ];

        $sortOrder = 0;
        $perDimension = count($items['R']);
        $seeded = [];

        for ($i = 0; $i < $perDimension; $i++) {
            foreach (Riasec::DIMENSIONS as $dimension) {
                $sortOrder++;
                $statement = $items[$dimension][$i];
                $seeded[] = $statement;

                RiasecQuestion::query()->updateOrCreate(
                    ['statement' => $statement],
                    [
                        'dimension' => $dimension,
                        'sort_order' => $sortOrder,
                        'is_active' => true,
                    ]
                );
            }
        }

        // Butir di luar daftar ini dinonaktifkan, bukan dihapus, supaya jawaban
        // pada sesi tes yang sudah selesai tetap utuh.
        RiasecQuestion::query()->whereNotIn('statement', $seeded)->update(['is_active' => false]);
    }
}
