# Arsitektur Sistem

Sistem Pendukung Keputusan rekomendasi program studi Politeknik Negeri Banyuwangi.
Menggabungkan profil kepribadian **RIASEC** dengan metode perangkingan
**CoCoSo** (Combined Compromise Solution) atas sembilan kriteria.

Dokumen ini menjelaskan *bentuk* dan *alasan* rancangan. Untuk peta berkas dan
cara menambah sesuatu, lihat [code.md](code.md).

---

## 1. Gambaran Umum

### Masalah yang diselesaikan

Calon mahasiswa memilih program studi berdasarkan persepsi, bukan berdasarkan
kecocokan yang terukur. Sistem ini memberi mereka pembanding: peringkat prodi
yang disusun dari nilai rapor, minat bakat, urutan pilihan sendiri, dan data
serapan kerja alumni.

Keluarannya **saran, bukan keputusan**. Keputusan akhir tetap di tangan calon
mahasiswa — hal ini dinyatakan eksplisit di halaman hasil.

### Dua aktor

| Aktor | Peran | Tidak boleh |
|---|---|---|
| **Calon mahasiswa** (`users.role = mahasiswa`) | Mengerjakan tes, melihat hasil dan riwayat miliknya | Membuka panel admin, melihat tes orang lain |
| **Admin** (`users.role = admin`) | Mengelola data master, memantau seluruh hasil tes | **Mengerjakan tes** |

Pemisahan ini ditegakkan di lapisan rute, bukan sekadar disembunyikan dari menu.

### Tumpukan teknologi

| Lapisan | Pilihan |
|---|---|
| Kerangka kerja | Laravel 11.55 (PHP 8.2+) |
| Basis data | MySQL (`spk_poliwangi`), pengujian di `spk_poliwangi_test` |
| Autentikasi | Laravel Breeze (Blade) |
| Antarmuka | Blade + Tailwind CSS + Alpine.js, mendukung mode gelap |
| Grafik | Chart.js via npm |
| Aset | Vite |
| Pengujian | PHPUnit |

Bahasa antarmuka, komentar kode, dan pesan galat: **Indonesia**.
Nama tabel, kolom, kelas, dan metode: **Inggris**, mengikuti konvensi Laravel.

---

## 2. Model Data

### Peta relasi

```
users ──< assessments ──< assessment_priorities >── study_programs
                      ├─< assessment_answers    >── riasec_questions
                      └─< assessment_results    >── study_programs

criteria    (mandiri — definisi kriteria C1..C9)
settings    (mandiri — parameter algoritma)
```

### Dua kelompok tabel

**Data master** — dikelola admin, memengaruhi tes yang dikerjakan sesudahnya:

| Tabel | Isi |
|---|---|
| `study_programs` | Alternatif keputusan. Identitas prodi, bobot relevansi 6 mapel, profil RIASEC 6 dimensi, data tracer study |
| `criteria` | Definisi C1..C9: kode, nama, bobot, jenis benefit/cost, sumber nilai |
| `riasec_questions` | Butir pernyataan kuesioner beserta dimensinya |
| `settings` | Parameter algoritma: threshold, lambda, epsilon, skala Likert, jumlah minimal prioritas |

**Data transaksi** — jejak satu sesi tes, tidak pernah diubah admin:

| Tabel | Isi |
|---|---|
| `assessments` | Biodata, nilai rapor, hasil profil RIASEC, rekomendasi, **snapshot parameter** |
| `assessment_priorities` | Urutan pilihan prodi calon mahasiswa |
| `assessment_answers` | Jawaban Likert per butir |
| `assessment_results` | Satu baris per prodi: `matrix`, `normalized`, S, P, K_a, K_b, K_c, K, K ternormalisasi, peringkat |

### Status sesi tes

```
draft ──> questionnaire ──> completed
```

`draft` praktis tidak terpakai: sesi langsung masuk `questionnaire` setelah
biodata tersimpan. `completed` diberikan hanya setelah perhitungan berhasil.

---

## 3. Prinsip Rancangan

Empat prinsip berikut menjelaskan sebagian besar keputusan teknis di sistem ini.

### 3.1 Hasil yang sudah selesai bersifat beku

Saat perhitungan berhasil, parameter yang dipakai **disalin** ke baris
`assessments`: `weights_snapshot`, `threshold_used`, `threshold_mode_used`,
`lambda_used`.

Konsekuensinya: admin boleh mengubah bobot kapan saja tanpa merusak hasil yang
sudah terbit. Tes lama tetap dapat dipertanggungjawabkan karena parameternya
melekat pada dirinya sendiri, bukan dibaca ulang dari tabel `criteria`.

Prinsip ini juga dipakai halaman rincian perhitungan dan analisis sensitivitas:
keduanya membaca angka tersimpan, tidak pernah menghitung ulang dari data master.

### 3.2 Kriteria bersifat data-driven

`criteria.source` menentukan dari mana nilai x_ij diambil:

| `source` | Nilai x_ij | Kriteria bawaan |
|---|---|---|
| `subject_score` | `nilai_rapor[subject] × relevansi_prodi[subject]` | C1–C6 |
| `riasec` | Cosine similarity vektor RIASEC siswa vs prodi, × 100 | C7 |
| `priority` | Konversi urutan prioritas menjadi skor 0–100 | C8 |
| `tracer` | `study_programs.employment_rate` (0.00–1.00) | C9 |

Admin dapat menambah, menonaktifkan, atau mengubah bobot kriteria **tanpa
menyentuh kode perhitungan**, selama `source`-nya salah satu dari empat di atas.

### 3.3 Data yang sudah terpakai tidak dihapus

Kunci asing `assessment_results.study_program_id` dan
`assessment_answers.riasec_question_id` memakai `cascadeOnDelete`. Menghapus
sebuah prodi akan ikut menghapus baris hasil tes lama secara diam-diam.

Karena itu penghapusan diblokir bila datanya sudah terpakai; admin diarahkan
menonaktifkan (`is_active = false`) agar data lama utuh sementara tes baru tidak
lagi memakainya.

### 3.4 Lapisan perhitungan tidak menyentuh basis data

`RiasecService`, `CocosoService`, `ExplanationService`, dan `SensitivityService`
menerima dan mengembalikan array biasa. Tidak ada query, tidak ada model
Eloquent. Karena itu semuanya dapat diuji dengan angka langsung
(`PHPUnit\Framework\TestCase`, tanpa `RefreshDatabase`) dan tetap benar meski
sumber datanya berganti.

`DecisionMatrixBuilder` dan `RecommendationService` adalah lapisan yang
menjembatani keduanya dengan basis data.

---

## 4. Alur Perhitungan

```
Biodata + nilai rapor + urutan prioritas
                │
Kuesioner RIASEC (30 butir, Likert 1–5)
                │
                ▼
      RecommendationService::calculate()          ← dalam DB::transaction
                │
    ┌───────────┼────────────────────────────┐
    ▼           ▼                            ▼
RiasecService   DecisionMatrixBuilder    CocosoService
skor → persen   x_ij per prodi           normalisasi → S, P → K
kode Holland    per kriteria             peringkat
    │           │                            │
    └───────────┴────────────┬───────────────┘
                             ▼
        assessment_results + snapshot parameter
                             │
                             ▼
              Aturan rekomendasi (threshold)
```

### Tahapan CoCoSo

1. **Normalisasi** min–max per kolom kriteria, dibedakan benefit dan cost.
2. **S_i** = Σ_j (w_j · r_ij) — *weighted sum*.
3. **P_i** = Σ_j (r_ij ^ w_j) — *weighted product*.
4. **Tiga strategi kompromi**:
   - K_ia = (P_i + S_i) / Σ(P_i + S_i)
   - K_ib = S_i / min(S) + P_i / min(P)
   - K_ic = (λ·S_i + (1−λ)·P_i) / (λ·max(S) + (1−λ)·max(P))
5. **K_i** = (K_ia · K_ib · K_ic)^(1/3) + (K_ia + K_ib + K_ic)/3

### Aturan penetapan rekomendasi

Bila prodi prioritas pertama mencapai ambang batas, prodi itulah yang
direkomendasikan — menghormati pilihan calon mahasiswa. Bila tidak, sistem
mengambil prodi dengan K tertinggi.

Ketika seluruh prodi berada di bawah ambang batas dan prioritas pertama kebetulan
menempati peringkat 1, prodi tersebut tetap yang terbaik yang tersedia sehingga
tetap direkomendasikan.

---

## 5. Tiga Koreksi Matematis Wajib

Ketiganya bukan hiasan: tanpa salah satunya perhitungan gagal atau menyesatkan.

### 5.1 Kolom konstan

**Masalah.** Nilai rapor calon mahasiswa sama untuk semua prodi. Kolom C1–C6
menjadi konstan, `max − min = 0`, normalisasi membagi nol.

**Penyelesaian.** Bobot relevansi mapel per prodi: `x_ij = nilai_rapor_j ×
relevansi_ij`. Informatika memberi bobot besar pada Matematika, Teknik Sipil pada
Fisika — kolomnya jadi bervariasi dan normalisasi bermakna.

**Pengaman tetap ada.** Bila `max == min`, seluruh `r_ij` diberi nilai `1.0`
karena kriteria tersebut memang tidak membedakan apa pun.

> **Catatan rancangan.** Perbedaan antar prodi sengaja ditaruh pada **nilai
> matriks (x_ij)**, bukan pada **bobot kriteria (w_j)**. Bobot per alternatif
> akan membuat setiap prodi dinilai dengan penggaris berbeda sehingga tidak lagi
> sebanding, dan menyimpang dari rumusan baku CoCoSo yang mensyaratkan satu
> vektor bobot untuk seluruh alternatif.

### 5.2 Pembagian nol pada K_ib

**Masalah.** Alternatif terburuk bisa ternormalisasi ke 0 di semua kriteria,
sehingga `S_i = P_i = 0` dan `S_i / min(S)` meledak.

**Penyelesaian.** Epsilon floor: `r_ij = max(r_ij, ε)`, dengan ε bawaan `1e-6`
tersimpan di tabel `settings`. Ditambah `DENOMINATOR_FLOOR = 1e-12` sebagai
pengaman terakhir pada seluruh penyebut.

### 5.3 Skala K_i bukan 0–1

**Masalah.** K_ib selalu ≥ 2, sehingga K_i berada di rentang ~1–5 dan bergeser
mengikuti jumlah alternatif. Ambang batas tidak dapat ditetapkan secara stabil.

**Penyelesaian.** Simpan keduanya: `k_value` (mentah, untuk lampiran laporan) dan
`k_normal = K_i / max(K_i) × 100`. Ambang batas dibandingkan terhadap `k_normal`.
Pengaturan `threshold_mode` (`normal` | `raw`) tersedia bila pembimbing meminta
versi mentah.

---

## 6. Model Otorisasi

Tiga lapis, masing-masing dengan tugas berbeda.

### Lapis 1 — Middleware peran

| Alias | Kelas | Perilaku |
|---|---|---|
| `admin` | `EnsureUserIsAdmin` | Bukan admin → 403 |
| `mahasiswa` | `EnsureUserIsMahasiswa` | Admin → dialihkan ke `admin.dashboard` dengan pesan penjelas |

Admin dialihkan, bukan ditolak, karena membuka `/tes` adalah kekeliruan navigasi
yang wajar — bukan pelanggaran.

### Lapis 2 — Policy

`AssessmentPolicy` mengatur akses ke sesi tes perorangan:

| Kemampuan | Pemilik | Admin |
|---|---|---|
| `view` | ✅ | ✅ |
| `update` | ✅ | ❌ |
| `delete` | ✅ | ✅ |

Admin sengaja tidak boleh `update`: data tes tidak pernah diubah pengelola supaya
hasil perhitungan tetap dapat dipertanggungjawabkan.

### Lapis 3 — Peran tidak dapat dipilih sendiri

`RegisteredUserController::store()` menetapkan `role = mahasiswa` secara eksplisit
— tidak diambil dari masukan dan tidak menyandarkan diri pada nilai bawaan kolom.
Kiriman `role=admin` pada formulir pendaftaran tidak berpengaruh.

**Akun administrator dibuat langsung di basis data** melalui `UserSeeder` atau
SQL. Tidak ada jalur pembuatan admin lewat antarmuka, sehingga permukaan serangan
untuk peningkatan hak akses tidak ada sama sekali.

### Lapis 4 — Cakupan query

Riwayat calon mahasiswa selalu dibaca lewat `$request->user()->assessments()`,
bukan `Assessment::all()` yang disaring belakangan. Kebocoran data tidak mungkin
terjadi karena query-nya memang tidak pernah menyentuh milik orang lain.

### Penempatan rute

Rute lembar hasil (`assessments.result`, `assessments.calculation`) sengaja
diletakkan **di luar** grup `mahasiswa`, dijaga policy saja. Bila ikut dikunci,
admin tidak akan bisa membuka lembar hasil calon mahasiswa untuk keperluan
rekapitulasi.

---

## 7. Peta Rute

### Publik

| Rute | Keterangan |
|---|---|
| `GET /` | Halaman depan — `HomeController`, angka dibaca dari data master |
| `routes/auth.php` | Masuk, daftar, pemulihan kata sandi (Breeze). Pendaftaran khusus calon mahasiswa |

### Terautentikasi

| Rute | Nama | Keterangan |
|---|---|---|
| `GET /dashboard` | `dashboard` | Admin dialihkan ke `admin.dashboard` |
| `GET/PATCH/DELETE /profile` | `profile.*` | Profil pengguna |
| `GET /tes/{id}/hasil` | `assessments.result` | Pemilik atau admin |
| `GET /tes/{id}/perhitungan` | `assessments.calculation` | Pemilik atau admin |

### Calon mahasiswa (`middleware: mahasiswa`)

| Rute | Nama |
|---|---|
| `GET /tes` | `assessments.index` |
| `GET /tes/mulai` | `assessments.create` |
| `POST /tes` | `assessments.store` |
| `DELETE /tes/{id}` | `assessments.destroy` |
| `GET/POST /tes/{id}/kuesioner` | `assessments.questionnaire`, `assessments.answers.store` |

### Admin (`prefix: admin`, `middleware: auth + admin`)

| Rute | Nama |
|---|---|
| `GET /admin` | `admin.dashboard` |
| `resource /admin/prodi` | `admin.study-programs.*` |
| `resource /admin/kriteria` | `admin.criteria.*` |
| `resource /admin/pernyataan` | `admin.questions.*` |
| `GET/PUT /admin/tracer` | `admin.tracer.*` |
| `GET/PUT /admin/pengaturan` | `admin.settings.*` |
| `GET /admin/statistik` | `admin.statistics` |
| `GET /admin/rekap` | `admin.recap.index` |
| `GET /admin/rekap/{id}` | `admin.recap.show` |
| `GET /admin/rekap/{id}/sensitivitas` | `admin.recap.sensitivity` |
| `DELETE /admin/rekap/{id}` | `admin.recap.destroy` |

Seluruh URL berbahasa Indonesia; nama rute berbahasa Inggris.

---

## 8. Analisis Sensitivitas

Menjawab dua pertanyaan yang selalu muncul pada sistem pendukung keputusan.

**Apakah pilihan λ menentukan hasil?** λ disapu dari 0 sampai 1 dalam 11
skenario. Bila peringkat 1 bertahan di semuanya, λ = 0.5 bukan penentu.

**Kriteria mana yang paling menentukan?** Bobot tiap kriteria digeser −50%,
−25%, +25%, +50%. Bobot kriteria lain diskalakan ulang secara proporsional agar
totalnya tetap — tanpa itu yang berubah bukan hanya kepentingan relatif kriteria,
melainkan juga skala S_i, sehingga skenarionya tidak sebanding dengan
perhitungan asli.

Untuk 9 kriteria: 11 + 36 = 47 skenario per sesi tes. Keluarannya rasio skenario
stabil, ketahanan terhadap λ, dan daftar kriteria kritis — kriteria yang
pergeseran bobotnya sanggup memindahkan peringkat 1.

Seluruhnya memakai matriks tersimpan pada sesi tes tersebut, sehingga hasil
aslinya tidak pernah tersentuh.

---

## 9. Penjelasan Hasil

Sistem pendukung keputusan yang hanya mengumumkan pemenang tidak benar-benar
mendukung keputusan. `ExplanationService` menerjemahkan angka menjadi alasan:

- **Kontribusi per kriteria** — `w_j × r_ij`, diurutkan dari penyumbang terbesar,
  ditandai kuat (r ≥ 0.70), sedang, atau lemah (r ≤ 0.30).
- **Perbandingan dengan pilihan pertama** — selisih kontribusi per kriteria,
  diurutkan dari penyebab terbesar, muncul hanya bila rekomendasi berbeda dari
  pilihan pertama.

Tidak ada kolom basis data baru: semuanya dihitung dari `assessment_results.normalized`
dan `assessments.weights_snapshot` yang memang sudah tersimpan.

---

## 10. Statistik Institusional

Melayani pertanyaan kampus, bukan pertanyaan perorangan.

Metrik paling bermakna adalah **kesenjangan minat versus rekomendasi**: berapa
kali sebuah prodi dijadikan pilihan pertama dibandingkan berapa kali prodi itu
benar-benar direkomendasikan. Selisih positif besar menandakan prodi banyak
diminati namun jarang cocok — kesenjangan antara persepsi calon mahasiswa dan
profil yang sesungguhnya dituntut prodi tersebut.

Selebihnya: asal sekolah teratas beserta rata-rata kecocokannya, rata-rata nilai
rapor per mata pelajaran, sebaran tipe RIASEC, jurusan sekolah, jenis kelamin,
dan tren tes per bulan.

> **Ketergantungan.** Tren bulanan memakai `DATE_FORMAT` sehingga terikat MySQL.

---

## 11. Penetapan Bobot

Bobot bawaan C1–C9:

| C1 | C2 | C3 | C4 | C5 | C6 | C7 | C8 | C9 | Σ |
|---|---|---|---|---|---|---|---|---|---|
| .10 | .08 | .08 | .08 | .07 | .09 | .20 | .15 | .15 | **1.00** |

Angka ini **bukan** hasil metode pembobotan formal seperti AHP atau Entropy.
Penetapannya dilakukan melalui **wawancara dengan pihak akademik Politeknik
Negeri Banyuwangi**. Dasar penetapan tiap kriteria dicatat pada kolom
`criteria.description` agar sumbernya melekat pada datanya.

Sistem tidak memaksa Σ bobot = 1, namun memberi peringatan mencolok di halaman
Kriteria dan dasbor admin bila totalnya menyimpang. Perhitungan hanya ditolak
bila total bobot benar-benar nol.

---

## 12. Batasan yang Diketahui

| Batasan | Keterangan |
|---|---|
| Belum ada ekspor CSV | Direncanakan, belum dikerjakan |
| Belum ada impor CSV | Data tracer study dimasukkan manual lewat formulir |
| Belum ada manajemen akun | Admin tidak dapat mereset kata sandi atau menonaktifkan akun calon mahasiswa |
| Belum ada periode/gelombang | Seluruh sesi tes menumpuk dalam satu kumpulan |
| Belum ada catatan perubahan | Perubahan bobot tidak terekam pelakunya |
| Kuesioner tidak tersimpan sebagian | Jawaban baru tersimpan ketika seluruh butir dikirim |
| Terikat MySQL | Statistik memakai `DATE_FORMAT`; `pdo_sqlite` tidak aktif di lingkungan pengembangan |
