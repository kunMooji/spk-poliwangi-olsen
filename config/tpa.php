<?php

/*
|--------------------------------------------------------------------------
| Latihan SKD CPNS (area tersembunyi /logintpa)
|--------------------------------------------------------------------------
|
| Soal, hint, dan pembahasan dibuat oleh Claude API. API key hanya dibaca
| dari .env (ANTHROPIC_API_KEY) dan tidak pernah disimpan di repositori.
|
*/

return [

    'anthropic_api_key' => env('ANTHROPIC_API_KEY'),

    'model' => env('TPA_MODEL', 'claude-opus-5-5'),

    // low = cepat & hemat, cukup untuk menyusun soal pilihan ganda.
    'effort' => env('TPA_EFFORT', 'low'),

    'questions_per_tryout' => 15,

    // Batas waktu satu panggilan API (detik). Shared hosting sering memutus
    // request PHP yang terlalu lama, jadi jangan dinaikkan berlebihan.
    'timeout' => (float) env('TPA_TIMEOUT', 240),

    'categories' => [
        'twk' => [
            'name' => 'Tes Wawasan Kebangsaan',
            'short' => 'TWK',
            'scoring' => 'Benar = 5 poin, salah/kosong = 0 poin.',
            'subtopics' => [
                'Nasionalisme' => 'semangat kebangsaan, sejarah pergerakan nasional, sikap mengutamakan kepentingan bangsa di era global/digital',
                'Integritas' => 'kejujuran, antikorupsi, konflik kepentingan, gratifikasi, kode etik ASN, Core Values ASN BerAKHLAK',
                'Bela Negara' => 'nilai dasar bela negara (UU 3/2002 & UU 23/2019), ancaman non-militer (siber, hoaks, narkoba, radikalisme)',
                'Pilar Negara' => 'Pancasila, UUD NRI 1945 dan amandemennya, NKRI, Bhinneka Tunggal Ika, lembaga negara, otonomi daerah',
                'Bahasa Negara' => 'kedudukan & fungsi bahasa Indonesia (UU 24/2009), PUEBI/EYD V, kalimat efektif, sumpah pemuda',
            ],
        ],
        'tiu' => [
            'name' => 'Tes Inteligensi Umum',
            'short' => 'TIU',
            'scoring' => 'Benar = 5 poin, salah/kosong = 0 poin.',
            'subtopics' => [
                'Verbal - Analogi' => 'hubungan kata berpasangan',
                'Verbal - Silogisme' => 'penarikan kesimpulan logis dari premis',
                'Verbal - Analitis' => 'penalaran posisi/urutan/penjadwalan dari beberapa syarat',
                'Verbal - Sinonim & Antonim' => 'persamaan dan lawan kata baku (KBBI)',
                'Numerik - Berhitung' => 'operasi hitung, pecahan, persen, akar & pangkat',
                'Numerik - Deret Angka' => 'pola deret angka/huruf',
                'Numerik - Perbandingan Kuantitatif' => 'membandingkan nilai x dan y',
                'Numerik - Soal Cerita' => 'aritmetika sosial, kecepatan, pekerjaan, usia, untung-rugi',
                'Figural - Analogi' => 'perubahan pola simbol pasangan pertama diterapkan ke pasangan kedua (ditulis dengan simbol teks)',
                'Figural - Ketidaksamaan' => 'mencari satu susunan simbol yang polanya berbeda',
                'Figural - Serial' => 'melanjutkan urutan simbol berdasarkan rotasi, jumlah elemen, atau perubahan bentuk',
            ],
        ],
        'tkp' => [
            'name' => 'Tes Karakteristik Pribadi',
            'short' => 'TKP',
            'scoring' => 'Tidak ada jawaban salah; setiap opsi bernilai 1-5 poin.',
            'subtopics' => [
                'Pelayanan Publik' => 'sikap melayani masyarakat dengan cepat, ramah, adil, sesuai SOP',
                'Jejaring Kerja' => 'membangun dan memelihara relasi kerja, kolaborasi lintas instansi',
                'Sosial Budaya' => 'adaptasi di lingkungan majemuk, toleransi, menghargai perbedaan',
                'Teknologi Informasi dan Komunikasi' => 'pemanfaatan TIK, SPBE, keamanan data, literasi digital',
                'Profesionalisme' => 'tanggung jawab, disiplin, kualitas kerja, pengembangan diri',
                'Anti Radikalisme' => 'sikap terhadap paham radikal, moderasi beragama, menangkal provokasi',
            ],
        ],
    ],

    // Konteks kebijakan/isu 2024–2027 yang wajib dipakai agar soal tidak basi.
    'current_context' => <<<'TXT'
Gunakan konteks Indonesia periode 2024-2027 bila relevan, misalnya: pemerintahan Presiden Prabowo Subianto
dan Wapres Gibran Rakabuming Raka (Kabinet Merah Putih, dilantik Oktober 2024) dengan visi Asta Cita;
Indonesia Emas 2045 dan RPJPN 2025-2045 / RPJMN 2025-2029; pemindahan Ibu Kota Nusantara (IKN);
program Makan Bergizi Gratis; hilirisasi industri; transformasi digital pemerintahan (SPBE, GovTech/INA Digital,
PP 82/2024 tentang Manajemen ASN turunan UU 20/2023 tentang ASN); Core Values ASN "BerAKHLAK" dan employer
branding "Bangga Melayani Bangsa"; pemilu & pilkada serentak 2024; KUHP baru (UU 1/2023) yang berlaku 2026;
UU Pelindungan Data Pribadi (UU 27/2022); isu hoaks, judi online, pinjol ilegal, perubahan iklim, dan keamanan siber.
Jangan mengarang angka statistik, nomor peraturan, atau peristiwa yang tidak pasti; jika ragu, buat soal yang
menguji konsep, bukan hafalan fakta spesifik.
TXT,

    // Rubrik tingkat kesulitan (10-100) yang diterjemahkan ke dalam prompt.
    'difficulty_rubric' => [
        30 => 'Mudah: soal pendek dan langsung, 1 langkah penalaran, konsep dasar, distraktor jelas berbeda dari jawaban benar.',
        60 => 'Sedang: setara SKD sebenarnya, 2-3 langkah penalaran, stem berisi konteks/kasus singkat, distraktor cukup masuk akal.',
        80 => 'Sulit: stem panjang berupa kasus/teks, 3-4 langkah penalaran atau hitungan bertahap, perlu analisis dan pemahaman mendalam, distraktor sangat mirip jawaban benar.',
        100 => 'Sangat sulit (di atas rata-rata SKD): kasus kompleks dan multi-konsep, 4+ langkah penalaran, jebakan halus pada kata kunci, beban hitung tinggi, semua opsi tampak masuk akal.',
    ],
];
