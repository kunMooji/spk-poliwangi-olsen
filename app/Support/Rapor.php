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

    /**
     * Batas mata pelajaran pendukung menurut aturan SNBP.
     *
     * Batas ini berlaku **per responden**, bukan per prodi. Sebuah prodi boleh
     * menautkan lebih dari dua mapel asalkan yang benar-benar berlaku bagi satu
     * peserta didik tetap paling banyak dua.
     *
     * Alasannya, satu prodi menerima pendaftar dari jenjang dan jurusan yang
     * berbeda-beda. Teknologi Rekayasa Otomotif, misalnya, wajar memakai
     * Matematika dan Fisika untuk pendaftar SMA, tetapi Teknik Kendaraan Ringan
     * untuk pendaftar SMK Teknologi Manufaktur dan Rekayasa. Bila batas dua
     * dihitung per prodi, salah satu kelompok pasti tidak kebagian — dan yang
     * paling dirugikan justru peserta didik SMK, yang merupakan pasar utama
     * pendidikan vokasi.
     *
     * Yang dijaga tetap sama dengan maksud aturan SNBP: seorang pendaftar tidak
     * pernah dinilai dengan lebih dari dua mapel pendukung.
     */
    public const MAX_SUPPORT_SUBJECTS = 2;

    /**
     * Jenjang sekolah asal responden.
     *
     * Berbeda dari `Subject::EDUCATION_LEVELS` yang memuat "umum": seorang
     * peserta didik selalu berasal dari salah satu jenjang, sedangkan sebuah
     * mata pelajaran bisa saja ditempuh oleh keduanya.
     */
    public const STUDENT_LEVELS = ['SMA' => 'SMA', 'SMK' => 'SMK'];

    /**
     * Nama `group` untuk mapel yang ditempuh seluruh peserta didik pada satu
     * jenjang, terlepas dari jurusan atau rumpun keahliannya.
     */
    public const COMMON_GROUP = 'Umum';

    /**
     * Pilihan jurusan per jenjang, diturunkan dari data mapel yang ada.
     *
     * Sengaja dibaca dari `subjects.group` dan bukan ditulis sebagai daftar
     * tetap: begitu admin menambah mapel pada rumpun keahlian baru, rumpun itu
     * langsung muncul sebagai pilihan tanpa perlu mengubah kode. Kelompok
     * "Umum" dikecualikan karena ia bukan jurusan melainkan mapel wajib.
     *
     * @return array<string, array<int, string>>
     */
    public static function majorOptions(): array
    {
        $options = array_fill_keys(array_keys(self::STUDENT_LEVELS), []);

        $groups = Subject::query()
            ->active()
            ->whereIn('education_level', array_keys(self::STUDENT_LEVELS))
            ->whereNotNull('group')
            ->where('group', '!=', self::COMMON_GROUP)
            ->ordered()
            ->get()
            ->groupBy('education_level');

        foreach ($groups as $level => $rows) {
            $options[$level] = $rows->pluck('group')->unique()->sort()->values()->all();
        }

        return $options;
    }

    /**
     * Jenjang dan kelompok tiap mapel, dipakai form untuk menyaring isian nilai
     * sesuai asal sekolah responden.
     *
     * Aturan penyaringannya: mapel berjenjang "umum" ditanyakan kepada siapa
     * pun; selebihnya hanya ditanyakan bila jenjangnya cocok, dan kelompoknya
     * entah "Umum" atau sama dengan jurusan yang dipilih responden.
     *
     * @param  Collection<int, Subject>  $subjects
     * @return array<int, array{level: string, group: string|null}>
     */
    public static function profiles(Collection $subjects): array
    {
        return $subjects
            ->mapWithKeys(fn (Subject $subject) => [
                $subject->id => [
                    'level' => $subject->education_level,
                    'group' => $subject->group,
                ],
            ])
            ->all();
    }

    /**
     * Apakah sebuah mapel ditempuh oleh peserta didik dengan jenjang dan jurusan
     * tertentu.
     *
     * Ini aturan yang sama dengan penyaringan di form pengisian nilai
     * (`matchesLevel` pada create.blade.php), ditulis ulang di sisi peladen
     * supaya perhitungan tidak bergantung pada apa yang kebetulan tampil di
     * layar. Keduanya harus diubah bersama-sama.
     *
     * Jenjang responden yang belum diketahui — data tes lama, sebelum kolom
     * `education_level` ada — sengaja tidak disaring sama sekali, agar hasil
     * lamanya tidak berubah surut.
     */
    public static function appliesTo(Subject $subject, ?string $level, ?string $major): bool
    {
        if ($subject->education_level === 'umum') {
            return true;
        }

        if ($level === null || $level === '') {
            return true;
        }

        if ($subject->education_level !== $level) {
            return false;
        }

        if ($major === null || $major === '') {
            return true;
        }

        return $subject->group === self::COMMON_GROUP || $subject->group === $major;
    }

    /**
     * Kombinasi jenjang dan jurusan yang melampaui batas mapel pendukung.
     *
     * Dipakai memvalidasi penyuntingan prodi oleh admin: yang diperiksa bukan
     * jumlah mapel yang ditautkan, melainkan berapa yang berlaku bagi tiap
     * kemungkinan asal sekolah pendaftar.
     *
     * @param  Collection<int, Subject>  $subjects
     * @return array<int, string> label kombinasi yang kelebihan, mis. "SMA · IPA"
     */
    public static function overloadedCombinations(Collection $subjects): array
    {
        $overloaded = [];

        foreach (self::majorOptions() as $level => $majors) {
            // Jenjang yang belum punya jurusan apa pun tetap diperiksa lewat
            // mapel wajibnya, sehingga batasnya tidak bisa dilewati diam-diam.
            foreach ($majors === [] ? [null] : $majors as $major) {
                $applicable = $subjects
                    ->filter(fn (Subject $subject) => self::appliesTo($subject, $level, $major))
                    ->count();

                if ($applicable > self::MAX_SUPPORT_SUBJECTS) {
                    $overloaded[] = trim($level.($major === null ? '' : ' · '.$major))
                        .' ('.$applicable.' mapel)';
                }
            }
        }

        return $overloaded;
    }

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
