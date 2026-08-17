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
| `Riasec.php` | Sumber kebenaran konstanta RIASEC: `DIMENSIONS` (R,I,A,S,E,C), `NAMES`, `LABELS`, `DESCRIPTIONS`, `COLORS`, `LIKERT_LABELS`. Dipakai bersama model, service, seeder, dan view supaya urutan dimensi konsisten di seluruh sistem |
| `Rapor.php` | Aturan nilai rapor SNBP: `SEMESTERS` (1–5, semester terakhir dikecualikan), `MAX_SUPPORT_SUBJECTS` (2), dan `supportSubjects()` yang mengumpulkan gabungan mapel pendukung seluruh prodi aktif |

Daftar mata pelajaran **tidak lagi dikunci di kode**. Ia berada di tabel
`subjects` dan dikelola admin, karena kurikulum SMA dan SMK berbeda: mapel
produktif SMK berbeda per konsentrasi keahlian dan penamaannya tidak seragam
antar sekolah.

`Rapor::supportSubjects()` mengumpulkan mapel pendukung dari **seluruh prodi
aktif**, bukan hanya prodi pilihan responden. CoCoSo memeringkat semua alternatif
sekaligus, sehingga prodi di luar daftar prioritas pun harus punya nilai — justru
prodi itulah yang berpotensi menjadi rekomendasi baru.

### `app/Services/`

| Berkas | Tanggung jawab | Menyentuh DB |
|---|---|---|
| `RiasecService.php` | Skor Likert → persentase → kode Holland; cosine similarity untuk C3 | ❌ |
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
| `Period` | Gelombang PMB; scope `active()`, statis `current()`, atribut `range_label` |
| `ActivityLog` | Jejak perubahan; konstanta `SUBJECT_LABELS`, `ACTION_LABELS`; scope `latestFirst()` |

### `app/Models/Concerns/`

| Berkas | Isi |
|---|---|
| `RecordsActivity.php` | Trait pencatat perubahan. Dipakai `Criteria`, `Setting`, `StudyProgram`, `RiasecQuestion`, `Period`, `User` |

Model pemakai boleh menimpa `activityAttributes()` (kolom yang dicatat) dan
`activityLabel()` (penanda yang tetap terbaca setelah datanya dihapus).

Trait ini dilewati bila tidak ada pengguna yang masuk, sehingga seeder dan
perintah konsol tidak ikut mengotori log.

### `app/Http/Controllers/`

| Berkas | Rute |
|---|---|
| `HomeController` | `/` — halaman depan publik |
| `DashboardController` | `/dashboard` — mengalihkan admin ke panelnya |
| `AssessmentController` | Alur pengisian tes + simpan sebagian jawaban |
| `AssessmentComparisonController` | `/tes/bandingkan` — perbandingan antar sesi |
| `ResultController` | Lembar hasil, rincian perhitungan, lembar cetak |
| `Admin/DashboardController` | `/admin` |
| `Admin/StudyProgramController` | CRUD prodi |
| `Admin/CriteriaController` | CRUD kriteria |
| `Admin/RiasecQuestionController` | CRUD pernyataan |
| `Admin/PeriodController` | CRUD gelombang PMB |
| `Admin/UserController` | Manajemen akun calon mahasiswa |
| `Admin/ActivityLogController` | Catatan perubahan (baca saja) |
| `Admin/SettingController` | Parameter algoritma |
| `Admin/TracerStudyController` | Pembaruan tracer massal |
| `Admin/AssessmentRecapController` | Rekap, ekspor CSV, detail, sensitivitas, hapus |
| `Admin/StatisticsController` | Statistik institusional |

### `app/Http/Requests/`

| Berkas | Dipakai untuk |
|---|---|
| `StoreAssessmentRequest` | Biodata + nilai rapor + prioritas |
| `StoreAnswersRequest` | Jawaban kuesioner, mewajibkan seluruh butir |
| `AutosaveAnswersRequest` | Jawaban sebagian, punya `answers()` yang membuang butir kosong |
| `Admin/StudyProgramRequest` | Tambah/ubah prodi, punya `payload()` |
| `Admin/CriteriaRequest` | Tambah/ubah kriteria, punya `payload()` |
| `Admin/RiasecQuestionRequest` | Tambah/ubah pernyataan, punya `payload()` |
| `Admin/PeriodRequest` | Tambah/ubah gelombang, punya `payload()` |
| `Admin/UpdateSettingsRequest` | Parameter algoritma — **seluruh kunci wajib dikirim sekaligus** |
| `Admin/UpdateTracerRequest` | Pembaruan tracer massal, validasi per baris |

Metode `payload()` mengembalikan data yang sudah siap disimpan — termasuk nilai
turunan seperti `employment_rate` dan normalisasi kotak centang. Controller
memanggil `$request->payload()`, bukan `$request->validated()`, agar aturan
turunannya tidak tercecer di banyak tempat.

### `resources/views/`

```
layouts/          app, guest, navigation (bercabang per peran)
components/       alert, flash, + komponen bawaan Breeze
assessments/      index, create, questionnaire, result, calculation, print, compare
admin/
  dashboard.blade.php
  statistics.blade.php
  study-programs/   index, create, edit, form
  criteria/         index, create, edit, form
  riasec-questions/ index, create, edit, form
  periods/          index, create, edit, form
  users/            index
  activity-logs/    index
  tracer/           index
  settings/         edit
  recap/            index, show, sensitivity
```

`assessments/print.blade.php` sengaja **berdiri sendiri** tanpa layout aplikasi
dan tanpa Tailwind: gaya cetak perlu kendali penuh atas ukuran kertas,
pemenggalan halaman, dan warna yang ikut tercetak.

Menu admin yang lebih jarang dibuka dikumpulkan dalam dropdown **Pengelolaan**
di `layouts/navigation.blade.php` supaya bilah navigasi tetap terbaca. Perhatikan
komponen `x-dropdown`: nilai `width` selain `48` diteruskan mentah sebagai kelas,
jadi tulis `width="w-56"`, bukan `width="56"`.

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

- **Mata pelajaran pendukung** (paling banyak 2, sesuai SNBP). Inilah yang
  membedakan prodi satu dengan lainnya pada C2 — C1 bernilai sama untuk semua
  prodi. Dua prodi dengan mapel pendukung identik akan selalu seri pada blok
  nilai rapor. Prodi yang dikosongkan mapel pendukungnya memakai rerata rapor.
- **Profil RIASEC 6 dimensi** (0–100). Menjadi pembanding cosine similarity C3.
- **Data tracer**. `employment_rate` dihitung otomatis dari
  `employed_count / alumni_count`, tidak pernah diisi manual.

### Menambah kriteria baru

Selama `source`-nya salah satu dari lima yang sudah ada, **tidak ada kode yang
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
| `threshold` | 0–100 | Ambang kelayakan yang ditampilkan pada lembar hasil — tidak menentukan rekomendasi |
| `threshold_mode` | `normal` \| `raw` | Pembanding: K ternormalisasi atau K mentah |
| `lambda` | 0–1 | Keseimbangan S_i dan P_i pada K_ic |
| `epsilon` | 1e-7 – 0.01 | Batas bawah nilai ternormalisasi |
| `unselected_priority_score` | 0–100 | Skor C4 untuk prodi di luar daftar prioritas |
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
| `Feature/RecommendationServiceTest` | Penyimpanan hasil, snapshot, rekomendasi mengikuti peringkat, pengaruh nilai rapor |
| `Feature/AdminPanelTest` | Pemisahan peran, seluruh CRUD, rekap, sensitivitas, statistik |
| `Feature/NewFeaturesTest` | Gelombang, manajemen akun, catatan perubahan, ekspor CSV, lembar cetak, simpan sebagian, perbandingan |
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
| `test_kolom_mapel_pendukung_bervariasi_antar_prodi` | C2 benar-benar membedakan alternatif |
| `test_rerata_rapor_konstan_tetap_ternormalisasi_pada_skala_aslinya` | Batas normalisasi tetap pada kolom konstan C1 |
| `test_mapel_yang_tidak_ditempuh_memakai_rerata_rapor` | Mapel kosong tidak dihukum sebagai nilai nol |
| `test_mapel_pendukung_dibatasi_dua_sesuai_aturan_snbp` | Batas dua mapel pendukung ditegakkan |
| `test_mengganti_gelombang_aktif_tidak_memindahkan_tes_lama` | Penandaan gelombang bersifat snapshot |
| `test_hanya_satu_gelombang_boleh_aktif` | `Period::current()` tidak ambigu |
| `test_kata_sandi_tidak_pernah_ikut_tersimpan_di_catatan_perubahan` | Log tidak menyimpan kredensial |
| `test_seeder_tidak_ikut_tercatat_karena_berjalan_tanpa_pengguna` | Log hanya berisi tindakan admin |
| `test_akun_yang_sudah_pernah_tes_tidak_dapat_dihapus` | Keutuhan arsip |
| `test_sesi_berjalan_terputus_setelah_akunnya_dinonaktifkan` | Penonaktifan berlaku seketika |
| `test_jawaban_sebagian_tersimpan_tanpa_menjalankan_perhitungan` | Perhitungan hanya dari jawaban lengkap |

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

### Nilai bawaan kolom tidak terbaca instance baru

`users.is_active` bawaannya `true` di basis data, tetapi model yang **baru
dibuat** tidak membaca nilai itu — `$user->is_active` bernilai `null` sampai
model dimuat ulang. Karena itu `User::$attributes` menetapkannya secara
eksplisit. Tanpa itu, setiap akun yang baru mendaftar langsung dianggap nonaktif
dan tidak dapat masuk.

Berlaku untuk setiap kolom boolean baru yang mengandalkan `default()` di migrasi.

### Rute statis harus mendahului rute ber-parameter

`/tes/bandingkan` dan `/admin/rekap/ekspor` didaftarkan **sebelum**
`/tes/{assessment}/...` dan `/admin/rekap/{assessment}`. Bila terbalik, kata
"bandingkan" dan "ekspor" akan tertangkap sebagai kode sesi tes dan menghasilkan
404.

### Pencatatan perubahan bergantung pada event model

`RecordsActivity` menyimak event `created`, `updated`, dan `deleted`. Pembaruan
massal seperti `Model::query()->update([...])` **tidak memicu event**, sehingga
tidak tercatat. Bila perlu tercatat, iterasi per model — seperti yang dilakukan
`PeriodController::persist()` saat menonaktifkan gelombang lain.

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
| Impor CSV tracer study | Perlu unduh template, pratinjau sebelum simpan, dan laporan baris gagal |
| Ekspor matriks keputusan | Rekap sudah bisa diunduh; matriks per sesi tes belum |
| Konfigurasi pengiriman surel | `MAIL_MAILER=log`; pemulihan kata sandi mandiri belum berfungsi |
| Notifikasi hasil selesai | Calon mahasiswa perlu membuka sendiri lembar hasilnya |
| Pembatasan laju pengiriman tes | Belum ada `throttle` pada rute pengiriman |
| `docs/perhitungan.md` | Contoh perhitungan manual untuk lampiran laporan |

Sudah dikerjakan sejak versi sebelumnya: ekspor CSV rekap, manajemen akun,
gelombang PMB, catatan perubahan, simpan sebagian jawaban, lembar cetak, dan
perbandingan antar sesi.
