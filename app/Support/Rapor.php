<?php

namespace App\Support;

use App\Models\Subject;
use Illuminate\Database\Eloquent\Collection;

/**
 * Aturan nilai rapor mengikuti skema seleksi SNBP.
 *
 * SNBP menilai dua komponen: rerata seluruh mata pelajaran (paling sedikit 50%)
 * dan paling banyak dua mata pelajaran pendukung program studi yang dituju
 * (paling banyak 50%). Kelas ini memusatkan aturan yang mengikat form pengisian,
 * validasi, dan penyusunan matriks keputusan.
 */
final class Rapor
{
    /**
     * Semester yang nilainya diminta.
     *
     * SNBP memeringkat berdasarkan rerata semua semester kecuali semester
     * terakhir. Untuk jenjang tiga tahun, itu berarti semester 1 sampai 5.
     */
    public const SEMESTERS = [1, 2, 3, 4, 5];

    /** Batas mata pelajaran pendukung per program studi menurut aturan SNBP. */
    public const MAX_SUPPORT_SUBJECTS = 2;

    /**
     * Mata pelajaran yang nilainya perlu ditanyakan kepada responden.
     *
     * Yang dikumpulkan adalah gabungan mapel pendukung seluruh prodi aktif, bukan
     * hanya prodi yang dipilih responden. CoCoSo memeringkat semua alternatif
     * sekaligus, sehingga prodi di luar daftar prioritas pun harus punya nilai —
     * justru prodi itulah yang berpotensi menjadi rekomendasi baru.
     *
     * Status aktif mapel sendiri sengaja tidak disaring: selama sebuah prodi
     * masih menautkannya, DecisionMatrixBuilder tetap membacanya. Menyembunyikan
     * mapel nonaktif dari form hanya akan membuat nilainya diam-diam jatuh ke
     * rerata umum untuk setiap responden.
     *
     * @return Collection<int, Subject>
     */
    public static function supportSubjects(): Collection
    {
        return Subject::query()
            ->whereHas('studyPrograms', fn ($query) => $query->where('study_programs.is_active', true))
            ->ordered()
            ->get();
    }

    /**
     * @return array<int, int>
     */
    public static function supportSubjectIds(): array
    {
        return self::supportSubjects()->pluck('id')->all();
    }

    /**
     * Mata pelajaran yang boleh ditambahkan sendiri oleh responden.
     *
     * Isinya seluruh mapel aktif di luar yang sudah ditanyakan. Berguna bagi
     * peserta didik SMK, yang mapel konsentrasi keahliannya belum tentu dipakai
     * prodi mana pun tetapi tetap layak tercatat pada rapornya.
     *
     * Nilai yang ditambahkan lewat jalur ini tidak dapat dipakai menyiasati
     * hasil: mapel pendukung tiap prodi tetap ditetapkan admin, sehingga sebuah
     * nilai baru hanya terpakai bila kebetulan ada prodi yang memang memakainya.
     *
     * @return Collection<int, Subject>
     */
    public static function selectableExtraSubjects(): Collection
    {
        return Subject::query()
            ->active()
            ->whereNotIn('id', self::supportSubjectIds())
            ->ordered()
            ->get();
    }
}
