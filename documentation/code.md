# Panduan Kode

Peta berkas, konvensi, dan cara mengerjakan perubahan yang lazim.
Untuk rancangan dan alasan di baliknya, lihat [architecture.md](architecture.md).

---

## 1. Menjalankan Proyek

```bash
composer install
npm install

cp .env.example .env          # sesuaikan kredensial MySQL
php artisan key:generate

php artisan migrate:fresh --seed
npm run build                 # atau: npm run dev

php artisan serve
```

### Akun bawaan

| Peran | Surel | Kata sandi |
|---|---|---|
| Admin | `admin@poliwangi.ac.id` | `password` |
| Calon mahasiswa | `siswa@example.com` | `password` |

### Basis data

| Lingkungan | Nama |
|---|---|
| Pengembangan | `spk_poliwangi` |
| Pengujian | `spk_poliwangi_test` |

Pengujian memakai MySQL terpisah, bukan SQLite `:memory:`, karena `pdo_sqlite`
tidak aktif pada PHP Laragon di lingkungan pengembangan. Basis data uji harus
sudah ada sebelum `php artisan test` dijalankan.

---

## 2. Peta Berkas

### `app/Support/`

| Berkas | Isi |
|---|---|
| `Riasec.php` | Sumber kebenaran konstanta: `DIMENSIONS` (R,I,A,S,E,C), `NAMES`, `LABELS`, `DESCRIPTIONS`, `COLORS`, `SUBJECTS`, `LIKERT_LABELS`. Dipakai bersama model, service, seeder, dan view supaya urutan dimensi konsisten di seluruh sistem |

`Riasec::SUBJECTS` adalah kunci yang mengikat tiga tempat sekaligus:
`criteria.subject`, kolom `{key}_score` pada `assessments`, dan kolom
`{key}_relevance` pada `study_programs`.

### `app/Services/`

| Berkas | Tanggung jawab | Menyentuh DB |
|---|---|---|
| `RiasecService.php` | Skor Likert → persentase → kode Holland; cosine similarity untuk C7 | ❌ |
| `CocosoService.php` | Seluruh tahapan CoCoSo, mengembalikan setiap langkah antara | ❌ |
| `ExplanationService.php` | Kontribusi per kriteria, perbandingan antar prodi, sorotan kuat/lemah | ❌ |
| `SensitivityService.php` | Sweep λ dan pergeseran bobot | ❌ |
| `DecisionMatrixBuilder.php` | Menyusun x_ij sesuai `criteria.source` | ✅ (baca) |
| `RecommendationService.php` | Orkestrator dalam transaksi, menyimpan hasil + snapshot | ✅ |

Empat service pertama murni. Jangan tambahkan query ke dalamnya — kemurnian
itulah yang membuatnya dapat diuji dengan angka langsung.

### `app/Models/`

Seluruh model menetapkan nama tabel eksplisit lewat `protected $table`.

| Model | Catatan |
|---|---|
| `User` | Konstanta `ROLE_ADMIN`, `ROLE_MAHASISWA`; `isAdmin()`, `isMahasiswa()`; scope `mahasiswa()`, `admin()` |
| `Assessment` | Kode `TES-XXXXXXXX` dibuat otomatis saat `creating`; `subjectScores()`, `riasecScores()`, `riasecPercentages()` |
| `AssessmentResult` | `matrix` dan `normalized` di-cast `array` |
| `AssessmentPriority`, `AssessmentAnswer` | Tabel penghubung |
| `StudyProgram` | `riasecVector()`, `subjectRelevance()`, atribut `holland_code`, `full_name`, `employment_percent` |
| `Criteria` | Konstanta `SOURCES`; `totalActiveWeight()`; scope `active()`, `ordered()` |
| `RiasecQuestion` | Scope `active()`, `ordered()`; atribut `dimension_name` |
| `Setting` | Key-value dengan cache selamanya; `values()`, `get()`, `set()`, `forgetCache()`; konstanta `DEFAULTS` |

### `app/Http/Controllers/`

| Berkas | Rute |
|---|---|
| `HomeController` | `/` — halaman depan publik |
| `DashboardController` | `/dashboard` — mengalihkan admin ke panelnya |
| `AssessmentController` | Alur pengisian tes |
| `ResultController` | Lembar hasil + rincian perhitungan |
| `Admin/DashboardController` | `/admin` |
| `Admin/StudyProgramController` | CRUD prodi |
| `Admin/CriteriaController` | CRUD kriteria |
| `Admin/RiasecQuestionController` | CRUD pernyataan |
| `Admin/SettingController` | Parameter algoritma |
| `Admin/TracerStudyController` | Pembaruan tracer massal |
| `Admin/AssessmentRecapController` | Rekap, detail, sensitivitas, hapus |
| `Admin/StatisticsController` | Statistik institusional |

### `app/Http/Requests/`

| Berkas | Dipakai untuk |
|---|---|
| `StoreAssessmentRequest` | Biodata + nilai rapor + prioritas |
| `StoreAnswersRequest` | Jawaban kuesioner |
| `Admin/StudyProgramRequest` | Tambah/ubah prodi, punya `payload()` |
| `Admin/CriteriaRequest` | Tambah/ubah kriteria, punya `payload()` |
| `Admin/RiasecQuestionRequest` | Tambah/ubah pernyataan, punya `payload()` |
| `Admin/UpdateSettingsRequest` | Parameter algoritma |
| `Admin/UpdateTracerRequest` | Pembaruan tracer massal, validasi per baris |

Metode `payload()` mengembalikan data yang sudah siap disimpan — termasuk nilai
turunan seperti `employment_rate` dan normalisasi kotak centang. Controller
memanggil `$request->payload()`, bukan `$request->validated()`, agar aturan
turunannya tidak tercecer di banyak tempat.

### `resources/views/`

```
layouts/          app, guest, navigation (bercabang per peran)
components/       alert, flash, + komponen bawaan Breeze
assessments/      index, create, questionnaire, result, calculation
admin/
  dashboard.blade.php
  statistics.blade.php
  study-programs/   index, create, edit, form
  criteria/         index, create, edit, form
  riasec-questions/ index, create, edit, form
  tracer/           index
  settings/         edit
  recap/            index, show, sensitivity
```

Berkas `form.blade.php` adalah partial bersama halaman tambah dan ubah,
disisipkan dengan `@include`. Variabelnya diberi nama tunggal (`$program`,
`$criterion`, `$question`) supaya partial-nya tidak peduli sedang membuat atau
mengubah.

---

## 3. Konvensi

| Aspek | Aturan |
|---|---|
| Nama tabel & kolom | Inggris, tanpa awalan (`criteria.name`, bukan `tbl_kriteria.nama_kriteria`) |
| Nama kelas & metode | Inggris |
| Teks antarmuka, komentar, pesan galat | Indonesia |
| URL | Indonesia (`/tes/mulai`, `/admin/prodi`) |
| Nama rute | Inggris (`assessments.create`, `admin.study-programs.index`) |
| Tabel sesi tes | `assessments`, bukan `tests` — menghindari rancu dengan PHPUnit |
| Kelas service | `final`, dependensi lewat constructor |
| Komentar | Menjelaskan **alasan**, bukan mengulang isi kode |

Gaya kode mengikuti Laravel Pint (standar Laravel).

---

## 4. Cara Mengerjakan Perubahan Umum

### Menambah program studi

Lewat antarmuka: **Admin → Program Studi → Tambah Prodi**.

Yang wajib diisi dengan sadar:

- **Bobot relevansi 6 mapel** (0–1). Inilah yang membedakan prodi satu dengan
  lainnya pada C1–C6. Dua prodi dengan profil relevansi identik akan selalu seri.
- **Profil RIASEC 6 dimensi** (0–100). Menjadi pembanding cosine similarity C7.
- **Data tracer**. `employment_rate` dihitung otomatis dari
  `employed_count / alumni_count`, tidak pernah diisi manual.

### Menambah kriteria baru

Selama `source`-nya salah satu dari empat yang sudah ada, **tidak ada kode yang
perlu diubah**. Cukup tambah lewat menu Kriteria, lalu sesuaikan bobot kriteria
lain supaya totalnya kembali 1.

Bila membutuhkan sumber nilai yang benar-benar baru:

1. Tambah nilai enum pada migrasi `criteria.source`.
2. Tambah entri pada `Criteria::SOURCES`.
3. Tambah cabang pada `DecisionMatrixBuilder::resolve()`.
4. Tambah kolom penampung datanya bila perlu.

### Mengubah bobot

**Admin → Kriteria → Ubah.** Perubahan hanya berlaku untuk tes berikutnya. Hasil
lama memakai `weights_snapshot` miliknya sendiri.

Periksa peringatan total bobot di halaman Kriteria setelah mengubah.

### Menambah butir kuesioner

**Admin → Pernyataan → Tambah Pernyataan.** Usahakan jumlah butir aktif per
dimensi tetap seimbang; halaman indeks memberi peringatan bila timpang.

Butir yang sudah pernah dijawab tidak dapat dihapus — nonaktifkan saja.

Persentase RIASEC memakai jumlah butir dari **jawaban yang benar-benar
terkumpul**, bukan dari daftar soal aktif saat ini. Jadi menambah atau
menonaktifkan pernyataan tidak merusak persentase tes yang sudah selesai.

### Mengubah parameter algoritma

**Admin → Pengaturan.** Berlaku untuk tes berikutnya.

| Kunci | Rentang | Arti |
|---|---|---|
| `threshold` | 0–100 | Nilai minimum agar prioritas pertama tetap direkomendasikan |
| `threshold_mode` | `normal` \| `raw` | Pembanding: K ternormalisasi atau K mentah |
| `lambda` | 0–1 | Keseimbangan S_i dan P_i pada K_ic |
| `epsilon` | 1e-7 – 0.01 | Batas bawah nilai ternormalisasi |
| `unselected_priority_score` | 0–100 | Skor C8 untuk prodi di luar daftar prioritas |
| `likert_min`, `likert_max` | 0–5, 2–10 | Skala kuesioner |
| `min_priorities` | 1–10 | Jumlah prodi minimal yang wajib diurutkan |

`Setting::values()` di-cache selamanya. Setiap penyimpanan dan penghapusan
otomatis membersihkan cache lewat `booted()`. Di dalam pengujian, panggil
`Setting::forgetCache()` setelah `seed()`.

---

## 5. Pengujian

```bash
php artisan test                              # seluruh berkas
php artisan test --filter=AdminPanelTest      # satu kelas
php artisan test tests/Unit                   # satu direktori
```

### Susunan

| Berkas | Cakupan |
|---|---|
| `Unit/RiasecServiceTest` | Konversi persentase, kode Holland, cosine similarity |
| `Unit/CocosoServiceTest` | Tahapan CoCoSo, pengaman kolom konstan dan pembagian nol |
| `Unit/ExplanationServiceTest` | Kontribusi, perbandingan, sorotan |
| `Unit/SensitivityServiceTest` | Sweep λ, pergeseran bobot, penskalaan ulang |
| `Feature/AssessmentFlowTest` | Alur calon mahasiswa ujung ke ujung, otorisasi, penjelasan hasil |
| `Feature/RecommendationServiceTest` | Penyimpanan hasil, snapshot, aturan threshold |
| `Feature/AdminPanelTest` | Pemisahan peran, seluruh CRUD, rekap, sensitivitas, statistik |
| `Feature/Auth/*`, `Feature/ProfileTest` | Bawaan Breeze |

Uji unit memakai `PHPUnit\Framework\TestCase` tanpa `RefreshDatabase` karena
service-nya murni. Uji feature memakai `RefreshDatabase` dan `$this->seed()`.

### Invarian yang dikunci pengujian

Beberapa test menjaga janji sistem, bukan sekadar memeriksa kode berjalan.
Perhatikan bila salah satunya gagal:

| Test | Menjaga |
|---|---|
| `test_perubahan_bobot_tidak_mengubah_hasil_tes_lama` | Snapshot parameter |
| `test_analisis_sensitivitas_tidak_mengubah_hasil_asli` | Sensitivitas hanya membaca |
| `test_admin_tidak_ikut_mengerjakan_tes` | Pemisahan peran |
| `test_prodi_yang_dipakai_pada_tes_tidak_dapat_dihapus` | Keutuhan arsip |
| `test_pernyataan_yang_sudah_dijawab_tidak_dapat_dihapus` | Keutuhan arsip |
| `test_riwayat_hanya_menampilkan_tes_milik_sendiri` | Kerahasiaan data |
| `test_nilai_rapor_dikalikan_relevansi_mapel_sehingga_kolom_bervariasi` | Koreksi kolom konstan |

---

## 6. Perintah Konsol

```bash
php artisan spk:demo                 # buat sesi contoh, cetak seluruh tahapan, lalu batalkan
php artisan spk:demo TES-ABC12345    # tampilkan sesi yang sudah ada
php artisan spk:demo --keep          # simpan sesi contoh yang dibuat otomatis
```

Mencetak setiap langkah perhitungan ke konsol: matriks keputusan, batas kolom,
matriks ternormalisasi, S, P, K_a, K_b, K_c, K, dan peringkat. Dipakai untuk
membandingkan hasil sistem dengan perhitungan manual di Excel.

---

## 7. Titik Rawan

Hal-hal yang mudah terlewat dan akibatnya tidak langsung terlihat.

### `Setting::values()` di-cache

Setiap perubahan pengaturan lewat jalur selain `Setting::set()` — misalnya
`DB::table('settings')->update(...)` — tidak akan membersihkan cache. Selalu
lewat model, atau panggil `Setting::forgetCache()` sesudahnya.

### Penghapusan berantai

`assessment_results` dan `assessment_priorities` memakai `cascadeOnDelete` ke
`study_programs`; `assessment_answers` ke `riasec_questions`. Penjaga sudah
dipasang di controller admin. Bila menambah jalur penghapusan baru, pasang
penjaga serupa.

### Kotak centang pada formulir

Kotak centang yang tidak dicentang tidak dikirim peramban. Karena itu setiap
kotak centang didahului `<input type="hidden" name="..." value="0">`, dan
`payload()` memakai `$this->boolean(...)`.

### `weights_snapshot` menentukan tampilan

Halaman rincian perhitungan, penjelasan hasil, dan analisis sensitivitas
membaca daftar kriteria dari `weights_snapshot`, bukan dari tabel `criteria`.
Kriteria yang dihapus admin tetap muncul pada hasil lama — memang itu yang
diinginkan.

### Peran pada pendaftaran

`role` ada di `User::$fillable` karena dibutuhkan seeder dan factory. Karena itu
`RegisteredUserController` menetapkan `role` secara eksplisit, bukan
mengandalkan nilai bawaan kolom. Bila kelak menambah jalur pembuatan pengguna
baru, **jangan** memakai `User::create($request->all())`.

Pembuatan akun admin dilakukan lewat `UserSeeder` atau SQL langsung.

### Statistik terikat MySQL

`StatisticsController::monthlyTrend()` memakai `DATE_FORMAT`. Bila kelak pindah
ke basis data lain, bagian ini perlu disesuaikan.

---

## 8. Yang Belum Dikerjakan

| Pekerjaan | Catatan |
|---|---|
| Ekspor CSV rekap dan matriks | Rencananya `response()->streamDownload`, tanpa paket tambahan |
| Impor CSV tracer study | Perlu unduh template, pratinjau sebelum simpan, dan laporan baris gagal |
| Manajemen akun pengguna | Reset kata sandi, nonaktifkan akun, tambah admin |
| Periode / gelombang PMB | Penandaan sesi tes agar rekap dapat disaring per gelombang |
| Catatan perubahan data master | Siapa mengubah bobot, kapan, dari berapa ke berapa |
| Simpan sebagian jawaban kuesioner | Saat ini jawaban baru tersimpan ketika seluruh butir dikirim |
| Pembatasan laju pengiriman tes | Belum ada `throttle` pada rute pengiriman |
| `docs/perhitungan.md` | Contoh perhitungan manual untuk lampiran laporan |
