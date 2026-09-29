# C4 — Minat Urutan Prioritas Prodi

| | |
|---|---|
| **Kode** | C4 |
| **Sumber** (`criteria.source`) | `priority` |
| **Jenis** | benefit |
| **Bobot bawaan** | 0,05 — **terkecil dari kelimanya** |
| **Satuan** | Skor 0–100 |
| **Batas normalisasi** | tetap, 0 – 100 |
| **Daya pisah terukur** | 14,5% |

## Dari mana nilainya

Konversi urutan pilihan responden menjadi skor linear. Prioritas pertama
memperoleh 100, prioritas terakhir memperoleh `100/N`:

```php
// DecisionMatrixBuilder::priorityValue()
if ($priorityCount === 0 || ! isset($priorityRanks[$program->id])) {
    return $unselectedScore;
}

return ($priorityCount - $priorityRanks[$program->id] + 1) / $priorityCount * 100;
```

Dengan tiga prioritas (bawaan `min_priorities`):

| Urutan | Skor |
|---|---|
| Pilihan 1 | 100,00 |
| Pilihan 2 | 66,67 |
| Pilihan 3 | 33,33 |
| Tidak dipilih | `unselected_priority_score`, bawaan 0 |

## Mengapa bobotnya paling kecil

Berbeda dari empat kriteria lain, **nilai C4 bukan atribut yang melekat pada
program studi** — melainkan pernyataan keinginan calon mahasiswa itu sendiri.

Bobot besar padanya membuat sistem mengembalikan pilihan yang sudah disebutkan
responden, alih-alih memberi informasi baru. Sebuah sistem rekomendasi yang
menjawab "prodi yang cocok untukmu adalah yang tadi kamu sebut pertama" tidak
menambah apa pun pada keputusan.

Pada 0,05, C4 berperan sebagai **pemecah seri**: sanggup membalikkan keputusan
yang sudah nyaris imbang, tetapi tidak menggerakkan hasil yang selisihnya jelas.

Minat tetap tertimbang besar lewat [C3](c3-kesesuaian-riasec.md), yang
mengukurnya dengan instrumen RIASEC alih-alih menanyakan preferensi secara
langsung.

## Prioritas tidak lagi menimpa hasil

Aturan lama memakai urutan prioritas untuk **menimpa** peringkat CoCoSo dalam
kondisi tertentu. Itu sudah dihapus: minat sudah diperhitungkan sebagai C4 di
dalam matriks keputusan, sehingga memakainya sekali lagi sebagai aturan penimpa
berarti **menghitung minat dua kali** — dan membuat prodi pilihan pertama nyaris
selalu terpilih terlepas dari nilai rapor maupun kesesuaian kepribadiannya.

Rekomendasi kini mengikuti peringkat CoCoSo apa adanya. Kolom
`assessments.matches_preference` menandai apakah pilihan pertama kebetulan
menempati peringkat 1 — murni informasi pada lembar hasil, bukan aturan
keputusan.

Bukti bahwa ini bekerja: pada data nyata, **2 dari 4 sesi memenangkan prodi yang
sama sekali bukan prioritas responden.**

## Daya pisah 14,5% dengan bobot 0,05

Angka ini tampak tidak sebanding, tetapi wajar dan benar. Daya pisah adalah
`bobot × rentang ternormalisasi`, dan C4 satu-satunya kriteria yang **selalu
memakai rentang penuh**: nilai 0 dan 100 dua-duanya benar-benar muncul selama
responden memilih prioritas dan ada prodi yang tidak dipilih.

Kriteria lain hanya menempati sebagian rentangnya — C5 misalnya bergerak
0,77–0,89 dari kemungkinan 0–1. Jadi bobot kecil C4 dikali rentang penuh bisa
melampaui bobot besar yang dikali rentang sempit.

Konsekuensi praktis: **C4 adalah tuas paling sensitif di sistem ini.** Pada uji
titik balik, menaikkan C4 dari 0,05 ke 0,07–0,10 saja sudah membalikkan juara di
dua sesi. Jangan menaikkannya tanpa sadar — hasilnya akan cepat berubah menjadi
"sistem mengembalikan pilihan yang sudah disebut siswa".

## Mengapa memakai batas normalisasi tetap

Nilainya memang dibangkitkan pada skala 0–100 oleh `priorityValue()`, jadi batas
tetap sekadar menyatakan skala yang sudah ada.

Manfaatnya pada **ketahanan**. Bila admin mengubah `unselected_priority_score`
menjadi misalnya 30, batas min–maks sampel akan merentangkan 30–100 kembali
menjadi 0–1 penuh, dan diam-diam mengembalikan pengaruh C4 ke ukuran semula —
membatalkan maksud penyetelan itu. Dengan batas tetap, menaikkan skor prodi tak
terpilih benar-benar mempersempit jarak seperti yang diharapkan.

## Pengaturan terkait

| Kunci | Arti |
|---|---|
| `min_priorities` | Jumlah prodi minimal yang wajib diurutkan responden (bawaan 3) |
| `unselected_priority_score` | Skor C4 untuk prodi di luar daftar pilihan (bawaan 0) |

## Keterbatasan

Skornya linear terhadap urutan. Tidak ada bukti bahwa jarak minat antara pilihan
1 dan 2 sama besarnya dengan jarak antara pilihan 2 dan 3 — asumsi kesetaraan
interval ini melekat pada konversi dan tidak diuji.

## Berkas terkait

```
app/Services/DecisionMatrixBuilder.php   priorityValue(), priorityRanks()
app/Models/AssessmentPriority.php
app/Http/Requests/StoreAssessmentRequest.php   validasi prioritas ganda dan jumlah minimum
```

## Pengujian

| Test | Menjaga |
|---|---|
| `test_matches_preference_menandai_pilihan_pertama_yang_menempati_peringkat_satu` | Penandaan bersifat informasi, bukan aturan |
| `test_rekomendasi_selalu_prodi_dengan_peringkat_teratas` | Prioritas tidak menimpa hasil |
| `test_prioritas_kurang_dari_batas_minimum_ditolak` | `min_priorities` ditegakkan |
| `test_prioritas_ganda_ditolak` | Satu prodi tidak dapat dipilih dua kali |
| `test_mengubah_bobot_kriteria_mengubah_peringkat` | Bobot benar-benar berpengaruh |
