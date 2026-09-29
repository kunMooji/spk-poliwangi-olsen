# Catatan Perubahan Sistem

Dokumen ini merekam perubahan yang dilakukan pada sistem beserta **alasannya**,
karena setiap keputusan rancangan di sini perlu dapat dipertahankan. Angka yang
disebut adalah hasil pengukuran pada data sesi tes yang benar-benar ada, bukan
perkiraan.

Susunannya dari yang paling memengaruhi hasil perhitungan ke yang paling
administratif.

---

## 1. Perubahan Metodologis

Enam perubahan berikut mengubah angka yang dihasilkan sistem. Semuanya perlu
disebut di laporan.

### 1.1 Bobot kriteria diskalakan agar berjumlah 1

**Sebelumnya.** `CriteriaRequest` memvalidasi tiap bobot `between:0,1`, tetapi
tidak ada yang memeriksa jumlahnya. Satu-satunya penjaga adalah `array_sum > 0`
saat perhitungan.

**Masalahnya.** CoCoSo mencampur dua agregasi yang memperlakukan bobot secara
berbeda: `S_i = Σ(w_j · r_ij)` tumbuh linear terhadap total bobot, sedangkan
`P_i = Σ(r_ij ^ w_j)` tidak. Ketika `Σw ≠ 1`, keduanya bergeser pada skala yang
tidak sebanding, sehingga `K_ib = S/min(S) + P/min(P)` dan `K_ic` yang mencampur
keduanya kehilangan makna. Hasilnya tetap keluar berupa angka — hanya saja
peringkatnya tidak lagi mencerminkan bobot yang dimaksud admin.

**Sekarang.** `RecommendationService::normalizeWeights()` menskalakan bobot
sebelum masuk CoCoSo. Penskalaan mempertahankan seluruh perbandingan antar bobot,
jadi maksud admin tidak berubah: 0.50 dan 0.25 tetap berbanding 2:1 setelah
menjadi 0.4 dan 0.2. Bobot negatif ditolak dengan `CalculationException`.

`weights_snapshot` kini menyimpan dua angka per kriteria: `weight` (yang
benar-benar dipakai) dan `weight_raw` (angka asli dari tabel), sehingga
penskalaannya dapat ditelusuri.

**Bukti.** Test `test_total_bobot_bukan_satu_diskalakan_dan_tidak_mengubah_perbandingan`
menggandakan seluruh bobot tiga kali dan memastikan K serta peringkatnya identik
sampai 1e-9.

### 1.2 Kesesuaian RIASEC: cosine similarity menjadi korelasi Pearson

**Sebelumnya.** C3 dihitung sebagai cosine similarity antara vektor RIASEC
responden dan profil prodi.

**Masalahnya.** Cosine tanpa pemusatan hanya bekerja pada vektor non-negatif,
sehingga sudut antar profil RIASEC tidak pernah lebar. Pada data nyata sistem
ini, rentangnya hanya **7 sampai 28 poin** dan yang terendah pun masih di atas
60. Pita sempit itu jadi masalah serius setelah dinormalisasi dengan batas
min-max sampel, yang merentangkannya paksa ke 0-1 penuh — selisih kecocokan yang
sebenarnya sepele tampil seolah selisih terburuk lawan terbaik.

**Sekarang.**

```
r     = Σ(a_i − ā)(b_i − b̄) / sqrt( Σ(a_i − ā)² · Σ(b_i − b̄)² ),   r ∈ [−1, 1]
nilai = (r + 1) / 2 × 100
```

Tafsirnya absolut: 100 = pola minat searah sempurna, 50 = tidak berkorelasi,
0 = berlawanan sempurna. Rentang pada data nyata melebar ke **46 sampai 82 poin**.

Sifat yang dipertahankan dari cosine: yang diukur adalah *pola* minat, bukan
besar skornya. Pearson bahkan lebih tegas karena kebal terhadap pergeseran
maupun penskalaan.

**Penanganan khusus.** Profil yang seluruh dimensinya bernilai sama — termasuk
responden yang menjawab seragam pada semua butir — tidak punya pola untuk
dicocokkan. Korelasinya tidak terdefinisi, dan dinilai **50 (netral)**, bukan 0.
Menilainya 0 berarti menyatakan "berlawanan", klaim yang tidak didukung data.

### 1.3 Seluruh kriteria memakai batas normalisasi tetap

**Sebelumnya.** C1, C2, dan C5 memakai batas teoretis tetap; C3 dan C4 memakai
min-max sampel.

**Masalahnya.** Perlakuan campuran membuat angka pada kolom `weight` berhenti
mewakili pengaruh yang sebenarnya. Kriteria berbatas sampel **selalu** terentang
ke 0-1 penuh berapa pun sebaran aslinya, sehingga daya pisahnya selalu persis
sebesar bobotnya. Kriteria berbatas tetap hanya menyumbang sebesar rentang
nyatanya.

Terukur pada data nyata: **C3 memegang 79,0% daya pisah dengan bobot 0.35,
sementara C1 berbobot 0.25 memegang 0,0%.** Uji langsung menunjukkan C3
sendirian — tanpa kriteria lain sama sekali — mereproduksi pemenang di 3 dari 4
sesi.

**Sekarang.** Batas tetap untuk kelimanya:

| Kriteria | Batas | Alasan |
|---|---|---|
| C1 `rapor_average` | 0 – 100 | kolom konstan; tanpa batas tetap seluruh alternatif bernilai 1.0 |
| C2 `support_subject` | 0 – 100 | rerata nilai mapel, berskala sama dengan rapor |
| C3 `riasec` | 0 – 100 | korelasi Pearson punya tafsir absolut |
| C4 `priority` | 0 – 100 | skala yang memang dibangkitkan `priorityValue()` |
| C5 `tracer` | 0.00 – 1.00 | rasio keterserapan bermakna apa adanya |

Batas tetap pada C4 juga membuatnya kebal terhadap perubahan
`unselected_priority_score`: bila admin menyetelnya ke 30, batas sampel akan
merentangkan 30-100 kembali ke 0-1 penuh dan diam-diam mengembalikan pengaruh C4
ke ukuran semula.

### 1.4 C2 berhenti membuang data yang sudah terkumpul

**Sebelumnya.** Prodi di luar daftar prioritas responden otomatis diberi nilai
semester terendah, dengan alasan kriteria maximin (Wald) — bertindak konservatif
saat tidak ada informasi.

**Masalahnya.** Alasan itu bertumpu pada premis bahwa formulir hanya menanyakan
mapel pendukung prodi prioritas. **Premisnya tidak benar.**
`AssessmentController` memuat `Rapor::supportSubjects()`, yaitu gabungan mapel
pendukung seluruh prodi aktif, dan `StoreAssessmentRequest` menyimpan nilainya
untuk semua mapel itu. Pada data nyata, **10 sampai 17 prodi non-prioritas per
sesi memang punya nilai yang sudah terkumpul** — lalu dibuang dan diganti
penalti tetap.

Akibatnya C2 sebagian besar mengukur "apakah prodi ini dipilih responden",
bukan prestasi akademik responden pada prodi itu, dan menghitung minat dua kali
bersama C4.

**Sekarang.** C2 dihitung dengan cara yang sama untuk setiap prodi. Mapel yang
relevan tetapi dikosongkan responden jatuh ke rerata rapor — pertanyaannya
diajukan, hanya tidak dijawab, jadi diperlakukan netral. Yang dirata-ratakan
tetap hanya mapel yang ditempuh jenjang dan jurusan responden, sehingga batas
dua mapel pendukung per responden sesuai SNBP tetap terjaga.

### 1.5 Ambang kelayakan menjadi ambang margin

**Sebelumnya.** Pengaturan `threshold` (bawaan 70) disebut "ambang kelayakan",
dibandingkan terhadap `k_normal`. Ada pula `threshold_mode` (`normal` | `raw`).

**Masalahnya.** `k_normal = K_i / max(K_i) × 100`, sehingga prodi peringkat 1
**selalu** bernilai tepat 100. Diperiksa pada 6 sesi tersimpan: nilai distinct
`recommended_k_normal` hanya `100`. Ambang berapa pun tidak pernah mengikat pada
prodi yang direkomendasikan, dan perbandingan antar sesi membandingkan 100 lawan
100. `threshold_mode` tidak pernah dipakai membandingkan apa pun.

Akar persoalannya: **K tidak punya skala absolut.** K_ia, K_ib, dan K_ic
ketiganya didefinisikan relatif terhadap alternatif lain pada sesi yang sama
(`Σ(P+S)`, `min S`, `min P`, `max S`, `max P`). Dua upaya menurunkan skala
absolut darinya sudah diuji dan gagal:

- Memakai `S_i` sebagai skor absolut: pada 20.000 simulasi, **6,2% pasangan
  prodi** urutannya berbeda dari peringkat CoCoSo — tabel akan menampilkan
  peringkat 2 dengan skor lebih tinggi daripada peringkat 1.
- Menyisipkan alternatif ideal dan anti-ideal sebagai jangkar: `min P` menjadi
  sangat kecil sehingga `K_ib` meledak (K ideal mencapai 333.384), dan urutan
  alternatif nyata berubah pada 67% percobaan.

**Sekarang.** `threshold` dan `threshold_mode` diganti `margin_threshold`
(bawaan 10). Yang disimpan adalah `margin_to_runner_up` — jarak peringkat 1
terhadap peringkat 2 pada skala relatif itu, yang bermakna dan sebanding antar
sesi. Margin lebar berarti rekomendasi meyakinkan; margin tipis berarti kedua
prodi teratas praktis setara dan responden diarahkan menimbang keduanya.

Migrasi `2026_08_31_000001_replace_threshold_with_margin` juga mengisi margin
sesi lama dari `assessment_results` yang sudah tersimpan, tanpa menghitung ulang.

Kolom `k_normal` dilabeli jujur sebagai **"% thd. terbaik"** pada seluruh tabel.

### 1.6 Peringkat yang praktis seri ditandai

**Masalahnya.** Setiap sesi memuat **5 sampai 12 pasang prodi bersebelahan** yang
selisihnya di bawah 0,5 poin dari 100 — beberapa hanya 0,003 dan 0,010. Selisih
sekecil itu berada di bawah presisi data yang membentuknya; urutan di antaranya
ditentukan pembulatan, dan ketika benar-benar sama, ditentukan urutan kode prodi.
Menampilkannya sebagai peringkat 11 dan 12 tanpa keterangan membuat sistem
mengklaim ketelitian yang tidak dimilikinya.

**Sekarang.** Pengaturan `tie_threshold` (bawaan 0,50) dan
`Assessment::tieGroups()` yang mengelompokkan secara berantai: bila #9 dekat #10
dan #10 dekat #11, ketiganya satu kelompok. Penandanya muncul di empat tabel
peringkat — hasil siswa, lembar cetak, rincian perhitungan, dan rekap admin.

Ini penanda murni. Test memastikan peringkat dan prodi rekomendasi tidak berubah
sama sekali, dan tidak ada prodi yang dikelompokkan tanpa tetangga yang
benar-benar berdekatan.

---

## 2. Perbaikan Cacat

### 2.1 Hasil tes yang sudah selesai dapat ditimpa

Halaman kuesioner mengalihkan sesi selesai ke lembar hasil, dan `autosave`
menolaknya dengan HTTP 409 — tetapi `storeAnswers` tidak punya penjaga yang sama.
Pengiriman langsung menimpa hasil di tempat:

```
sebelum: rekomendasi prodi 14, holland RIA
sesudah: rekomendasi prodi 12, holland SRI
```

Dampaknya dua-duanya buruk: rekomendasi yang sudah dilihat dan dicetak responden
berubah tanpa jejak, dan riwayat sesi kehilangan versi lamanya sehingga fitur
perbandingan antar sesi tidak punya bahan pembanding.

Sekarang ditolak dengan arahan membuat sesi baru — yang justru menyimpan keduanya
sehingga perubahannya bisa ditelusuri.

### 2.2 Skala Likert tidak tersambung ke tampilan

Pengaturan `likert_min` / `likert_max` dibaca oleh validasi dan oleh perhitungan
persentase, tetapi tombol pilihannya memakai konstanta `LIKERT_LABELS` yang
dipatok 1-5 dan tata letak `grid-cols-5`.

Bila skala disetel 1-7, layar tetap menampilkan lima tombol sementara
matematikanya membagi dengan rentang 6:

```
Responden menjawab 5 (maksimum yang tersedia) di seluruh butir R
  -> tercatat 66,67%, seharusnya 100%
```

Setiap responden tertekan ke bawah secara sistematis, tanpa error dan tanpa
peringatan. Sekarang `Riasec::likertScale($min, $max)` menurunkan label dari
rentang yang berlaku, dan jumlah kolom tombol mengikutinya.

> **Catatan.** Skala yang dipakai penelitian ini tetap **1-5** sesuai proposal.
> Perbaikan ini bersifat jaring pengaman, bukan perubahan instrumen.

### 2.3 Statistik menghitung rata-rata dari angka konstan

`averageFit` pada dasbor admin adalah `avg(recommended_k_normal)` — rata-rata
dari konstanta 100, sehingga selalu menampilkan 100,0. Kolom per sekolah
(`average_fit`) mengalami hal yang sama, menampilkan 100,0 untuk setiap sekolah
seolah sekolah-sekolah itu berbeda. Keduanya diganti rata-rata margin.

### 2.4 Nilai desimal tertolak dan dibulatkan diam-diam

Dua persoalan terpisah:

- **Koma ditolak.** `82,5` — penulisan yang benar dalam bahasa Indonesia — gagal
  aturan `numeric` karena `is_numeric()` hanya mengenal titik. Sekarang
  `prepareForValidation()` menormalkan koma menjadi titik khusus untuk kolom
  nilai. Masukan yang memang bukan angka tetap ditolak dengan pesan yang benar.
- **Pembulatan diam-diam.** `82.756` lolos validasi, lalu dibulatkan oleh kolom
  `decimal(5,2)` menjadi 82,76 — sementara rerata terlanjur dihitung dari angka
  aslinya, sehingga angka tersimpan dan angka yang dipakai menghitung berbeda.
  Sekarang dibulatkan eksplisit di satu tempat sebelum keduanya.

Input nilai juga diubah dari `type="number"` ke `type="text" inputmode="decimal"`
agar koma dapat diketik sementara papan ketik ponsel tetap memunculkan angka.

---

## 3. Transparansi

### 3.1 Daya pisah kriteria

`CriteriaInfluenceService` menghitung **bobot × rentang nilai ternormalisasi**,
yaitu seberapa besar sebuah kriteria benar-benar sanggup menggeser peringkat.

Bobot saja tidak menjawab itu: kriteria yang nilainya nyaris sama untuk semua
prodi tidak memisahkan apa pun betapapun besar bobotnya. Angka ini tampil di dua
tempat — rincian perhitungan (per sesi, dilihat responden) dan menu Kriteria
(rerata lintas sesi, dilihat admin saat menyetel bobot), lengkap dengan
peringatan untuk kriteria berbobot yang daya pisahnya nol.

### 3.2 Reliabilitas instrumen

`ReliabilityService` menghitung **Cronbach's alpha** per dimensi dan untuk
seluruh instrumen, tampil di menu Statistik.

```
alpha = k/(k−1) × (1 − Σ varian tiap butir / varian skor total)
```

Dua keputusan rancangan:

- **Hanya responden yang menjawab lengkap** butir aktif yang dihitung. Mengisi
  kekosongan dengan rerata akan menekan varian dan menaikkan alpha secara semu.
  Instrumen ini juga pernah berubah dari 42 ke 24 butir, sehingga mencampur
  responden dua versi berarti menghitung varian atas kolom yang tidak sebanding.
- **Di bawah 30 responden ditandai belum memadai untuk dilaporkan.** Angkanya
  tetap tampil agar dapat dipantau, tetapi dengan peringatan agar tidak
  diam-diam masuk ke laporan.

Alpha bernilai negatif tidak dijepit ke nol — itu hasil yang sah dan informatif,
pertanda butir-butirnya justru berlawanan arah.

---

## 4. Kebersihan

- **Penomoran kriteria.** Kode masih menyebut skema `C1..C9` yang sudah tidak ada
  di 14 tempat (`TracerStudyController` menyebut tracer C9, `AssessmentPriority`
  menyebut prioritas C8, `RiasecService` menyebut RIASEC C7, label pengaturan
  "Skor C8"). Seluruhnya diselaraskan ke C1..C5. Dua migrasi yang menyebut
  `C1..C6` sengaja dibiarkan karena menjelaskan kolom `math_relevance` dan
  `math_score` yang memang ada saat migrasi itu dibuat dan sudah dihapus
  migrasi berikutnya.
- **Kode mati.** Cabang `<select>` pada form pengaturan beserta konstanta
  `CHOICES` — sisa `threshold_mode` yang tidak pernah terpakai lagi.
- **Data uji.** Akun `siswa@example.com` (3 sesi) dan `test-ui@example.com`
  (1 sesi) dihapus. Seluruh akun contoh dan fixture pengujian dipindah ke format
  `@gmail.com`; `UserFactory` diubah dari `fake()->safeEmail()` yang selalu
  berdomain `example.*`.
- **Test usang.** Assertion `LandingPageTest` masih mencari judul lama
  "Enam dimensi kepribadian RIASEC" yang berubah saat rombakan UI.

---

## 5. Angka Pengukuran

### Daya pisah kriteria, sebelum dan sesudah

Rata-rata dari empat sesi nyata (sesi berapor 11 dikeluarkan sebagai data uji):

| Kriteria | Bobot | Sebelum | Sesudah |
|---|---|---|---|
| C1 rapor | 0,25 | 0,0% | 0,0% |
| C2 mapel pendukung | 0,20 | 5,7% | **12,6%** |
| C3 RIASEC | 0,35 | 79,0% | **67,9%** |
| C4 prioritas | 0,05 | 11,3% | 14,5% |
| C5 tracer | 0,15 | 4,0% | 5,1% |

C3 sendirian yang tadinya mereproduksi pemenang di 3 dari 4 sesi, menjadi 2 dari 4.

### Analisis sensitivitas

Uji geseran bobot ±25% dan ±50% serta sweep λ, 31 skenario per sesi:

| Sesi | Margin #1 vs #2 | Skenario stabil | Kriteria kritis |
|---|---|---|---|
| TES-HMITPMWC | 6,03 | 31/31 (100%) | tidak ada |
| TES-Z5RRW0EU | 3,13 | 31/31 (100%) | tidak ada |
| TES-SAIMO01 | 3,82 | 30/31 (96,8%) | C3 |
| TES-C6QFKFGI | 0,86 | 23/31 (74,2%) | C2, C3, C4 |

Nilai λ tidak pernah menggeser juara di sesi mana pun, sehingga pilihan λ = 0,5
tidak arbitrer. Pola yang terlihat: **sesi bermargin tipis juga yang paling
rapuh terhadap perubahan bobot** — memvalidasi indikator margin sebagai penanda
keyakinan.

### Statistik setelah pembersihan data uji

```
Rata-rata rapor di dasbor: 57,63  ->  78,10
```

### Cakupan pengujian

```
198 test  ->  210 test, seluruhnya lulus
```

---

## 6. Keterbatasan yang Masih Terbuka

Empat hal berikut **belum** diperbaiki dan perlu disebut sebagai keterbatasan.

1. **Profil RIASEC prodi dan data tracer masih data contoh.** Ke-19 prodi masih
   memakai angka bawaan seeder — log aktivitas penyuntingan kosong. Padahal
   C3 dan C5 bersama memegang sekitar 78% daya pisah keputusan. Ini pekerjaan
   pengumpulan data, bukan pekerjaan kode; menu Program Studi dan Tracer Study
   sudah tersedia untuk mengisinya.
2. **Bobot tidak berasal dari metode pembobotan formal.** Penetapannya lewat
   wawancara akademik, bukan AHP atau Entropy, sehingga tidak ada uji
   konsistensi. Analisis sensitivitas memberi argumen ketahanan, bukan argumen
   penetapan.
3. **Resolusi instrumen terbatas.** 4 butir per dimensi dengan skala Likert 1-5
   hanya menghasilkan 17 tingkat nilai, bergerak dalam langkah 6,25 poin. Ini
   ikut menyumbang kedekatan nilai antar alternatif. Penelitian selanjutnya dapat
   menaikkannya lewat skala 7 titik — 25 tingkat dengan langkah 4,17 poin, tanpa
   menambah jumlah butir sehingga tidak menambah beban responden.
4. **Nilai rapor tidak terverifikasi.** Dilaporkan sendiri oleh responden dengan
   validasi `numeric between:0,100`. Wajar untuk alat rekomendasi; perlu
   verifikasi bila dipakai untuk keputusan yang mengikat.

### Catatan atas pemilihan 4 butir per dimensi

Instrumen sempat memakai 7 butir per dimensi (42 butir), lalu dipangkas ke 4
(24 butir) dengan penonaktifan yang **seimbang** — setiap dimensi menyisakan
tepat 4 butir, sehingga perbandingan antar dimensi tetap adil.

Alasannya menghindari *respondent fatigue* dan *satisficing* (Krosnick, 1991),
yang muncul sebagai *straight-lining* atau *non-differentiation*. Data yang ada
konsisten dengan itu:

```
TES-SAIMO01   42 butir | 69% jawaban tertumpuk di satu pilihan
TES-Z5RRW0EU  24 butir | 38% jawaban tertumpuk di satu pilihan
```

**Namun ini satu responden lawan satu responden** — indikasi, bukan bukti.
Perdebatan ini baru dapat diselesaikan dengan angka Cronbach's alpha setelah
terkumpul sekitar 30 responden.

---

## 7. Berkas yang Berubah

**Berkas baru**

```
app/Services/CriteriaInfluenceService.php
app/Services/ReliabilityService.php
database/migrations/2026_08_31_000001_replace_threshold_with_margin.php
database/migrations/2026_09_01_000001_add_tie_threshold_setting.php
tests/Unit/ReliabilityServiceTest.php
```

**Perhitungan**

```
app/Services/RecommendationService.php   penskalaan bobot, margin, snapshot
app/Services/DecisionMatrixBuilder.php   batas tetap seragam, C2
app/Services/RiasecService.php           cosine -> Pearson
app/Services/CocosoService.php           dokumentasi skala K
app/Support/Riasec.php                   likertScale()
app/Models/Assessment.php                tieGroups(), margin
app/Models/Setting.php                   margin_threshold, tie_threshold
```

**Antarmuka dan pengendali**

```
app/Http/Controllers/AssessmentController.php             penjaga sesi selesai, skala Likert
app/Http/Controllers/ResultController.php                 daya pisah
app/Http/Controllers/Admin/CriteriaController.php         daya pisah
app/Http/Controllers/Admin/StatisticsController.php       margin, reliabilitas
app/Http/Controllers/Admin/SettingController.php          hapus kode mati
app/Http/Controllers/Admin/AssessmentRecapController.php  kolom ekspor
app/Http/Requests/StoreAssessmentRequest.php              desimal koma, pembulatan
app/Http/Requests/Admin/UpdateSettingsRequest.php         pengaturan baru
```

**Tampilan**

```
resources/views/assessments/     result, print, calculation, compare, create, questionnaire
resources/views/admin/           criteria/index, statistics, recap/show, settings/edit,
                                 study-programs/form
```

**Dokumentasi**

```
documentation/architecture.md    skala K, batas normalisasi, margin, penskalaan bobot
documentation/code.md            pengaturan, Pearson, service baru
```
