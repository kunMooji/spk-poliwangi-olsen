# C3 — Kesesuaian Kepribadian RIASEC

| | |
|---|---|
| **Kode** | C3 |
| **Sumber** (`criteria.source`) | `riasec` |
| **Jenis** | benefit |
| **Bobot bawaan** | 0,35 |
| **Satuan** | Skala 0–100 (korelasi dipetakan) |
| **Batas normalisasi** | tetap, 0 – 100 |
| **Daya pisah terukur** | **67,9%** — terbesar dari kelimanya |

## Perannya

Kriteria dengan pengaruh terbesar, dan satu-satunya yang membawa **minat serta
kepribadian** ke dalam keputusan. Inilah yang membedakan sistem ini dari mesin
perangkingan SNBP: yang dicari bukan siswa terbaik untuk sebuah prodi, melainkan
prodi yang paling sejalan dengan pola minat seorang siswa.

Bobot 0,35 diberikan justru agar minat tertimbang besar **melalui instrumen
psikometri**, bukan melalui pertanyaan preferensi langsung yang jawabannya sudah
diketahui responden. Preferensi langsung ditangani [C4](c4-prioritas-minat.md)
dengan bobot terkecil.

## Dari mana nilainya

Dua vektor enam dimensi dibandingkan:

- **Vektor responden** — persentase per dimensi RIASEC, hasil akumulasi jawaban
  Likert (`Assessment::riasecPercentages()`).
- **Vektor prodi** — profil RIASEC program studi, kolom `riasec_r` sampai
  `riasec_c` pada tabel `study_programs`.

```
r     = Σ(a_i − ā)(b_i − b̄) / sqrt( Σ(a_i − ā)² · Σ(b_i − b̄)² ),   r ∈ [−1, 1]
nilai = (r + 1) / 2 × 100
```

Tafsirnya absolut:

| Nilai | Arti |
|---|---|
| 100 | pola minat searah sempurna |
| 50 | tidak berkorelasi |
| 0 | berlawanan sempurna |

## Koreksi: cosine similarity menjadi korelasi Pearson

**Perilaku lama.** C3 dihitung sebagai cosine similarity, dinormalisasi dengan
batas min–maks sampel.

**Mengapa keliru.** Cosine tanpa pemusatan hanya bekerja pada vektor
non-negatif, sehingga sudut antar profil RIASEC tidak pernah lebar. Pada data
nyata sistem ini, rentangnya hanya **7 sampai 28 poin** dan yang terendah pun
masih di atas 60 — dua prodi yang sangat berbeda karakternya tetap terbaca
"mirip".

Pita sempit itu jadi masalah serius setelah dinormalisasi dengan batas sampel,
yang merentangkannya paksa ke 0–1 penuh. Selisih kecocokan yang sebenarnya
sepele tampil seolah selisih terburuk lawan terbaik, dan C3 mendominasi hasil
jauh melebihi bobotnya.

**Terukur:** C3 memegang **79,0% daya pisah** dengan bobot 0,35. Uji langsung
menunjukkan C3 sendirian — tanpa kriteria lain sama sekali — mereproduksi
pemenang di **3 dari 4 sesi**.

**Sekarang.** Pearson memusatkan kedua vektor terlebih dulu, sehingga korelasi
negatif benar-benar mungkin dan rentangnya melebar:

```
Sesi            cosine (rentang)      pearson (rentang)
TES-SAIMO01     62,44 – 90,48  (28)   2,58 – 84,83  (82)
TES-HMITPMWC    83,63 – 97,00  (13)   19,11 – 88,39 (69)
TES-C6QFKFGI    84,46 – 97,91  (13)   12,30 – 83,17 (71)
TES-Z5RRW0EU    88,67 – 95,62  ( 7)   19,25 – 64,89 (46)
```

Karena skalanya kini punya tafsir absolut, C3 dapat dinormalisasi dengan batas
tetap 0–100 seperti kriteria lain — menyelesaikan sekaligus persoalan perlakuan
campuran. Daya pisah turun ke **67,9%**, dan C3 sendirian hanya mereproduksi
pemenang di 2 dari 4 sesi.

**Sifat yang dipertahankan.** Yang diukur tetap *pola* minat, bukan besar
skornya. Responden yang menjawab konsisten rendah tetap dinilai cocok selama
urutan minatnya sejalan dengan profil prodi — bahkan lebih tegas, karena Pearson
kebal terhadap pergeseran maupun penskalaan.

## Profil datar dinilai netral, bukan nol

Vektor yang seluruh dimensinya bernilai sama — termasuk responden yang menjawab
seragam pada semua butir — tidak punya pola untuk dicocokkan. Korelasinya tidak
terdefinisi (varian nol pada penyebut), dan dikembalikan sebagai
`NEUTRAL_COMPATIBILITY = 50.0`.

Mengembalikan 0 berarti menyatakan "berlawanan dengan seluruh prodi", klaim yang
tidak didukung data. Nilai 50 menyatakan yang sebenarnya: tidak ada informasi
untuk membedakan.

## Instrumen pengukurnya

| | |
|---|---|
| Jumlah butir aktif | 24 (4 per dimensi, seimbang) |
| Skala | Likert 1–5 |
| Tingkat nilai per dimensi | 17 |
| Langkah persentase | 6,25 poin |

Instrumen sempat memakai 7 butir per dimensi (42 butir), lalu dipangkas untuk
menghindari *respondent fatigue* dan *satisficing* (Krosnick, 1991) — penurunan
mutu jawaban yang muncul sebagai *straight-lining* atau *non-differentiation*.

Penonaktifannya **seimbang**: setiap dimensi menyisakan tepat 4 butir, sehingga
perbandingan antar dimensi tetap adil. Bila satu dimensi menyisakan 7 butir
sementara lainnya 2, dimensi yang lebih panjang akan punya pengukuran lebih
stabil dan perbandingannya tidak sebanding.

Data yang ada konsisten dengan kekhawatiran itu, meski **satu responden lawan
satu responden** sehingga hanya indikasi:

```
TES-SAIMO01   42 butir | 69% jawaban tertumpuk di satu pilihan
TES-Z5RRW0EU  24 butir | 38% jawaban tertumpuk di satu pilihan
```

Reliabilitasnya diukur `ReliabilityService` dengan Cronbach's alpha per dimensi,
tampil di menu Statistik. Angkanya baru layak dilaporkan pada sekitar 30
responden.

## Keterbatasan

**Profil RIASEC prodi masih data contoh.** Ke-19 prodi memakai angka bawaan
`StudyProgramSeeder` — log aktivitas penyuntingan kosong. Karena C3 memegang
67,9% daya pisah, ini keterbatasan paling serius pada sistem: perhitungannya
benar, masukannya belum diverifikasi. Harus diganti data sebenarnya lewat menu
Program Studi sebelum hasil dipakai mengambil kesimpulan.

**Resolusi terbatas.** 17 tingkat dengan langkah 6,25 poin membuat vektor RIASEC
terkuantisasi kasar — dua responden yang minatnya sebenarnya berbeda dapat
mendarat di vektor identik. Ini ikut menyumbang kedekatan nilai antar alternatif.
Penelitian selanjutnya dapat menaikkannya lewat skala 7 titik: 25 tingkat dengan
langkah 4,17 poin, **tanpa menambah jumlah butir** — resolusi adalah hasil
perkalian jumlah butir × rentang skala. Sistem sudah mendukungnya lewat
pengaturan `likert_max`.

## Berkas terkait

```
app/Services/RiasecService.php           compatibility(), percentages(), hollandCode()
app/Support/Riasec.php                   DIMENSIONS, likertScale()
app/Services/ReliabilityService.php      Cronbach's alpha
app/Models/StudyProgram.php              riasecVector()
```

## Pengujian

| Test | Menjaga |
|---|---|
| `test_vektor_identik_menghasilkan_kecocokan_seratus_persen` | Batas atas skala |
| `test_pola_yang_bergeser_seragam_tetap_seratus_persen` | Kekebalan Pearson terhadap pergeseran |
| `test_pola_berlawanan_sempurna_menghasilkan_nol` | Batas bawah skala |
| `test_pola_tidak_berkorelasi_menghasilkan_lima_puluh` | Titik tengah skala |
| `test_profil_datar_dinilai_netral_bukan_nol` | Profil tanpa pola tidak dihukum |
| `test_hasil_selalu_berada_pada_skala_nol_seratus` | 500 vektor acak tetap dalam rentang |
| `test_konversi_persentase_pada_titik_tengah_dan_maksimum` | Konversi Likert ke persentase |
| `test_menjawab_setinggi_mungkin_selalu_menghasilkan_seratus_persen` | Skala Likert tersambung ke perhitungan |
