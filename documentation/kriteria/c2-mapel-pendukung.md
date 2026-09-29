# C2 — Nilai Mapel Pendukung Prodi

| | |
|---|---|
| **Kode** | C2 |
| **Sumber** (`criteria.source`) | `support_subject` |
| **Jenis** | benefit |
| **Bobot bawaan** | 0,20 |
| **Satuan** | Skala 0–100 |
| **Batas normalisasi** | tetap, 0 – 100 |
| **Daya pisah terukur** | 12,6% |

## Perannya

**Inilah satu-satunya kolom yang membedakan nilai rapor antar program studi.**
[C1](c1-rerata-rapor.md) bernilai sama untuk semua prodi menurut definisi, jadi
seluruh kontribusi prestasi akademik terhadap peringkat berasal dari C2.

Sesuai komponen kedua SNBP: nilai pada **paling banyak dua** mata pelajaran
pendukung program studi yang dituju.

## Dari mana nilainya

Rerata nilai responden pada mapel pendukung prodi terkait, disaring menurut
jenjang dan jurusan sekolahnya:

```php
$subjects = $program->supportSubjects
    ->filter(fn (Subject $subject) => Rapor::appliesTo($subject, $educationLevel, $schoolMajor));

if ($subjects->isEmpty()) {
    return $raporAverage;
}

$total = 0.0;
foreach ($subjects as $subject) {
    $total += $subjectScores[$subject->id] ?? $raporAverage;
}

return $total / $subjects->count();
```

Dua jalur cadangan, keduanya jatuh ke rerata rapor:

- **Prodi tanpa mapel pendukung yang cocok** dengan jenjang/jurusan responden.
- **Mapel relevan yang dikosongkan responden** — pertanyaannya diajukan, hanya
  tidak dijawab, jadi diperlakukan netral. Menghukumnya sebagai nol akan menilai
  responden atas sesuatu yang tidak pernah ia nyatakan.

## Batas dua mapel berlaku per responden, bukan per prodi

Sebuah prodi boleh menautkan lebih dari dua mapel — TRPL-D4 misalnya menautkan
Matematika, Informatika, dan Rekayasa Perangkat Lunak — asalkan yang
benar-benar berlaku bagi **satu peserta didik** tetap paling banyak dua.

Alasannya: satu prodi menerima pendaftar dari jenjang dan jurusan berbeda.
Teknologi Rekayasa Otomotif wajar memakai Matematika dan Fisika untuk pendaftar
SMA, tetapi Teknik Kendaraan Ringan untuk pendaftar SMK Teknologi Manufaktur.
Bila batas dua dihitung per prodi, salah satu kelompok pasti tidak kebagian — dan
yang paling dirugikan justru peserta didik SMK, pasar utama pendidikan vokasi.

Penegakannya lewat `Rapor::overloadedCombinations()`, yang memeriksa **setiap
kombinasi jenjang × jurusan** saat admin menyunting prodi. Pemeriksaan pada 19
prodi aktif: tidak ada satu pun kombinasi yang melebihi dua mapel.

## Koreksi: C2 pernah mengukur pilihan, bukan prestasi

**Perilaku lama.** Prodi di luar daftar prioritas responden otomatis diberi
**nilai semester terendah**, bukan dihitung dari nilai mapelnya. Alasannya
kriteria maximin (Wald): bertindak konservatif saat tidak ada informasi, agar
prodi yang tidak dipertimbangkan responden tidak diam-diam diuntungkan rerata
yang belum tentu benar.

**Mengapa keliru.** Alasan itu bertumpu pada premis bahwa formulir hanya
menanyakan mapel pendukung prodi prioritas. Premisnya tidak benar:
`AssessmentController` memuat `Rapor::supportSubjects()` — gabungan mapel
pendukung **seluruh prodi aktif** — dan `StoreAssessmentRequest` menyimpan
nilainya untuk semua mapel itu.

Pemeriksaan pada data nyata:

```
TES-SAIMO01   prodi non-prioritas yang punya data: 17 dari 17
TES-HMITPMWC  prodi non-prioritas yang punya data: 10 dari 16
TES-C6QFKFGI  prodi non-prioritas yang punya data: 10 dari 16
TES-Z5RRW0EU  prodi non-prioritas yang punya data: 13 dari 16
```

Maximin karena itu bukan menghadapi ketiadaan informasi, melainkan **membuang
informasi yang ada** dan menggantinya dengan penalti tetap. Akibatnya C2 sebagian
besar mengukur "apakah prodi ini dipilih responden" — bukan prestasi akademik
responden pada prodi itu — dan menghitung minat dua kali bersama
[C4](c4-prioritas-minat.md).

**Sekarang.** C2 dihitung dengan cara yang sama untuk setiap prodi, prioritas
maupun bukan. Efek terukurnya: daya pisah C2 naik dari 5,7% ke 12,6%.

## Mengapa memakai batas normalisasi tetap

Nilai mapel berskala 0–100 sama seperti rapor aslinya, jadi maknanya absolut.
Dengan batas min–maks sampel, selisih kecil antar prodi akan direntangkan menjadi
0–1 penuh — beda beberapa poin terbaca seolah beda terburuk lawan terbaik.

Konsekuensi yang perlu diterima: bila nilai mapel responden merata, C2 memang
nyaris tidak memisahkan. Itu **benar secara metodologis** — responden yang sama
baiknya di semua mapel pendukung memang tidak punya keunggulan akademik pada
prodi mana pun:

```
Sesi TES-Z5RRW0EU  C2 bergerak 87,80 – 90,00  (sebaran sempit, responden merata)
Sesi TES-C6QFKFGI  C2 bergerak 48,70 – 86,40  (sebaran lebar, responden timpang)
```

Berbeda dengan C1 yang **selalu** nol, daya pisah C2 kecil karena datanya, bukan
karena strukturnya.

## Keterbatasan

Mapel pendukung tiap prodi ditetapkan admin dan belum pernah divalidasi terhadap
kurikulum resmi prodi. Penetapan yang keliru akan menggeser hasil tanpa memberi
tanda apa pun.

## Berkas terkait

```
app/Services/DecisionMatrixBuilder.php   supportSubjectValue()
app/Support/Rapor.php                    appliesTo(), overloadedCombinations(), supportSubjects()
app/Http/Requests/Admin/StudyProgramRequest.php   penegakan batas dua mapel
```

## Pengujian

| Test | Menjaga |
|---|---|
| `test_kolom_mapel_pendukung_bervariasi_antar_prodi` | C2 benar-benar membedakan alternatif |
| `test_nilai_mapel_pendukung_yang_berbeda_menghasilkan_peringkat_yang_berbeda` | C2 menggerakkan peringkat |
| `test_prodi_non_prioritas_tetap_memakai_nilai_mapel_yang_sudah_terkumpul` | Data yang terkumpul tidak dibuang |
| `test_nilai_mapel_pendukung_menggerakkan_prodi_non_prioritas` | Prodi di luar pilihan bisa naik karena prestasinya sendiri |
| `test_mapel_yang_tidak_ditempuh_memakai_rerata_rapor` | Mapel kosong tidak dihukum sebagai nol |
| `test_mapel_pendukung_disaring_menurut_jenjang_responden` | Penyaringan jenjang bekerja |
| `test_mapel_pendukung_dibatasi_dua_sesuai_aturan_snbp` | Batas dua mapel per responden ditegakkan |
| `test_prodi_tanpa_mapel_pendukung_yang_cocok_memakai_rerata_rapor` | Jalur cadangan benar |
