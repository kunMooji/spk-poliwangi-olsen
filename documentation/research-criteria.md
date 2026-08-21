# Catatan Penelitian — Perubahan Kriteria Nilai Rapor

Dokumen ini mencatat *kenapa* kriteria nilai rapor berubah dari enam mapel tetap
(C1–C6) menjadi dua kriteria (C1–C2), dan kenapa perubahan itu koreksi
implementasi, bukan penyimpangan dari proposal sempro. Ditulis untuk bahan
konsultasi ke dosen pembimbing dan sebagai jejak keputusan bagi penulisan
laporan (bab metode dan bab pembahasan).

Untuk bentuk kriteria yang berlaku sekarang, lihat [architecture.md](architecture.md#3-algoritma-cocoso)
dan kode di [CriteriaSeeder.php](../database/seeders/CriteriaSeeder.php) serta
[DecisionMatrixBuilder.php](../app/Services/DecisionMatrixBuilder.php).

---

## 1. Ringkasan perubahan

| | Skema lama | Skema baru |
|---|---|---|
| Kriteria rapor | C1–C6: nilai 6 mapel tetap (pola IPA — Matematika, Fisika, Kimia, Biologi, dst) | C1: rerata rapor seluruh mapel · C2: rerata mapel pendukung prodi |
| Total kriteria | 9 | 5 |
| Bobot blok rapor | 0.45 terbagi rata ke 6 mapel | 0.45 = C1 0.25 + C2 0.20 (55,6% : 44,4%) |
| Rujukan aturan | Ditetapkan sendiri, tanpa acuan formal | Mengikuti struktur penilaian SNBP 2026 |

Kategori kriteria (rapor, RIASEC, prioritas prodi, prospek kerja) tetap empat.
Yang berubah hanya jumlah kriteria *di dalam* kategori rapor.

---

## 2. Kenapa berubah

### 2.1 Menepati janji sempro sendiri

Proposal (hal. 22) menyatakan keenam mapel ditetapkan "berdasarkan syarat
pendaftaran". Syarat pendaftaran nasional yang dimaksud — SNBP 2026 — pada
kenyataannya tidak menetapkan enam mapel tetap. Aturannya dua komponen:
rerata seluruh mapel (bobot ≥ 50%) dan paling banyak dua mapel pendukung
program studi (bobot ≤ 50%). Skema lama menyimpang dari rujukan yang
diklaimnya sendiri. Rasionalnya tidak berubah — hanya operasionalisasinya
yang dikoreksi agar sesuai rujukan.

### 2.2 Skema lama tidak valid untuk populasi target

Tabel 3.1 proposal menyebut sosialisasi ke SMA **dan SMK**. Tapi objek
penelitian adalah politeknik, yang pasar utamanya justru lulusan SMK.
Masalahnya:

- Siswa jurusan IPS/Bahasa tidak menempuh Fisika, Kimia, atau Biologi.
- Siswa SMK punya mata pelajaran konsentrasi keahlian yang sama sekali tidak
  terwakili oleh keenam mapel itu.

Memaksa kelompok ini mengisi nilai mapel yang tidak mereka tempuh berarti
datanya dikarang atau diisi nol — bukan soal kerapian data, tapi cacat
validitas pada instrumen pengumpulan data itu sendiri.

### 2.3 "Relevansi mapel" tidak punya rujukan

Skema lama memerlukan bobot relevansi 0–1 per mapel per prodi, ditetapkan
tanpa acuan formal — subjektif. Ini bertentangan dengan klaim rumusan masalah
(RM #3) bahwa sistem bebas dari bias subjektivitas manual. Niat awalnya —
membedakan prodi berdasarkan mapel yang relevan — tetap dipertahankan, tapi
lewat mekanisme yang berdasar regulasi (mapel pendukung SNBP), bukan angka
yang ditentukan sendiri.

### 2.4 Temuan matematis saat implementasi

Ini bagian yang mendorong perubahan dari sisi teknis, ditemukan saat
mengerjakan `DecisionMatrixBuilder`, bukan diasumsikan sejak awal:

Pada rumus lama `x_ij = nilai_rapor × relevansi_mapel`, nilai rapor menjadi
faktor pengali yang **sama untuk seluruh mapel dalam satu baris (satu
responden)**. Begitu masuk normalisasi min-max CoCoSo, faktor pengali konstan
itu tercoret secara aljabar — akibatnya siswa dengan rapor 95 dan siswa
dengan rapor 40 bisa memperoleh peringkat rekomendasi yang identik, karena
yang tersisa untuk membedakan hanya pola relevansi, bukan levelnya.

Solusinya bukan menambal rumus lama, tapi menyadari akar masalahnya: **SNBP
memeringkat siswa untuk satu prodi, sedangkan sistem ini memeringkat prodi
untuk satu siswa** — arah pemeringkatannya terbalik. Konsekuensinya:

- C1 (rerata rapor) memang **konstan** untuk semua prodi dalam satu sesi tes
  — ia atribut siswa, bukan atribut prodi, sehingga secara matematis tidak
  bisa membedakan peringkat antar-prodi. Ia tetap dipertahankan sebagai
  kriteria karena kontribusinya nyata secara nilai absolut (lihat batas
  normalisasi tetap di §3), bukan karena mengubah urutan.
- C2 (mapel pendukung) adalah satu-satunya bagian blok rapor yang benar-benar
  membedakan nilai antar prodi, karena tiap prodi ditautkan ke mapel
  pendukungnya sendiri.

Temuan ini dilaporkan terbuka sebagai bagian dari hasil penelitian — bukan
disembunyikan atau ditambal secara diam-diam.

### 2.5 Tetap dalam koridor RM #1

Tujuan penelitian #1 adalah menganalisis kebutuhan fungsional sistem.
Jawaban atas analisis itu wajar ditemukan selama proses penelitian
berlangsung, bukan diasumsikan benar sejak bab 1. Koreksi ini adalah hasil
mengerjakan RM #1, bukan penyimpangan darinya.

---

## 3. Konsekuensi teknis

Karena C1 bernilai konstan per sesi tes, batas normalisasi min-max sampel
bawaan CoCoSo tidak cocok untuknya (rentang sampel = 0, semua alternatif
dapat skor 1.0, rapor 95 tidak terbedakan dari rapor 40). Solusinya: C1 dan
C2 memakai **batas normalisasi tetap** 0–100 (skala rapor asli), bukan
batas dari rentang sampel. Rincian dan kriteria lain yang memakai batas tetap
(termasuk C5/tracer) ada di komentar `DecisionMatrixBuilder::FIXED_BOUNDS`.

---

## 4. Yang tidak berubah

- Novelty penelitian (integrasi RIASEC + CoCoSo) — utuh.
- RM #2 dan RM #3 — metode CoCoSo dan analisis sensitivitas sama persis.
- Empat kategori kriteria: rapor, RIASEC, prioritas prodi, prospek kerja.
- Komposisi bobot hasil wawancara akademik: rapor 0.45, RIASEC 0.35, tracer
  0.15, minat 0.05 — hanya pembagian di dalam blok rapor yang berubah
  (0.25 : 0.20 = 55,6% : 44,4%, memenuhi batas SNBP rerata ≥ 50%).
