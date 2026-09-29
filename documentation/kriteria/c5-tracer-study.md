# C5 — Prospek Kerja (Tracer Study)

| | |
|---|---|
| **Kode** | C5 |
| **Sumber** (`criteria.source`) | `tracer` |
| **Jenis** | benefit |
| **Bobot bawaan** | 0,15 |
| **Satuan** | Rasio 0,00–1,00 |
| **Batas normalisasi** | tetap, 0,00 – 1,00 |
| **Daya pisah terukur** | 5,1% |

## Dari mana nilainya

Rasio alumni yang terserap dunia kerja, kolom `study_programs.employment_rate`.

Nilainya **tidak pernah diisi manual** — selalu diturunkan dari data tracer:

```php
// StudyProgramRequest::payload()
$data['employment_rate'] = $alumni > 0
    ? round($data['employed_count'] / $alumni, 3)
    : 0;
```

Konsekuensinya rasio itu selalu berada di 0,00–1,00 secara struktural, dan admin
tidak dapat memasukkan angka yang tidak konsisten dengan jumlah alumninya.
Validasi memastikan `employed_count` tidak melebihi `alumni_count`.

Kolom pendukung: `alumni_count`, `employed_count`, `tracer_year`,
`tracer_updated_at`. Pemutakhirannya lewat menu Tracer Study, yang menyunting
seluruh prodi sekaligus dalam satu formulir.

## Perannya

Satu-satunya kriteria yang membawa **informasi dari luar diri responden**. C1 dan
C2 berasal dari rapornya, C3 dari kuesionernya, C4 dari pilihannya — C5 berasal
dari nasib alumni prodi tersebut.

Perannya menyeimbangkan: prodi yang cocok secara kepribadian tetapi serapan
kerjanya rendah akan sedikit tertahan, dan sebaliknya.

## Mengapa batas normalisasi tetap wajib di sini

Ini kasus yang paling jelas menunjukkan bahaya batas min–maks sampel.

Rasio keterserapan bermakna **apa adanya**: 0,80 berarti 80% alumni terserap,
bukan "terburuk di antara prodi lain". Pada data nyata, sebarannya sempit:

```
employment_rate ke-19 prodi: 0,772 – 0,889
```

Selisih nyatanya hanya 11,7 poin persen — perbedaan yang ada, tetapi tidak
dramatis. Dengan batas sampel, rentang itu direntangkan menjadi 0–1 penuh:
prodi berserapan 77,2% dinilai 0, dan yang 88,9% dinilai 1. Beda 11 poin
diperlakukan seolah beda antara "tidak ada alumni terserap sama sekali" dan
"seluruh alumni terserap".

Akibatnya C5 mendominasi hasil jauh melebihi bobot 0,15 yang diberikan admin.

Dengan batas tetap 0,00–1,00, nilai ternormalisasinya tetap 0,772 sampai 0,889 —
daya pisahnya 5,1%, sepadan dengan sebaran nyatanya. Ini keputusan rancangan
pertama yang memperkenalkan konsep batas tetap ke sistem, dan belakangan
diperluas ke seluruh kriteria.

## Keterbatasan

**Data tracer masih data contoh.** Ke-19 prodi memakai angka bawaan
`StudyProgramSeeder` dengan `tracer_year` seragam 2025; log aktivitas
penyuntingan kosong, artinya belum pernah ada admin yang memperbaruinya. Harus
diganti data resmi sebelum hasil dipakai mengambil kesimpulan.

**Tidak ada penanda kedaluwarsa.** Kolom `tracer_year` dan `tracer_updated_at`
tersimpan tetapi tidak pernah dibandingkan dengan tanggal berjalan. Data serapan
tahun 2019 akan dipakai dengan bobot yang sama seperti data tahun ini, tanpa
peringatan apa pun.

**Serapan kerja bukan satu-satunya ukuran prospek.** Rasio ini tidak membedakan
pekerjaan yang sesuai bidang dari yang tidak, tidak memperhitungkan tingkat
penghasilan, dan tidak menangkap prodi yang alumninya banyak melanjutkan studi.

**Belum ada impor CSV.** Data dimasukkan manual lewat formulir, sehingga
memutakhirkan 19 prodi berarti mengisi 38 kolom sekaligus.

## Berkas terkait

```
app/Services/DecisionMatrixBuilder.php            resolve() cabang 'tracer', FIXED_BOUNDS
app/Http/Controllers/Admin/TracerStudyController.php
app/Http/Requests/Admin/StudyProgramRequest.php   penurunan employment_rate
app/Models/StudyProgram.php                       getEmploymentPercentAttribute()
```

## Pengujian

| Test | Menjaga |
|---|---|
| `test_admin_memperbarui_data_tracer_study` | Pemutakhiran massal bekerja |
| `test_alumni_terserap_tidak_boleh_melebihi_total_alumni` | Rasio tidak pernah melebihi 1,00 |
| `test_menyimpan_hasil_untuk_setiap_program_studi_aktif` | C5 ikut masuk matriks untuk semua prodi |
