# Kriteria Keputusan C1–C5

Lima kriteria yang menyusun matriks keputusan `x_ij`. Tiap berkas di folder ini
menjelaskan satu kriteria: dari mana nilainya, rumusnya, bagaimana
dinormalisasi, seberapa besar pengaruh nyatanya, dan keterbatasan yang melekat
padanya.

| Kode | Nama | Sumber | Bobot | Daya pisah terukur |
|---|---|---|---|---|
| [C1](c1-rerata-rapor.md) | Rerata Rapor Seluruh Mapel | `rapor_average` | 0,25 | **0,0%** |
| [C2](c2-mapel-pendukung.md) | Nilai Mapel Pendukung Prodi | `support_subject` | 0,20 | 12,6% |
| [C3](c3-kesesuaian-riasec.md) | Kesesuaian Kepribadian RIASEC | `riasec` | 0,35 | **67,9%** |
| [C4](c4-prioritas-minat.md) | Minat Urutan Prioritas Prodi | `priority` | 0,05 | 14,5% |
| [C5](c5-tracer-study.md) | Prospek Kerja (Tracer Study) | `tracer` | 0,15 | 5,1% |

Seluruhnya bertipe **benefit** — makin besar makin baik. Tidak ada kriteria
`cost` pada konfigurasi bawaan, meskipun `CocosoService` mendukungnya.

## Membaca kolom terakhir

**Daya pisah bukan bobot.** Bobot menyatakan niat; daya pisah menyatakan pengaruh
yang benar-benar terjadi. Rumusnya:

```
daya pisah = bobot × (nilai ternormalisasi tertinggi − terendah)
```

Sumbangan sebuah kriteria terhadap **selisih** antar alternatif adalah bobotnya
dikali *rentang* nilainya, bukan bobotnya saja. Kriteria yang nilainya nyaris
sama untuk seluruh prodi tidak memisahkan apa pun betapapun besar bobotnya.

C1 adalah contoh ekstremnya: bobot 0,25 tetapi daya pisah 0,0%, karena kolomnya
konstan menurut definisi. Angka pada tabel di atas adalah rata-rata dari empat
sesi nyata; angkanya bergeser mengikuti data responden, kecuali C1 yang secara
struktural selalu nol.

Daya pisah dihitung `CriteriaInfluenceService` dan ditampilkan di dua tempat:
halaman rincian perhitungan (per sesi) dan menu Kriteria (rerata lintas sesi).

## Blok penilaian

Kelima kriteria terbagi menjadi dua kelompok dengan asal-usul berbeda:

**Blok rapor (0,45)** — C1 dan C2, mengikuti skema SNBP: komponen pertama berupa
rerata seluruh mapel (≥ 50% dari blok) dan komponen kedua berupa paling banyak
dua mapel pendukung prodi (≤ 50%). Pembagiannya 0,25 : 0,20, yaitu 55,6% : 44,4%.

**Tambahan sistem (0,55)** — C3, C4, dan C5 tidak dikenal SNBP. Ketiganya
membedakan sistem ini dari sekadar mesin perangkingan SNBP: yang dicari bukan
"siswa mana untuk prodi ini", melainkan "prodi mana untuk siswa ini".

Rasio 50:50 SNBP dihitung terhadap **blok rapor saja**, bukan terhadap total
seluruh kriteria.

## Menambah atau mengubah kriteria

`criteria.source` menentukan dari mana `x_ij` diambil, sehingga admin dapat
menambah, menonaktifkan, atau mengubah bobot **tanpa menyentuh kode perhitungan**
— selama `source`-nya salah satu dari lima nilai yang dikenal
`DecisionMatrixBuilder`. Menambah `source` baru berarti menambah cabang pada
`resolve()` dan, bila skalanya bermakna absolut, entri pada `FIXED_BOUNDS`.

Lihat [code.md §4](../code.md) untuk langkahnya.
