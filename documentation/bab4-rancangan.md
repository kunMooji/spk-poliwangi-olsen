# Rancangan BAB 4 — Hasil dan Pembahasan

Dokumen kerja untuk menyusun BAB 4 skripsi *Sistem Pendukung Keputusan Rekomendasi
Program Studi Menggunakan Metode CoCoSo dengan Pendekatan RIASEC (Studi Kasus:
Politeknik Negeri Banyuwangi)*.

Isinya: kerangka bab, apa yang masuk ke tiap subbab, tabel dan gambar yang perlu
disiapkan, angka yang sudah terukur di sistem, serta daftar data yang masih harus
dikumpulkan sebelum bab ini bisa ditutup.

Sumber angka: [perubahan-sistem.md](perubahan-sistem.md), [kriteria/](kriteria/),
[research-criteria.md](research-criteria.md), dan kode yang berjalan.

---

## 0. Peta Rumusan Masalah ke Subbab

BAB 4 harus terbaca sebagai jawaban atas tiga rumusan masalah:

| RM | Pertanyaan | Dijawab di |
|---|---|---|
| RM 1 | Permasalahan dan kebutuhan fungsional apa yang menjadi dasar perancangan SPK? | 4.1 |
| RM 2 | Bagaimana penerapan CoCoSo menghasilkan rekomendasi yang objektif dan personal? | 4.2, 4.3, 4.4.1 |
| RM 3 | Bagaimana stabilitas dan ketangguhan hasil saat diuji analisis sensitivitas? | 4.4.3, 4.5.3 |

Bagian 4.5 menarik ketiganya menjadi satu argumen dan menyebut keterbatasan.

---

## 4.1 Hasil Identifikasi Masalah dan Analisis Kebutuhan

Menjawab RM 1. Ini bagian yang paling banyak berubah dari proposal, karena
wawancara menghasilkan temuan yang **mengoreksi rancangan kriteria di BAB 3**.
Perubahan itu jangan disembunyikan — justru inilah bukti bahwa RM 1 benar-benar
dikerjakan, bukan diasumsikan sejak awal.

### 4.1.1 Hasil Wawancara

Narasumber: Uri Anjarwati, S.E., M.M. — Kepala Bagian Akademik Kemahasiswaan dan
Kerjasama Poliwangi. Wawancara 24 Juni 2026, 13 butir pertanyaan.

**Tabel 4.1 Temuan Wawancara dan Implikasinya pada Sistem**

| No | Temuan lapangan | Butir | Implikasi pada sistem |
|---|---|---|---|
| 1 | Belum ada tahapan seleksi atau asesmen apa pun untuk melihat kecocokan pendaftar dengan prodi yang dipilih | 9 | Celah yang diisi penelitian ini; sistem berperan sebagai instrumen pemetaan mandiri, bukan alat seleksi |
| 2 | Calon pendaftar sering bingung memilih prodi bukan karena tidak punya minat, melainkan belum paham hubungan minat–kemampuan–prodi–prospek karier | 6 | Keluaran sistem harus menampilkan keempat sisi itu sekaligus, bukan sekadar satu angka peringkat |
| 3 | Penyebab dominan salah jurusan: minim informasi, ikut pilihan teman, prodi tidak sesuai minat dan kemampuan | 7 | Membenarkan pemakaian instrumen minat terstandar (RIASEC) alih-alih menanyakan preferensi langsung |
| 4 | Angka pengunduran diri 2–3% dinilai masih perlu jadi perhatian dan bahan evaluasi | 8 | Menguatkan urgensi di latar belakang |
| 5 | **Enam mata pelajaran tetap dinilai belum cukup mewakili kemampuan akademik pendaftar.** Poliwangi mengikuti aturan nasional: SNBP memakai rerata rapor seluruh mapel, ditambah paling banyak dua mapel kekhususan/pendukung sesuai prodi, serta portofolio dan prestasi | 10 | **Kriteria akademik dirombak** dari C1–C6 (enam mapel tetap) menjadi C1 (rerata seluruh mapel) dan C2 (mapel pendukung prodi). Lihat 4.1.2 |
| 6 | Urutan pilihan prodi belum tentu mencerminkan minat; masih dipengaruhi peluang lolos dan ikut teman. Di SNBP urutan dipakai **sebagai pembeda saja bila nilai sama** | 11 | Bobot C4 ditetapkan terkecil (0,05) dan diposisikan sebagai pemecah seri, bukan penggerak hasil |
| 7 | Setuju bila komponen kepribadian dipadukan dengan nilai rapor, karena rapor hanya mengukur kemampuan akademik | 12 | Membenarkan C3 (RIASEC) sebagai kriteria berbobot terbesar |
| 8 | Informasi prospek kerja cukup berpengaruh bagi calon mahasiswa maupun orang tua; data tracer study layak jadi pertimbangan | 13 | Membenarkan C5 (rasio keterserapan alumni) |
| 9 | Sosialisasi jemput bola menyasar SMA, SMK, dan MA melalui kunjungan tim PMB, MGBK, dan Edufair | 5 | Sistem harus berbasis web agar dapat dipakai di lapangan; **populasi mencakup SMK**, yang berdampak langsung pada temuan No. 5 |

Lembar wawancara lengkap ditempatkan di lampiran.

### 4.1.2 Koreksi Kriteria Akademik: dari Enam Mapel Tetap ke Skema SNBP

**Ini subbab kunci.** Tulis sebagai *temuan*, bukan sebagai permintaan maaf. Alur
argumennya empat langkah.

**a. Proposal tidak menepati rujukannya sendiri.**
Proposal (hal. 22) menyatakan keenam mapel ditetapkan "berdasarkan syarat
pendaftaran". Syarat pendaftaran nasional yang dimaksud — SNBP — pada kenyataannya
tidak mengenal enam mapel tetap. Aturannya dua komponen: rerata nilai rapor
seluruh mata pelajaran dengan bobot paling sedikit 50%, dan paling banyak dua mata
pelajaran pendukung program studi dengan bobot paling banyak 50%. Hasil wawancara
butir 10 mengonfirmasi hal ini langsung dari pihak institusi.

**b. Skema lama tidak valid untuk populasi target.**
Poliwangi adalah politeknik yang pasar utamanya lulusan SMK, dan sosialisasinya
memang menyasar SMA, SMK, dan MA (wawancara butir 5). Siswa jurusan IPS atau Bahasa
tidak menempuh Fisika, Kimia, dan Biologi; siswa SMK punya mata pelajaran
konsentrasi keahlian yang sama sekali tidak terwakili keenam mapel itu. Memaksa
mereka mengisi nilai mapel yang tidak ditempuh berarti data dikarang atau diisi
nol — cacat validitas pada instrumen pengumpulan data, bukan sekadar soal kerapian.

**c. "Relevansi mapel" tidak punya rujukan formal.**
Skema lama memerlukan bobot relevansi 0–1 untuk tiap mapel pada tiap prodi, yang
harus ditetapkan sendiri tanpa acuan. Ini bertabrakan dengan RM 3 yang justru
mengklaim sistem bebas dari bias subjektivitas. Niat awalnya — membedakan prodi
berdasarkan mapel yang relevan — tetap dipertahankan, tetapi lewat mekanisme yang
berdasar regulasi (mapel pendukung SNBP), bukan angka yang ditentukan sendiri.

**d. Temuan matematis saat implementasi.**
Pada rumus lama `x_ij = nilai_rapor × relevansi_mapel`, nilai rapor menjadi faktor
pengali yang sama untuk seluruh mapel dalam satu baris. Begitu masuk normalisasi
min–max CoCoSo, faktor konstan itu tercoret secara aljabar: siswa dengan rapor 95
dan siswa dengan rapor 40 dapat memperoleh peringkat rekomendasi yang identik,
karena yang tersisa untuk membedakan hanya pola relevansi, bukan levelnya.

Akar persoalannya perlu dinyatakan eksplisit, karena ini kontribusi konseptual:
**SNBP memeringkat siswa untuk satu prodi, sedangkan sistem ini memeringkat prodi
untuk satu siswa.** Arah pemeringkatannya terbalik. Karena itu tidak semua komponen
SNBP otomatis berfungsi sebagai pembeda di sini.

**Tabel 4.2 Perbandingan Skema Kriteria Akademik**

| | Skema proposal | Skema hasil penelitian |
|---|---|---|
| Kriteria rapor | C1–C6: enam mapel tetap pola IPA | C1 rerata seluruh mapel · C2 mapel pendukung prodi |
| Total kriteria | 9 | 5 |
| Bobot blok rapor | 0,45 dibagi rata ke enam mapel | 0,45 = C1 0,25 + C2 0,20 (55,6% : 44,4%) |
| Rujukan | Ditetapkan sendiri | Struktur penilaian SNBP |
| Validitas untuk SMK/IPS | Tidak terpenuhi | Terpenuhi — hanya mapel yang ditempuh yang dirata-ratakan |

Empat kategori kriteria (rapor, RIASEC, prioritas prodi, prospek kerja) **tidak
berubah**. Yang berubah hanya jumlah kriteria di dalam kategori rapor. Novelty
penelitian — integrasi RIASEC dengan CoCoSo — utuh.

### 4.1.3 Kriteria Keputusan Final

**Tabel 4.3 Kriteria Keputusan C1–C5**

| Kode | Nama | Sifat | Bobot | Bentuk nilai | Batas normalisasi |
|---|---|---|---|---|---|
| C1 | Rerata Rapor Seluruh Mapel | Benefit | 0,25 | Rerata semester 1–5, skala 0–100 | 0 – 100 (tetap) |
| C2 | Nilai Mapel Pendukung Prodi | Benefit | 0,20 | Rerata maksimal dua mapel pendukung, skala 0–100 | 0 – 100 (tetap) |
| C3 | Kesesuaian Kepribadian RIASEC | Benefit | 0,35 | Korelasi Pearson dipetakan ke 0–100 | 0 – 100 (tetap) |
| C4 | Minat Urutan Prioritas Prodi | Benefit | 0,05 | Konversi urutan pilihan, skor 0–100 | 0 – 100 (tetap) |
| C5 | Prospek Kerja (Tracer Study) | Benefit | 0,15 | Rasio keterserapan alumni | 0,00 – 1,00 (tetap) |

Seluruhnya bertipe *benefit*. Total bobot 1,000.

Pembagian bobot dan asal-usulnya:

- **Blok rapor 0,45** — C1 dan C2, mengikuti SNBP. Rasio 55,6% : 44,4% memenuhi
  syarat komponen pertama ≥ 50% dan komponen kedua ≤ 50%. Rasio ini dihitung
  terhadap blok rapor saja, bukan terhadap total seluruh kriteria.
- **Tambahan sistem 0,55** — C3, C4, dan C5 tidak dikenal SNBP. Ketiganya yang
  membedakan sistem ini dari sekadar mesin perangkingan SNBP.

Perlu ditulis terus terang bahwa bobot berasal dari wawancara akademik, bukan dari
metode pembobotan formal seperti AHP atau Entropy. Konsekuensinya tidak ada uji
konsistensi — dan justru itulah alasan analisis sensitivitas menjadi pengujian yang
wajib, bukan pelengkap.

### 4.1.4 Kebutuhan Fungsional

**Tabel 4.4 Kebutuhan Fungsional Sisi Calon Mahasiswa**

| Kode | Kebutuhan | Realisasi |
|---|---|---|
| KF-01 | Mendaftar dan masuk sebagai calon mahasiswa | Autentikasi Laravel Breeze |
| KF-02 | Mengisi biodata, nilai rapor per semester, dan nilai mapel pendukung | Formulir mulai tes |
| KF-03 | Menyusun urutan prioritas program studi (minimal 3) | Bagian prioritas pada formulir |
| KF-04 | Mengisi kuesioner RIASEC skala Likert dengan simpan otomatis | Halaman kuesioner |
| KF-05 | Melihat dasbor hasil: profil RIASEC dan peringkat rekomendasi | Halaman hasil |
| KF-06 | Menelusuri rincian perhitungan CoCoSo tiap langkah | Halaman rincian perhitungan |
| KF-07 | Mencetak lembar hasil | Halaman cetak |
| KF-08 | Melihat riwayat dan membandingkan antar sesi tes | Riwayat dan halaman banding |

**Tabel 4.5 Kebutuhan Fungsional Sisi Admin**

| Kode | Kebutuhan | Realisasi |
|---|---|---|
| KF-09 | CRUD program studi beserta profil RIASEC dan mapel pendukungnya | Menu Program Studi |
| KF-10 | CRUD mata pelajaran master (jenjang SMA/SMK, kelompok peminatan) | Menu Mata Pelajaran |
| KF-11 | Mengelola kriteria dan bobot tanpa menyentuh kode perhitungan | Menu Kriteria |
| KF-12 | CRUD butir pernyataan kuesioner RIASEC | Menu Pernyataan |
| KF-13 | Memperbarui data tracer study tiap prodi | Menu Tracer Study |
| KF-14 | Mengatur parameter algoritma: λ, ε, ambang margin, ambang seri, skala Likert | Menu Pengaturan |
| KF-15 | Mengelola gelombang PMB | Menu Periode |
| KF-16 | Memantau, melihat rincian, dan mengekspor rekapitulasi hasil tes | Menu Rekap |
| KF-17 | Menjalankan analisis sensitivitas per sesi tes | Submenu sensitivitas pada rekap |
| KF-18 | Melihat statistik agregat dan reliabilitas instrumen | Menu Statistik |
| KF-19 | Mengelola pengguna dan menelusuri log aktivitas | Menu Pengguna dan Log |

Batasan hak akses ditegakkan di lapisan rute: admin **tidak boleh** mengerjakan
tes, calon mahasiswa tidak boleh membuka panel admin maupun hasil orang lain.

### 4.1.5 Perubahan terhadap Rancangan Proposal

Subbab pendek tapi penting untuk sidang — supaya penguji tidak menemukan sendiri
selisih antara BAB 3 dan BAB 4.

**Tabel 4.6 Perubahan Rancangan dan Alasannya**

| Aspek | Proposal (BAB 3) | Hasil penelitian (BAB 4) | Alasan |
|---|---|---|---|
| Jumlah kriteria | 9 (C1–C9) | 5 (C1–C5) | Wawancara butir 10 dan validitas untuk SMK/IPS — lihat 4.1.2 |
| Kesesuaian RIASEC | Persentase akumulasi skor Likert | Korelasi Pearson dipetakan ke 0–100 | Mengukur kecocokan *pola* minat terhadap profil prodi, bukan tinggi skor mentah |
| Penentuan rekomendasi | Ambang kelayakan (*threshold*) atas nilai K | Ambang **margin** peringkat 1 terhadap peringkat 2 | K tidak punya skala absolut; peringkat 1 selalu bernilai 100 sehingga ambang tidak pernah mengikat — lihat 4.5.3 |
| Normalisasi | Min–max sampel | Batas tetap untuk kelima kriteria | Batas sampel membuat bobot berhenti mewakili pengaruh sebenarnya — lihat 4.5.2 |

Bila pembimbing menghendaki, BAB 3 dapat direvisi agar sinkron. Bila tidak, subbab
ini yang menjembatani.

---

## 4.2 Hasil Implementasi Sistem

Menjawab RM 2 pada sisi pembangunan sistem. Isinya deskriptif dan bergambar.

### 4.2.1 Lingkungan Implementasi

| Lapisan | Pilihan |
|---|---|
| Kerangka kerja | Laravel 11.55 (PHP 8.2+) |
| Basis data | MySQL |
| Autentikasi | Laravel Breeze (Blade) |
| Antarmuka | Blade, Tailwind CSS, Alpine.js, mendukung mode gelap |
| Grafik | Chart.js |
| Aset | Vite |
| Pengujian | PHPUnit |

Jumlah alternatif: **19 program studi** Poliwangi (18 D4 dan 1 D3).

### 4.2.2 Struktur Basis Data

Sajikan ERD, lalu jelaskan pembagian tiga kelompok tabel — ini yang membuat hasil
tes dapat dipertanggungjawabkan:

- **Data master** — dikelola admin: `study_programs`, `study_program_subjects`,
  `subjects`, `criteria`, `riasec_questions`, `settings`, `periods`.
- **Data transaksi** — jejak satu sesi tes: `assessments`,
  `assessment_rapor_semesters`, `assessment_subject_scores`,
  `assessment_priorities`, `assessment_answers`, `assessment_results`.
- **Data jejak** — `activity_logs`, hanya ditulis, tidak pernah diubah.

Poin yang layak ditonjolkan: tabel `assessments` menyimpan **snapshot parameter**
(bobot, λ, ε yang berlaku saat perhitungan). Bila admin mengubah bobot besok, hasil
tes kemarin tidak ikut berubah dan tetap dapat direproduksi. Ini syarat agar hasil
penelitian dapat diaudit.

### 4.2.3 Antarmuka Calon Mahasiswa

Tampilkan tangkapan layar berpasangan dengan mockup BAB 3 (Gambar 3.6–3.9) supaya
terlihat implementasinya menepati rancangan:

1. Halaman muka dan alur tiga langkah
2. Formulir biodata, nilai rapor per semester, nilai mapel pendukung, dan
   penyusunan prioritas prodi
3. Kuesioner RIASEC dengan simpan otomatis dan indikator kemajuan
4. Dasbor hasil: kartu ringkasan, grafik profil RIASEC, tabel peringkat, penanda
   peringkat praktis seri
5. Halaman rincian perhitungan
6. Lembar cetak dan halaman perbandingan antar sesi

### 4.2.4 Antarmuka Admin

1. Dasbor admin
2. Kelola Program Studi — termasuk profil RIASEC enam dimensi dan mapel pendukung
3. Kelola Kriteria — bobot beserta kolom **daya pisah** terukur
4. Kelola Pernyataan RIASEC
5. Tracer Study
6. Pengaturan parameter algoritma
7. Rekap hasil tes, ekspor, dan halaman analisis sensitivitas
8. Statistik dan reliabilitas instrumen

### 4.2.5 Implementasi Instrumen RIASEC

- Instrumen: **24 butir, 4 butir per dimensi**, skala Likert **1–5**.
- Pemangkasan dari 42 butir dilakukan seimbang — tiap dimensi menyisakan tepat
  empat butir — supaya perbandingan antar dimensi tetap adil. Alasannya menghindari
  *respondent fatigue* dan *satisficing* (Krosnick, 1991).
- Skor tiap dimensi dipersentasekan terhadap rentang skala yang berlaku.
- Kode Holland tiga huruf diambil dari tiga dimensi tertinggi.

Nilai C3 dihitung sebagai korelasi Pearson antara vektor RIASEC responden dan
profil RIASEC prodi:

```
r     = Σ(a_i − ā)(b_i − b̄) / sqrt( Σ(a_i − ā)² · Σ(b_i − b̄)² ),   r ∈ [−1, 1]
nilai = (r + 1) / 2 × 100
```

Tafsirnya absolut: 100 pola minat searah sempurna, 50 tidak berkorelasi, 0
berlawanan. Profil yang seluruh dimensinya bernilai sama — termasuk responden yang
menjawab seragam di semua butir — tidak punya pola untuk dicocokkan; korelasinya
tidak terdefinisi dan dinilai **50 (netral)**, bukan 0, karena menilainya 0 berarti
menyatakan "berlawanan", klaim yang tidak didukung data.

### 4.2.6 Implementasi Algoritma CoCoSo

Petakan tahapan rumus di BAB 2 ke kelas yang mengerjakannya:

| Tahap | Rumus | Kelas |
|---|---|---|
| Penyusunan matriks keputusan | 2.1 | `DecisionMatrixBuilder` |
| Normalisasi | 2.2 / 2.3 | `CocosoService` |
| Nilai S dan P | 2.4 / 2.5 | `CocosoService` |
| Strategi agregasi Kia, Kib, Kic | 2.6–2.8 | `CocosoService` |
| Nilai akhir Ki dan peringkat | 2.9 | `CocosoService` |
| Orkestrasi dan penyimpanan | — | `RecommendationService` |
| Analisis sensitivitas | — | `SensitivityService` |
| Daya pisah kriteria | — | `CriteriaInfluenceService` |
| Reliabilitas instrumen | — | `ReliabilityService` |

Dua keputusan implementasi yang perlu disebut:

**Penskalaan bobot.** CoCoSo mencampur dua agregasi yang memperlakukan bobot
berbeda: `S_i = Σ(w_j · r_ij)` tumbuh linear terhadap total bobot, sedangkan
`P_i = Σ(r_ij ^ w_j)` tidak. Ketika Σw ≠ 1, keduanya bergeser pada skala yang tidak
sebanding sehingga `K_ib` dan `K_ic` kehilangan makna. Sistem karena itu menskalakan
bobot menjadi berjumlah 1 sebelum perhitungan. Penskalaan mempertahankan seluruh
perbandingan antar bobot, jadi maksud admin tidak berubah.

**Batas normalisasi tetap.** Kelima kriteria memakai batas tetap, bukan min–max
sampel. Alasannya dibahas di 4.5.2.

Parameter bawaan: λ = 0,5; ε = 1e-6; ambang margin = 10; ambang praktis seri = 0,50;
minimal prioritas = 3.

---

## 4.3 Hasil Perhitungan CoCoSo

Menjawab RM 2 pada sisi komputasi. **Pakai satu responden nyata sebagai studi
kasus**, tampilkan seluruh langkah, lalu buktikan perhitungan manual dan
perhitungan sistem menghasilkan angka yang sama.

### 4.3.1 Data Masukan Studi Kasus
Biodata ringkas (inisial saja), rerata rapor per semester, nilai mapel pendukung,
urutan prioritas prodi, dan skor enam dimensi RIASEC hasil kuesioner.

### 4.3.2 Matriks Keputusan (x_ij)
Tabel 19 baris (prodi) × 5 kolom (C1–C5). Jangan dipotong — normalisasi bergantung
pada seluruh alternatif.

### 4.3.3 Matriks Ternormalisasi (r_ij)
Tabel berukuran sama, disertai satu contoh perhitungan manual:

```
C3 prodi A: (nilai − 0) / (100 − 0)
```

Tegaskan pembaginya adalah batas tetap, bukan min dan max sampel.

### 4.3.4 Nilai S_i dan P_i
Tabel dua kolom, satu contoh perhitungan manual per rumus.

### 4.3.5 Nilai K_ia, K_ib, K_ic
Tabel tiga kolom, disertai contoh perhitungan manual.

### 4.3.6 Nilai Akhir K_i dan Peringkat
Tabel akhir terurut. Tandai peringkat 1, sebutkan margin terhadap peringkat 2, dan
tandai kelompok peringkat yang praktis seri.

### 4.3.7 Verifikasi Manual terhadap Keluaran Sistem
Tabel perbandingan: "hitung manual", "keluaran sistem", "selisih". Selisih harus nol
sampai batas pembulatan. Ini bukti implementasi setia pada rumus BAB 2.

### 4.3.8 Pembacaan Hasil
Bagaimana sistem menyampaikan hasilnya kepada responden: prodi rekomendasi, margin
sebagai indikator keyakinan, penanda praktis seri, dan pernyataan bahwa keluarannya
**saran, bukan keputusan**.

---

## 4.4 Hasil Pengujian

### 4.4.1 Black-Box Testing

Format tabel baku: No, Fitur, Skenario, Masukan, Hasil yang diharapkan, Hasil
aktual, Kesimpulan (Valid/Tidak).

Cakupan minimal disusun dari daftar kebutuhan fungsional 4.1.4, ditambah kasus batas
berikut — semuanya pernah bermasalah dan sudah diperbaiki, sehingga menarik untuk
ditampilkan:

| Skenario batas | Yang diharapkan |
|---|---|
| Nilai rapor ditulis dengan koma (`82,5`) | Diterima dan dinormalkan |
| Nilai rapor di luar 0–100 atau bukan angka | Ditolak dengan pesan yang benar |
| Prioritas prodi kurang dari minimal | Ditolak |
| Mengirim ulang jawaban pada sesi yang sudah selesai | Ditolak, diarahkan membuat sesi baru |
| Simpan otomatis kuesioner terputus lalu dilanjutkan | Jawaban sebelumnya tetap ada |
| Admin mencoba mengerjakan tes | Ditolak di lapisan rute |
| Calon mahasiswa membuka hasil milik orang lain | Ditolak |
| Total bobot kriteria tidak sama dengan 1 | Tetap dihitung, bobot diskalakan, perbandingan antar bobot tidak berubah |
| Bobot kriteria negatif | Ditolak |
| Skala Likert diubah ke 1–7 | Jumlah tombol pilihan ikut berubah |

### 4.4.2 Pengujian Otomatis (pendukung)

Opsional tapi menguatkan: sistem memiliki **210 pengujian PHPUnit** (Feature dan
Unit) yang seluruhnya lulus, mencakup layanan CoCoSo, RIASEC, sensitivitas,
reliabilitas, alur asesmen, dan panel admin. Sebutkan sebagai bukti pendukung
Black-Box, bukan penggantinya — BAB 3 hanya menyebut tiga metode pengujian.

### 4.4.3 Analisis Sensitivitas

Menjawab RM 3. Rancangan pengujian yang sudah berjalan di sistem:

**Skenario per sesi tes = 31**

- **Sweep λ**: 11 nilai (0,0 sampai 1,0 dengan langkah 0,1)
- **Geseran bobot**: 4 geseran (−50%, −25%, +25%, +50%) × 5 kriteria = 20 skenario

Setiap skenario dihitung ulang dari **matriks keputusan yang sudah tersimpan**,
sehingga tidak ada nilai baru yang dikarang dan hasil asli tidak tersentuh.
Indikator yang dicatat: apakah peringkat 1 berpindah (*stable*), rasio skenario
stabil, dan kriteria mana yang pergeserannya sanggup memindahkan peringkat 1
(*kriteria kritis*).

**Tabel 4.7 Hasil Analisis Sensitivitas** — angka terukur pada empat sesi nyata:

| Sesi | Margin #1 vs #2 | Skenario stabil | Kriteria kritis |
|---|---|---|---|
| TES-HMITPMWC | 6,03 | 31/31 (100%) | tidak ada |
| TES-Z5RRW0EU | 3,13 | 31/31 (100%) | tidak ada |
| TES-SAIMO01 | 3,82 | 30/31 (96,8%) | C3 |
| TES-C6QFKFGI | 0,86 | 23/31 (74,2%) | C2, C3, C4 |

Dua temuan yang perlu **dibahas**, bukan sekadar dilaporkan:

1. **Nilai λ tidak pernah menggeser juara di sesi mana pun.** Artinya pemilihan
   λ = 0,5 tidak arbitrer — hasil tidak bergantung padanya.
2. **Sesi bermargin tipis juga yang paling rapuh terhadap perubahan bobot.**
   TES-C6QFKFGI dengan margin 0,86 hanya stabil di 74,2% skenario, sementara sesi
   bermargin ≥ 3 stabil hampir sempurna. Ini memvalidasi margin sebagai penanda
   keyakinan: sistem bisa memberi tahu sendiri kapan rekomendasinya perlu dibaca
   dengan hati-hati.

Catatan jujur yang harus ditulis: angka di atas berasal dari **empat sesi**. Untuk
BAB 4 final, jalankan ulang setelah data responden UAT terkumpul.

### 4.4.4 User Acceptance Testing

Responden: perwakilan calon mahasiswa (siswa kelas XII) dan admin/pihak PMB
Poliwangi.

Susun kuesioner UAT per aspek: kemudahan antarmuka, kejelasan informasi dasbor,
kejelasan rincian perhitungan, kecepatan respons, dan kebermanfaatan rekomendasi.
Skala Likert 1–5. Sajikan tabel butir, rerata per butir, rerata per aspek, dan
persentase penerimaan, lalu kategorikan.

Tambahkan satu bagian kualitatif berisi masukan terbuka responden — ini yang
biasanya paling berguna untuk subbab saran di BAB 5.

---

## 4.5 Pembahasan

Bagian ini yang membedakan skripsi dari laporan pekerjaan. Semuanya berbasis angka
yang sudah terukur.

### 4.5.1 Daya Pisah Kriteria: Bobot Bukan Pengaruh

Temuan paling menarik dari penelitian ini. Definisikan dulu:

```
daya pisah = bobot × (nilai ternormalisasi tertinggi − terendah)
```

Sumbangan sebuah kriteria terhadap **selisih** antar alternatif adalah bobotnya
dikali rentang nilainya, bukan bobotnya saja. Kriteria yang nilainya nyaris sama
untuk semua prodi tidak memisahkan apa pun, betapapun besar bobotnya.

**Tabel 4.8 Bobot dan Daya Pisah Terukur** — rata-rata empat sesi:

| Kriteria | Bobot | Daya pisah |
|---|---|---|
| C1 Rerata rapor | 0,25 | **0,0%** |
| C2 Mapel pendukung | 0,20 | 12,6% |
| C3 RIASEC | 0,35 | **67,9%** |
| C4 Prioritas | 0,05 | 14,5% |
| C5 Tracer study | 0,15 | 5,1% |

**C1 berbobot 0,25 tetapi daya pisahnya nol.** Penyebabnya struktural, bukan cacat
yang bisa ditambal dengan menyetel bobot: nilai rerata rapor sama untuk seluruh
prodi dalam satu sesi tes, karena ia atribut siswa, bukan atribut prodi. Sebuah
kolom yang konstan tidak memisahkan apa pun.

Ini kembali ke temuan 4.1.2: SNBP memeringkat siswa untuk satu prodi, sistem ini
memeringkat prodi untuk satu siswa. C1 tetap dipertahankan karena aturan SNPMB
mewajibkan rerata rapor sebagai komponen penilaian dan nilainya bermakna secara
absolut, tetapi perannya perlu dinyatakan apa adanya. Agar C1 benar-benar ikut
memeringkat, dibutuhkan data yang belum tersedia: pembanding yang melekat pada
prodi, misalnya rerata rapor pendaftar yang biasanya diterima atau tingkat keketatan
tiap prodi. Ini bahan saran penelitian lanjutan.

Sistem menampilkan daya pisah ini secara terbuka di halaman rincian perhitungan dan
menu Kriteria, lengkap dengan peringatan untuk kriteria berbobot yang daya pisahnya
nol. Transparansi ini layak disebut sebagai kontribusi praktis.

### 4.5.2 Konsekuensi Batas Normalisasi

Kriteria berbatas sampel **selalu** terentang ke 0–1 penuh berapa pun sebaran
aslinya, sehingga daya pisahnya selalu persis sebesar bobotnya; kriteria berbatas
tetap hanya menyumbang sebesar rentang nyatanya. Mencampur keduanya membuat angka
pada kolom bobot berhenti mewakili pengaruh yang sebenarnya.

Bukti terukur sebelum perbaikan: C3 memegang 79,0% daya pisah, dan C3 sendirian —
tanpa kriteria lain sama sekali — mereproduksi pemenang di **3 dari 4 sesi**.
Setelah kelima kriteria memakai batas tetap dan C2 diperbaiki, angkanya turun ke
67,9% dan reproduksi pemenang oleh C3 sendirian turun menjadi 2 dari 4 sesi.

Dominasi C3 yang tersisa adalah **konsekuensi bobot yang memang disengaja** (0,35,
terbesar) dan sejalan dengan hasil wawancara butir 12. Yang penting, sekarang angka
itu terukur dan dapat dipertanggungjawabkan, bukan efek samping normalisasi yang
tidak disadari.

### 4.5.3 Dari Ambang Kelayakan ke Ambang Margin

Proposal merancang ambang kelayakan atas nilai K. Saat diimplementasikan, rancangan
itu terbukti tidak dapat bekerja, dan alasannya perlu dijelaskan karena ini temuan
metodologis:

`k_normal = K_i / max(K_i) × 100`, sehingga prodi peringkat 1 **selalu** bernilai
tepat 100. Diperiksa pada enam sesi tersimpan, nilai distinct-nya hanya 100. Ambang
berapa pun tidak pernah mengikat pada prodi yang direkomendasikan.

Akar persoalannya: **K tidak punya skala absolut.** K_ia, K_ib, dan K_ic ketiganya
didefinisikan relatif terhadap alternatif lain pada sesi yang sama — lewat Σ(P+S),
min S, min P, max S, dan max P pada rumus 2.6–2.8. Dua upaya menurunkan skala
absolut sudah diuji dan gagal:

- Memakai S_i sebagai skor absolut: pada 20.000 simulasi, **6,2% pasangan prodi**
  urutannya berbeda dari peringkat CoCoSo — tabel akan menampilkan peringkat 2
  dengan skor lebih tinggi daripada peringkat 1.
- Menyisipkan alternatif ideal dan anti-ideal sebagai jangkar: min P menjadi sangat
  kecil sehingga K_ib meledak, dan urutan alternatif nyata berubah pada 67%
  percobaan.

Gantinya adalah **margin peringkat 1 terhadap peringkat 2** — besaran yang bermakna
dan sebanding antar sesi. Margin lebar berarti rekomendasi meyakinkan; margin tipis
berarti dua prodi teratas praktis setara dan responden diarahkan menimbang keduanya.
Hasil sensitivitas di 4.4.3 membuktikan margin memang berkorelasi dengan ketahanan.

Ditambah penanda **peringkat praktis seri**: setiap sesi memuat 5 sampai 12 pasang
prodi bersebelahan yang selisihnya di bawah 0,5 poin dari 100 — beberapa hanya
0,003. Selisih sekecil itu berada di bawah presisi data yang membentuknya.
Menampilkannya sebagai peringkat 11 dan 12 tanpa keterangan berarti mengklaim
ketelitian yang tidak dimiliki sistem.

### 4.5.4 Posisi terhadap Penelitian Terdahulu

Kaitkan kembali ke Tabel 2.1:

- **Taufik & Rosyda (2022)** memadukan nilai mata kuliah dan RIASEC dengan Weighted
  Product untuk merekomendasikan **profesi bagi mahasiswa tingkat akhir**.
  Penelitian ini memakai RIASEC untuk merekomendasikan **program studi bagi calon
  mahasiswa**, dan mengukurnya sebagai korelasi pola terhadap profil prodi, bukan
  sekadar akumulasi skor.
- **Kusuma dkk. (2025)** menempatkan prospek kerja dan minat sebagai kriteria
  berbobot tertinggi lewat AHP-TOPSIS. Temuan penelitian ini sejalan pada arah
  minat, tetapi menambahkan catatan penting: bobot besar tidak otomatis berarti
  pengaruh besar — lihat 4.5.1.
- **Zulvianty dkk. (2024)** membuktikan CoCoSo menghasilkan peringkat yang stabil.
  Penelitian ini menguji klaim stabilitas itu secara eksplisit lewat 31 skenario
  sensitivitas per sesi, dan menemukan stabilitasnya **bergantung pada margin** —
  bukan sifat yang berlaku merata di semua kasus.

### 4.5.5 Keterbatasan

Tulis terus terang:

1. **Profil RIASEC prodi dan data tracer masih data contoh.** Ke-19 prodi masih
   memakai angka bawaan seeder, padahal C3 dan C5 bersama memegang sekitar 73% daya
   pisah keputusan. Ini pekerjaan pengumpulan data, bukan pekerjaan kode; menu
   Program Studi dan Tracer Study sudah tersedia untuk mengisinya. **Wajib
   diselesaikan sebelum BAB 4 final.**
2. **Bobot tidak berasal dari metode pembobotan formal.** Ditetapkan lewat wawancara
   akademik, bukan AHP atau Entropy, sehingga tidak ada uji konsistensi. Analisis
   sensitivitas memberi argumen ketahanan, bukan argumen penetapan.
3. **Resolusi instrumen terbatas.** Empat butir per dimensi dengan skala Likert 1–5
   hanya menghasilkan 17 tingkat nilai, bergerak dalam langkah 6,25 poin. Ini ikut
   menyumbang kedekatan nilai antar alternatif. Skala 7 titik akan memberi 25 tingkat
   dengan langkah 4,17 poin tanpa menambah jumlah butir.
4. **Nilai rapor tidak terverifikasi.** Dilaporkan sendiri oleh responden. Wajar
   untuk alat rekomendasi; perlu verifikasi bila dipakai untuk keputusan mengikat.
5. **Reliabilitas instrumen belum dapat dilaporkan.** Cronbach's alpha dihitung
   sistem per dimensi dan untuk seluruh instrumen, tetapi memerlukan sekitar 30
   responden yang menjawab lengkap. Angka dari UAT akan mengisi ini.

---

## 4.6 Gambar dan Tabel yang Perlu Disiapkan

**Gambar**

| No | Gambar | Bentuk |
|---|---|---|
| 4.1 | ERD basis data | Diagram |
| 4.2 | Halaman muka | Tangkapan layar |
| 4.3 | Formulir data dan nilai rapor | Tangkapan layar |
| 4.4 | Penyusunan prioritas prodi | Tangkapan layar |
| 4.5 | Kuesioner RIASEC | Tangkapan layar |
| 4.6 | Dasbor hasil dan grafik RIASEC | Tangkapan layar |
| 4.7 | Halaman rincian perhitungan | Tangkapan layar |
| 4.8 | Dasbor admin | Tangkapan layar |
| 4.9 | Kelola program studi dan profil RIASEC | Tangkapan layar |
| 4.10 | Kelola kriteria dengan kolom daya pisah | Tangkapan layar |
| 4.11 | Halaman analisis sensitivitas | Tangkapan layar |
| 4.12 | Grafik daya pisah kriteria | Diagram batang |
| 4.13 | Grafik stabilitas per skenario sensitivitas | Diagram |
| 4.14 | Grafik hasil UAT per aspek | Diagram batang |

**Tabel** — 4.1 temuan wawancara, 4.2 perbandingan skema kriteria, 4.3 kriteria
final, 4.4–4.5 kebutuhan fungsional, 4.6 perubahan terhadap proposal, 4.7 hasil
sensitivitas, 4.8 bobot dan daya pisah, ditambah tabel langkah perhitungan di 4.3
serta tabel Black-Box dan UAT.

---

## 4.7 Data yang Masih Harus Dikumpulkan

Urut berdasarkan yang paling menghambat penulisan:

- [ ] **Profil RIASEC enam dimensi untuk 19 prodi.** Sumber yang bisa dipakai:
      penilaian ahli (dosen/kaprodi tiap prodi) atau pemetaan dari deskripsi
      kurikulum. Metodenya perlu dijelaskan di 4.1 karena ini masukan berbobot
      terbesar.
- [ ] **Data tracer study nyata per prodi** dari bagian akademik Poliwangi — rasio
      alumni terserap kerja, idealnya selaras dengan laporan IKU 1.1.
- [ ] **Mapel pendukung resmi tiap prodi** sesuai ketentuan SNPMB tahun berjalan,
      maksimal dua mapel per prodi.
- [ ] **Responden UAT** — calon mahasiswa dan admin. Sekaligus sumber data untuk
      Cronbach's alpha (≥ 30 responden menjawab lengkap) dan untuk menjalankan ulang
      analisis sensitivitas pada jumlah sesi yang memadai.
- [ ] **Satu sesi tes terpilih** sebagai studi kasus 4.3, sebaiknya sesi berdata
      lengkap dengan margin sedang agar pembahasannya kaya.
- [ ] **Tangkapan layar final** setelah data nyata masuk — jangan memakai tangkapan
      layar berisi data contoh.

---

## 4.8 Isu untuk Dikonsultasikan ke Pembimbing

Empat hal ini sebaiknya diputuskan sebelum menulis, karena memengaruhi struktur bab
dan kemungkinan revisi BAB 3:

1. **Perubahan 9 kriteria menjadi 5.** BAB 3 direvisi agar sinkron, atau cukup
   dijelaskan sebagai temuan di 4.1.2 dan 4.1.5? Bahan pendukungnya sudah lengkap:
   wawancara butir 10 dan [research-criteria.md](research-criteria.md).
2. **Ambang kelayakan menjadi ambang margin.** Ini juga mengubah Activity Diagram
   Gambar 3.4 yang bercabang pada "Ki prodi pilihan ≥ threshold". Perlu diputuskan
   apakah diagramnya diperbarui.
3. **Pelaporan C1 yang berdaya pisah nol.** Rekomendasi: laporkan apa adanya sebagai
   temuan, karena ini justru kontribusi. Alternatifnya menghapus C1, tetapi itu
   berarti melepas keselarasan dengan aturan SNBP yang menjadi dasar 4.1.2.
4. **Pengujian otomatis 210 test.** Dimasukkan sebagai subbab pendukung 4.4.2, atau
   cukup disebut sekilas, mengingat BAB 3 hanya menyebut tiga metode pengujian.
