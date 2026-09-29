# C1 — Rerata Rapor Seluruh Mapel

| | |
|---|---|
| **Kode** | C1 |
| **Sumber** (`criteria.source`) | `rapor_average` |
| **Jenis** | benefit |
| **Bobot bawaan** | 0,25 |
| **Satuan** | Skala 0–100 |
| **Batas normalisasi** | tetap, 0 – 100 |
| **Daya pisah terukur** | **0,0%** |

## Dari mana nilainya

Rerata nilai rapor seluruh mata pelajaran, **semester 1 sampai 5** — semua
semester kecuali yang terakhir, sesuai ketentuan SNPMB untuk jenjang tiga tahun.
Semesternya dikunci pada `Rapor::SEMESTERS`.

```php
// StoreAssessmentRequest::raporAverage()
$scores = $this->raporSemesters();

return round(array_sum($scores) / count($scores), self::SCORE_PRECISION);
```

Rerata dihitung dari nilai yang **sudah dibulatkan** ke dua desimal, bukan dari
angka mentah, agar angka yang tersimpan sama dengan angka yang dipakai
menghitung. Kolom penyimpannya `decimal(5,2)`.

Nilai desimal diterima dengan titik maupun koma: `prepareForValidation()`
menormalkan `82,5` menjadi `82.5`, karena `is_numeric()` tidak mengenal koma.

## Sifat yang wajib disadari

**Rerata rapor adalah atribut calon mahasiswa, bukan atribut program studi.**
Nilainya karena itu sama persis untuk seluruh alternatif dalam satu sesi tes:

```
Sesi TES-Z5RRW0EU, kolom C1 untuk 19 prodi:
  87,80  87,80  87,80  87,80  87,80  ...  87,80
  -> hanya 1 nilai berbeda, max − min = 0
```

Konsekuensinya **C1 tidak dapat mengubah urutan peringkat sama sekali**. Bobot
0,25 padanya tidak berarti 25% pengaruh, melainkan 0%.

Ini bukan cacat yang bisa ditambal dengan menyetel bobot. Kolom yang konstan
tidak memisahkan apa pun, titik. Penyebabnya struktural: SNBP memeringkat
**siswa untuk satu prodi**, sedangkan sistem ini memeringkat **prodi untuk satu
siswa** — sehingga rerata rapor keseluruhan tidak punya sisi yang berbeda antar
alternatif. Pembeda nilai rapor antar prodi sepenuhnya ada pada [C2](c2-mapel-pendukung.md).

## Mengapa tetap memakai batas normalisasi tetap

Meski tidak memisahkan, C1 tetap perlu dinormalisasi dengan benar.

Dengan batas min–maks sampel, `max − min = 0` membuat `CocosoService::normalize()`
memberi nilai `1.0` kepada seluruh alternatif — rapor 95 tak terbedakan dari 55,
dan bobot 0,25 terbuang percuma menambahkan konstanta terbesar yang mungkin.
Batas tetap 0–100 membuat rapor terbaca pada skala aslinya: responden berapor
87,8 menyumbang 0,878 × bobot, yang berapor 55 menyumbang 0,55 × bobot.

Sumbangannya tetap sama untuk semua prodi, tetapi **besarannya** kini
mencerminkan nilai sebenarnya.

## Efek yang tetap ada meski urutan tidak berubah

C1 **memampatkan jarak skor** yang ditampilkan. Makin tinggi C1, makin sempit
selisih `k_normal` antar prodi, karena konstanta besar yang sama ditambahkan ke
setiap alternatif sehingga perbedaan di antara mereka terlihat kecil secara
relatif:

```
C1 = 90  ->  urutan C>B>D>A  | skor 92,32 – 100,00 (lebar 7,68)
C1 = 20  ->  urutan C>B>D>A  | skor 90,62 – 100,00 (lebar 9,38)
```

Urutannya identik — itu yang penting dan selalu berlaku. Ini salah satu alasan
margin peringkat 1 versus 2 lebih berguna daripada skor mentahnya.

> **Catatan lanjutan.** Menaikkan **bobot** C1 ke angka ekstrem (≥ 0,77) dapat
> membalikkan pemenang, meski kolomnya konstan. Penyebabnya artefak CoCoSo:
> `S_i` tetap memenangkan alternatif yang sama di semua bobot, tetapi
> `P_i = Σ(r^w)` tidak linear terhadap `w`, sehingga memindahkan bobot besar ke
> kriteria konstan menggeser struktur `P` dan `K` ikut berubah. Angkanya bergerak
> tanpa ada informasi baru sama sekali — alasan tambahan untuk tidak menumpuk
> bobot besar pada kriteria berkolom konstan.

## Agar C1 benar-benar ikut memeringkat

Dibutuhkan data yang **belum ada** di sistem: pembanding yang melekat pada prodi,
misalnya rerata rapor pendaftar yang biasanya diterima, atau tingkat keketatan
tiap prodi. Dengan data itu, C1 berubah dari "nilai saya" menjadi "nilai saya
dibanding yang biasanya diterima prodi ini" — dan barulah kolomnya bervariasi.

Selama data itu belum ada, yang paling jujur adalah menampilkan daya pisahnya apa
adanya. Halaman rincian perhitungan menandai C1 dengan label
**"tidak memisahkan"**, dan menu Kriteria memberi peringatan bahwa bobotnya tidak
mengubah peringkat.

## Berkas terkait

```
app/Http/Requests/StoreAssessmentRequest.php   raporAverage(), raporSemesters()
app/Support/Rapor.php                          SEMESTERS
app/Services/DecisionMatrixBuilder.php         resolve(), FIXED_BOUNDS
```

## Pengujian

| Test | Menjaga |
|---|---|
| `test_rerata_rapor_dihitung_dari_seluruh_semester` | Rerata memakai semester 1–5 |
| `test_rerata_rapor_konstan_tetap_ternormalisasi_pada_skala_aslinya` | Batas tetap dipakai, bukan batas sampel |
| `test_daya_pisah_kriteria_dihitung_dari_rentang_bukan_dari_bobot` | Daya pisah C1 nol meski bobotnya besar |
| `test_nilai_rapor_menerima_desimal_dengan_titik_maupun_koma` | Koma diterima sebagai pemisah desimal |
| `test_nilai_dibulatkan_ke_dua_desimal_bukan_dipangkas_diam_diam` | Angka tersimpan sama dengan angka yang dihitung |
