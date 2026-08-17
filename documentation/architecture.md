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
           ▲
periods ───┘  (gelombang PMB — penanda sesi tes)

criteria       (mandiri — definisi kriteria C1..C5)
settings       (mandiri — parameter algoritma)
activity_logs  (mandiri — jejak perubahan data master)
```

### Dua kelompok tabel

**Data master** — dikelola admin, memengaruhi tes yang dikerjakan sesudahnya:

| Tabel | Isi |
|---|---|
| `study_programs` | Alternatif keputusan. Identitas prodi, profil RIASEC 6 dimensi, data tracer study |
| `study_program_subjects` | Mata pelajaran pendukung tiap prodi — paling banyak dua, sesuai aturan SNBP |
| `subjects` | Master mata pelajaran rapor: nama, jenjang SMA/SMK, kelompok peminatan atau rumpun keahlian |
| `criteria` | Definisi C1..C5: kode, nama, bobot, jenis benefit/cost, sumber nilai |
| `riasec_questions` | Butir pernyataan kuesioner beserta dimensinya |
| `settings` | Parameter algoritma: threshold, lambda, epsilon, skala Likert, jumlah minimal prioritas |
| `periods` | Gelombang PMB: nama, tahun akademik, rentang tanggal, penanda aktif |

**Data transaksi** — jejak satu sesi tes, tidak pernah diubah admin:

| Tabel | Isi |
|---|---|
| `assessments` | Biodata, rerata rapor, hasil profil RIASEC, rekomendasi, **snapshot parameter**, penanda gelombang |
| `assessment_rapor_semesters` | Rerata nilai seluruh mapel per semester (1–5) — komponen pertama SNBP |
| `assessment_subject_scores` | Nilai pada mapel pendukung; `null` berarti mapel tidak ditempuh — komponen kedua SNBP |
| `assessment_priorities` | Urutan pilihan prodi calon mahasiswa |
| `assessment_answers` | Jawaban Likert per butir — ditulis bertahap oleh simpan otomatis |
| `assessment_results` | Satu baris per prodi: `matrix`, `normalized`, S, P, K_a, K_b, K_c, K, K ternormalisasi, peringkat |

**Data jejak** — hanya ditulis, tidak pernah diubah:

| Tabel | Isi |
|---|---|
| `activity_logs` | Siapa mengubah data master apa, kapan, dari nilai berapa ke berapa |

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
| `rapor_average` | `assessments.rapor_average` — sama untuk seluruh prodi | C1 |
| `support_subject` | Rerata nilai siswa pada mapel pendukung prodi terkait | C2 |
| `riasec` | Cosine similarity vektor RIASEC siswa vs prodi, × 100 | C3 |
| `priority` | Konversi urutan prioritas menjadi skor 0–100 | C4 |
| `tracer` | `study_programs.employment_rate` (0.00–1.00) | C5 |

Admin dapat menambah, menonaktifkan, atau mengubah bobot kriteria **tanpa
menyentuh kode perhitungan**, selama `source`-nya salah satu dari lima di atas.

Mapel pendukung yang nilainya tidak dimiliki siswa — misalnya peserta didik IPS
yang tidak menempuh Fisika — jatuh ke `rapor_average`, bukan ke nol. Nol akan
menjatuhkan peringkat prodi seolah siswa benar-benar gagal di mapel itu,
padahal ia hanya tidak mengambilnya. Prodi yang belum ditetapkan mapel
pendukungnya diperlakukan sama.

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
   Batas `min_j`/`max_j` diambil dari sampel alternatif, **kecuali** kriteria
   berskala absolut yang memakai batas teoretis tetap — lihat 5.1.
2. **S_i** = Σ_j (w_j · r_ij) — *weighted sum*.
3. **P_i** = Σ_j (r_ij ^ w_j) — *weighted product*.
4. **Tiga strategi kompromi**:
   - K_ia = (P_i + S_i) / Σ(P_i + S_i)
   - K_ib = S_i / min(S) + P_i / min(P)
   - K_ic = (λ·S_i + (1−λ)·P_i) / (λ·max(S) + (1−λ)·max(P))
5. **K_i** = (K_ia · K_ib · K_ic)^(1/3) + (K_ia + K_ib + K_ic)/3

### Aturan penetapan rekomendasi

Rekomendasi adalah prodi dengan **K tertinggi**, tanpa pengecualian.

Urutan prioritas calon mahasiswa **tidak** dipakai untuk menimpa hasil. Minat
sudah diperhitungkan sebagai kriteria C4 di dalam matriks keputusan; memakainya
sekali lagi sebagai aturan penimpa berarti menghitung minat dua kali, dan
membuat prodi pilihan pertama nyaris selalu terpilih terlepas dari nilai rapor
maupun kesesuaian kepribadiannya.

`matches_preference` menandai apakah pilihan pertama kebetulan menempati
peringkat 1 — murni informasi pada lembar hasil, bukan aturan keputusan.

Pengaturan `threshold` dipertahankan sebagai **ambang kelayakan** yang
ditampilkan pada lembar hasil, bukan penentu prodi mana yang direkomendasikan.

### Pembagian bobot kriteria

Nilai rapor **0.45** · RIASEC **0.35** · tracer study **0.15** · minat **0.05**.

Blok nilai rapor 0.45 dibagi mengikuti aturan SNBP: komponen pertama (C1, rerata
seluruh mapel) **0.25** atau 55,6% dari blok — memenuhi syarat paling sedikit
50% — dan komponen kedua (C2, mapel pendukung) **0.20** atau 44,4%, di bawah
batas 50%. Rasio itu dihitung terhadap **blok rapor saja**, bukan terhadap total
seluruh kriteria: RIASEC, prioritas minat, dan tracer study adalah tambahan
sistem ini yang tidak dikenal SNBP. Halaman Kriteria menampilkan peringatan bila
rasio tersebut jatuh di bawah 50%.

C4 (urutan prioritas) sengaja memegang bobot terkecil. Berbeda dari empat
kriteria lain, nilainya bukan atribut yang melekat pada program studi melainkan
pernyataan keinginan responden itu sendiri. Bobot besar membuat sistem
mengembalikan pilihan yang sudah disebutkan calon mahasiswa alih-alih memberi
informasi baru — dan itu meniadakan gunanya sebagai alat bantu keputusan. Pada
0.05, C4 hanya sanggup membalikkan keputusan yang sudah nyaris seri: berperan
sebagai pemecah seri, bukan penggerak hasil.

Minat tetap tertimbang besar, tetapi lewat C3 yang mengukurnya dengan instrumen
RIASEC alih-alih menanyakan preferensi secara langsung.

C3 ditahan di 0.35 dan tidak dinaikkan lebih jauh: menaruh 0.40–0.45 pada satu
kuesioner laporan-diri membuat hampir separuh keputusan bertumpu pada satu
instrumen. Dengan pembagian ini tidak ada kriteria tunggal yang melewati 0.35.

---

## 5. Tiga Koreksi Matematis Wajib

Ketiganya bukan hiasan: tanpa salah satunya perhitungan gagal atau menyesatkan.

### 5.1 Kolom konstan pada nilai rapor

Skema penilaian rapor mengikuti SNBP: **komponen pertama** berupa rerata nilai
seluruh mata pelajaran pada semua semester kecuali semester terakhir (C1), dan
**komponen kedua** berupa nilai pada paling banyak dua mata pelajaran pendukung
program studi yang dituju (C2).

**Sifat C1 yang perlu disadari.** Rerata rapor adalah atribut calon mahasiswa,
bukan atribut program studi. Nilainya karena itu **sama persis untuk seluruh
alternatif** dalam satu sesi tes: kolomnya konstan dan `max − min = 0`.

Konsekuensinya pada CoCoSo: C1 menambah konstanta yang sama ke `S_i` dan `P_i`
setiap alternatif, sehingga **tidak mengubah urutan peringkat**. Ini bukan cacat
yang perlu diperbaiki, melainkan konsekuensi logis dari perbedaan arah
pemeringkatan — SNBP memeringkat *siswa* untuk satu prodi, sedangkan sistem ini
memeringkat *prodi* untuk satu siswa. Yang membedakan alternatif adalah C2,
karena tiap prodi menetapkan mapel pendukung yang berbeda.

Upaya "memperbaiki" C1 dengan mengalikannya dengan faktor relevansi per prodi
justru keliru dua kali: SNBP tidak mengenal konsep relevansi kontinu, dan
variasi yang dihasilkannya seluruhnya berasal dari faktor relevansi, bukan dari
nilai rapor. Di bawah normalisasi min–max berbasis sampel, nilai rapor sebagai
konstanta pengali `c` bahkan tercoret secara aljabar:

```
(c·v_i − c·v_min) / (c·v_max − c·v_min) = (v_i − v_min) / (v_max − v_min)
```

**Yang tetap wajib: batas normalisasi tetap.** Karena C1 konstan, normalisasi
berbasis sampel akan memberi `1.0` kepada seluruh alternatif — rapor 90 tak
terbedakan dari rapor 55, dan bobot 0.25 terbuang percuma. Kriteria yang
besarannya bermakna absolut karena itu dinormalisasi terhadap **skala bakunya**:

| Kriteria | Batas | Alasan |
|---|---|---|
| C1 `rapor_average` | 0 – 100 | kolom konstan; tanpa batas tetap seluruh alternatif bernilai 1.0 dan rapor tidak terbaca sama sekali |
| C2 `support_subject` | 0 – 100 | rerata nilai mapel, berskala sama dengan rapor aslinya |
| C5 `tracer` | 0.00 – 1.00 | rasio keterserapan bermakna apa adanya — 0.80 berarti 80% alumni terserap |

C3 (`riasec`) dan C4 (`priority`) tetap memakai min–max sampel. Cosine similarity
tidak punya tafsir absolut — 69 bukan berarti "69% cocok" — dan urutan prioritas
hanya bermakna relatif terhadap pilihan lain pada sesi yang sama; untuk keduanya
perbandingan antar alternatif justru tafsir yang benar.

Tanpa batas tetap pada C5, selisih nyata yang kecil (mis. 0.78–0.89) direntangkan
menjadi 0–1 penuh, sehingga beda 11 poin diperlakukan seolah beda terburuk lawan
terbaik dan C5 mendominasi hasil jauh melebihi bobotnya.

**Pengaman tetap ada.** Bila `max == min`, seluruh `r_ij` diberi nilai `1.0`
karena kriteria tersebut memang tidak membedakan apa pun. Nilai ternormalisasi
juga dijepit ke rentang 0–1, karena batas tetap tidak dijamin melingkupi seluruh
nilai sebagaimana batas sampel.

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
mengikuti jumlah alternatif. Nilainya tidak dapat dibandingkan antar sesi tes.

**Penyelesaian.** Simpan keduanya: `k_value` (mentah, untuk lampiran laporan) dan
`k_normal = K_i / max(K_i) × 100` sebagai angka yang ditampilkan. Ambang
kelayakan dibandingkan terhadap `k_normal`; pengaturan `threshold_mode`
(`normal` | `raw`) tersedia bila pembimbing meminta versi mentah.

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

Panel **Akun Pengguna** menegakkan hal yang sama: akun admin hanya ditampilkan,
tidak dapat dinonaktifkan maupun disetel ulang kata sandinya dari sana, dan admin
tidak dapat menyunting akunnya sendiri lewat panel itu.

### Lapis 4 — Akun aktif

`users.is_active` diperiksa di **dua** tempat, dan keduanya diperlukan:

| Tempat | Menangani |
|---|---|
| `LoginRequest::authenticate()` | Percobaan masuk baru. Diperiksa **setelah** kredensial terbukti benar, karena akun nonaktif tetap memiliki kata sandi yang sah |
| `EnsureAccountIsActive` (middleware `web`) | Sesi yang sedang berjalan. Tanpa ini, penonaktifan baru berlaku setelah sesinya kedaluwarsa sendiri |

Akun **dinonaktifkan, bukan dihapus**: `assessments.user_id` memakai
`cascadeOnDelete`, sehingga menghapus akun ikut menghapus seluruh arsip hasil
tesnya. Penghapusan hanya diizinkan bagi akun yang belum pernah mengerjakan tes.

`User::$attributes` menetapkan `is_active = true`. Nilai bawaan kolom tidak
terbaca oleh instance yang baru dibuat, sehingga tanpa ini setiap akun yang baru
mendaftar akan langsung dianggap nonaktif.

### Lapis 5 — Cakupan query

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
| `GET /tes/{id}/cetak` | `assessments.print` | Lembar cetak; pemilik atau admin |

### Calon mahasiswa (`middleware: mahasiswa`)

| Rute | Nama |
|---|---|
| `GET /tes` | `assessments.index` |
| `GET /tes/mulai` | `assessments.create` |
| `GET /tes/bandingkan` | `assessments.compare` |
| `POST /tes` | `assessments.store` |
| `DELETE /tes/{id}` | `assessments.destroy` |
| `GET/POST /tes/{id}/kuesioner` | `assessments.questionnaire`, `assessments.answers.store` |
| `POST /tes/{id}/kuesioner/simpan` | `assessments.answers.autosave` |

`/tes/bandingkan` didaftarkan **sebelum** rute ber-parameter agar tidak
tertangkap sebagai kode sesi tes.

### Admin (`prefix: admin`, `middleware: auth + admin`)

| Rute | Nama |
|---|---|
| `GET /admin` | `admin.dashboard` |
| `resource /admin/prodi` | `admin.study-programs.*` |
| `resource /admin/kriteria` | `admin.criteria.*` |
| `resource /admin/pernyataan` | `admin.questions.*` |
| `resource /admin/periode` | `admin.periods.*` |
| `GET /admin/pengguna` | `admin.users.index` |
| `PUT /admin/pengguna/{id}/status` | `admin.users.status` |
| `PUT /admin/pengguna/{id}/kata-sandi` | `admin.users.password` |
| `DELETE /admin/pengguna/{id}` | `admin.users.destroy` |
| `GET /admin/log` | `admin.activity-logs.index` |
| `GET/PUT /admin/tracer` | `admin.tracer.*` |
| `GET/PUT /admin/pengaturan` | `admin.settings.*` |
| `GET /admin/statistik` | `admin.statistics` |
| `GET /admin/rekap` | `admin.recap.index` |
| `GET /admin/rekap/ekspor` | `admin.recap.export` |
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
rapor beserta rata-rata tiap mapel pendukung, sebaran tipe RIASEC, jurusan sekolah, jenis kelamin,
dan tren tes per bulan.

> **Ketergantungan.** Tren bulanan memakai `DATE_FORMAT` sehingga terikat MySQL.

---

## 11. Penetapan Bobot

Bobot bawaan C1–C5:

| C1 rerata rapor | C2 mapel pendukung | C3 RIASEC | C4 minat | C5 tracer | Σ |
|---|---|---|---|---|---|
| .25 | .20 | .35 | .05 | .15 | **1.00** |

C1 dan C2 bersama membentuk blok nilai rapor 0.45, dengan pembagian 55,6% : 44,4%
sesuai batas SNBP (komponen pertama ≥ 50%, komponen kedua ≤ 50%).

Angka ini **bukan** hasil metode pembobotan formal seperti AHP atau Entropy.
Penetapannya dilakukan melalui **wawancara dengan pihak akademik Politeknik
Negeri Banyuwangi**. Dasar penetapan tiap kriteria dicatat pada kolom
`criteria.description` agar sumbernya melekat pada datanya.

Sistem tidak memaksa Σ bobot = 1, namun memberi peringatan mencolok di halaman
Kriteria dan dasbor admin bila totalnya menyimpang. Perhitungan hanya ditolak
bila total bobot benar-benar nol.

---

## 12. Gelombang Penerimaan

Sesi tes **menyalin** gelombang yang aktif saat tes dibuat ke
`assessments.period_id`, bukan membacanya ulang saat rekap disusun. Prinsipnya
sama dengan `weights_snapshot`: penandaan melekat pada sesi tes itu sendiri,
sehingga membuka gelombang berikutnya tidak memindahkan tes yang sudah tercatat.

Hanya **satu gelombang boleh aktif**, ditegakkan `PeriodController::persist()`.
Dua gelombang aktif membuat `Period::current()` ambigu dan sesi tes bisa masuk ke
gelombang yang salah tanpa disadari.

Gelombang yang sudah dipakai tidak dapat dihapus — sejalan dengan prinsip 3.3.
Kunci asingnya `nullOnDelete` sehingga hasil tes tidak ikut hilang, tetapi
penandaannya tidak dapat dipulihkan.

---

## 13. Catatan Perubahan

`activity_logs` menjawab pertanyaan yang selalu muncul saat hasil dipersoalkan:
*siapa mengubah bobot C3, kapan, dari berapa ke berapa.*

Pencatatan dipasang di **tingkat model** lewat trait `RecordsActivity`, bukan di
controller, supaya perubahan lewat jalur mana pun ikut terekam — termasuk
`Setting::set()` yang dipanggil dari luar controller.

| Keputusan | Alasan |
|---|---|
| Dilewati bila tidak ada pengguna yang masuk | Seeder dan perintah konsol bukan tindakan admin; mencatatnya hanya mengaburkan jejak |
| Penyimpanan tanpa perubahan nilai tidak dicatat | Menekan tombol Simpan tanpa mengubah apa pun bukan peristiwa |
| `user_name` ikut disalin | Jejak tetap terbaca setelah akun pelakunya dihapus |
| Kata sandi tidak masuk `User::activityAttributes()` | Jejak perubahan tidak boleh menyimpan hash kredensial; peristiwa reset dicatat sebagai tindakan, bukan selisih nilai |

Halaman log **hanya dapat dibaca**. Catatan yang dapat disunting tidak lagi
berguna sebagai bukti telusur.

---

## 14. Keluaran untuk Dibawa Pulang

### Lembar cetak calon mahasiswa

`GET /tes/{id}/cetak` menghasilkan halaman HTML ber-`@media print`, bukan PDF
dari pustaka. Peramban yang mencetaknya menjadi PDF, sehingga **tidak ada
dependensi tambahan** yang perlu dipasang dan dipelihara.

Lembar dan layar membaca data dari `ResultController::resultData()` yang sama —
lembar cetak yang angkanya berbeda dari layar akan menimbulkan keraguan atas
hasilnya.

### Ekspor CSV rekap

`GET /admin/rekap/ekspor` memakai `response()->streamDownload` dengan `chunk()`,
sehingga rekap besar tidak dirakit seluruhnya di memori. Penyaringannya dibagi
dengan halaman rekap lewat `AssessmentRecapController::filtered()`, supaya berkas
yang terunduh benar-benar berisi baris yang sedang dilihat admin.

Ditulis dengan BOM UTF-8 agar Excel di Windows tidak salah membaca huruf beraksen.

---

## 15. Simpan Sebagian Jawaban

Kuesioner menyimpan jawaban di **dua lapis**:

| Lapis | Kapan | Menangani |
|---|---|---|
| `localStorage` | Setiap perubahan | Halaman tertutup mendadak, koneksi putus |
| `assessment_answers` lewat `POST .../kuesioner/simpan` | 1,2 detik setelah perubahan terakhir | Berpindah perangkat, membersihkan riwayat peramban |

Pengiriman ditunda sebentar supaya rentetan klik cepat menjadi satu permintaan.
Penyimpanan memakai `upsert` pada indeks unik
`(assessment_id, riasec_question_id)`, sehingga menjawab ulang butir yang sama
menimpa nilainya alih-alih menggandakan baris.

Draft lokal yang belum sempat terkirim disusulkan ke server saat halaman dibuka
kembali. Bila pengiriman gagal, jawaban tetap aman di `localStorage` dan
pengguna diberi tahu tanpa alur pengisiannya terhenti.

Perhitungan **tidak pernah** dijalankan dari jawaban sebagian — hanya
`storeAnswers()` yang memicunya, dan ia tetap mewajibkan seluruh butir terjawab.

---

## 16. Perbandingan Antar Sesi

Calon mahasiswa yang mengulang tes ingin tahu apa yang berubah. Halaman
`/tes/bandingkan` menyandingkan dua sesi: pergeseran persentase RIASEC per
dimensi, perubahan rerata rapor dan nilai mapel pendukung, dan apakah rekomendasinya
berpindah.

Seluruh angka dibaca dari sesi tes masing-masing, tidak ada yang dihitung ulang,
sehingga perbandingannya setia pada hasil yang pernah terbit.

Rekomendasi yang **tetap sama** di beberapa sesi adalah informasi tersendiri: ia
memperkuat keyakinan bahwa prodi tersebut memang sesuai, bukan hasil kebetulan.

---

## 17. Batasan yang Diketahui

| Batasan | Keterangan |
|---|---|
| Belum ada impor CSV | Data tracer study dimasukkan manual lewat formulir |
| Belum ada pembuatan admin lewat antarmuka | Disengaja — lihat §6. Admin dibuat lewat seeder atau SQL |
| Pengiriman surel belum dikonfigurasi | `MAIL_MAILER=log`; pemulihan kata sandi mandiri belum berfungsi, karena itu admin dibekali setel ulang manual |
| Belum ada notifikasi hasil | Calon mahasiswa perlu membuka sendiri lembar hasilnya |
| Belum ada `throttle` pada pengiriman tes | Rute pengiriman belum dibatasi lajunya |
| Terikat MySQL | Statistik memakai `DATE_FORMAT`; `pdo_sqlite` tidak aktif di lingkungan pengembangan |
