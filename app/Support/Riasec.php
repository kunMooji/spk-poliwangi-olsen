<?php

namespace App\Support;

/**
 * Konstanta model heksagonal RIASEC (Holland Codes) dan daftar mata pelajaran.
 *
 * Dipakai bersama oleh model, service perhitungan, seeder, dan view supaya
 * urutan dimensi konsisten di seluruh sistem.
 */
final class Riasec
{
    /** Urutan baku dimensi — jangan diubah, dipakai sebagai urutan vektor. */
    public const DIMENSIONS = ['R', 'I', 'A', 'S', 'E', 'C'];

    public const NAMES = [
        'R' => 'Realistic',
        'I' => 'Investigative',
        'A' => 'Artistic',
        'S' => 'Social',
        'E' => 'Enterprising',
        'C' => 'Conventional',
    ];

    public const LABELS = [
        'R' => 'Realistic (Praktis)',
        'I' => 'Investigative (Analitis)',
        'A' => 'Artistic (Kreatif)',
        'S' => 'Social (Sosial)',
        'E' => 'Enterprising (Kepemimpinan)',
        'C' => 'Conventional (Terstruktur)',
    ];

    public const DESCRIPTIONS = [
        'R' => 'Menyukai pekerjaan konkret yang melibatkan mesin, alat, dan aktivitas fisik. Cenderung praktis dan menyukai hasil kerja yang bisa dilihat serta disentuh langsung, seperti di bidang teknik, otomotif, atau elektronika.',
        'I' => 'Menyukai kegiatan meneliti, menganalisis data, dan memecahkan masalah abstrak. Berpikir sistematis dengan rasa ingin tahu tinggi, sehingga cocok pada bidang sains, riset, maupun teknologi informasi.',
        'A' => 'Menyukai kegiatan bebas dan ekspresif yang menuntut imajinasi serta estetika. Lebih nyaman bekerja tanpa aturan kaku, dan sering unggul di bidang desain, seni, maupun penulisan kreatif.',
        'S' => 'Menyukai kegiatan membantu, mengajar, dan berinteraksi dengan orang lain. Empati dan kemampuan komunikasi yang kuat membuat tipe ini menonjol di bidang pendidikan, kesehatan, maupun layanan masyarakat.',
        'E' => 'Menyukai kegiatan memimpin, memengaruhi, menjual, dan mengambil keputusan. Percaya diri dan berorientasi target, sehingga sering tertarik pada bidang bisnis, kewirausahaan, maupun manajemen.',
        'C' => 'Menyukai kegiatan yang tertib, sistematis, dan berhubungan dengan data serta administrasi. Teliti dan menyukai keteraturan, biasanya cocok pada bidang akuntansi, administrasi, maupun pengelolaan data.',
    ];

    /** Ringkasan untuk rincian dimensi pada hasil tes. */
    public const SHORT_DESCRIPTIONS = [
        'R' => 'Praktis dan nyaman bekerja langsung dengan alat.',
        'I' => 'Analitis, suka riset, data, dan pemecahan masalah.',
        'A' => 'Kreatif serta ekspresif dalam menghasilkan karya.',
        'S' => 'Senang membantu dan berinteraksi dengan orang lain.',
        'E' => 'Percaya diri memimpin dan berorientasi pada target.',
        'C' => 'Teliti, terstruktur, dan nyaman mengelola data.',
    ];

    public const COLORS = [
        'R' => '#2563eb',
        'I' => '#0d9488',
        'A' => '#c026d3',
        'S' => '#ea580c',
        'E' => '#dc2626',
        'C' => '#65a30d',
    ];

    /**
     * Label skala Likert menurut BANYAKNYA titik, bukan menurut nilainya.
     *
     * Skala kuesioner dapat disetel admin lewat `likert_min` dan `likert_max`,
     * sehingga labelnya tidak boleh dipatok pada 1-5. Kunci larik ini adalah
     * jumlah titik skala; nilainya dipetakan ke rentang yang sedang berlaku.
     *
     * Titik tengah hanya ada pada jumlah ganjil. Skala genap sengaja tidak
     * diberi "Netral" — itu memang sifat skala genap (forced choice), bukan
     * label yang kebetulan hilang.
     */
    private const LIKERT_ANCHORS = [
        2 => ['Tidak Sesuai', 'Sesuai'],
        3 => ['Tidak Sesuai', 'Netral', 'Sesuai'],
        4 => ['Sangat Tidak Sesuai', 'Tidak Sesuai', 'Sesuai', 'Sangat Sesuai'],
        5 => ['Sangat Tidak Sesuai', 'Tidak Sesuai', 'Netral', 'Sesuai', 'Sangat Sesuai'],
        6 => ['Sangat Tidak Sesuai', 'Tidak Sesuai', 'Agak Tidak Sesuai', 'Agak Sesuai', 'Sesuai', 'Sangat Sesuai'],
        7 => ['Sangat Tidak Sesuai', 'Tidak Sesuai', 'Agak Tidak Sesuai', 'Netral', 'Agak Sesuai', 'Sesuai', 'Sangat Sesuai'],
    ];

    /** Singkatan label, dipakai pada tombol pilihan di layar sempit. */
    private const LIKERT_ANCHORS_SHORT = [
        2 => ['TS', 'S'],
        3 => ['TS', 'N', 'S'],
        4 => ['STS', 'TS', 'S', 'SS'],
        5 => ['STS', 'TS', 'N', 'S', 'SS'],
        6 => ['STS', 'TS', 'ATS', 'AS', 'S', 'SS'],
        7 => ['STS', 'TS', 'ATS', 'N', 'AS', 'S', 'SS'],
    ];

    /**
     * Label skala Likert untuk rentang yang sedang berlaku.
     *
     * Mengembalikan `[nilai => label]` sepanjang rentang $min..$max. Rentang di
     * luar daftar jangkar diberi label ujung saja dan nomor di antaranya —
     * lebih jujur daripada mengarang gradasi kata yang tidak punya padanan.
     *
     * @return array<int, string>
     */
    public static function likertScale(int $min, int $max, bool $short = false): array
    {
        if ($max <= $min) {
            return [$min => $short ? 'S' : 'Sesuai'];
        }

        $count = $max - $min + 1;
        $anchors = $short ? self::LIKERT_ANCHORS_SHORT : self::LIKERT_ANCHORS;

        $labels = $anchors[$count] ?? null;

        $scale = [];

        foreach (range($min, $max) as $index => $value) {
            if ($labels !== null) {
                $scale[$value] = $labels[$index];

                continue;
            }

            $scale[$value] = match (true) {
                $index === 0 => $short ? 'STS' : 'Sangat Tidak Sesuai',
                $index === $count - 1 => $short ? 'SS' : 'Sangat Sesuai',
                default => (string) $value,
            };
        }

        return $scale;
    }

    public static function name(string $dimension): string
    {
        return self::NAMES[strtoupper($dimension)] ?? $dimension;
    }

    public static function label(string $dimension): string
    {
        return self::LABELS[strtoupper($dimension)] ?? $dimension;
    }

    public static function description(string $dimension): string
    {
        return self::DESCRIPTIONS[strtoupper($dimension)] ?? '';
    }

    public static function shortDescription(string $dimension): string
    {
        return self::SHORT_DESCRIPTIONS[strtoupper($dimension)] ?? '';
    }

    public static function color(string $dimension): string
    {
        return self::COLORS[strtoupper($dimension)] ?? '#64748b';
    }

    /**
     * Ubah map dimensi => nilai menjadi kode Holland (dimensi tertinggi).
     *
     * @param  array<string, float>  $values
     */
    public static function hollandCode(array $values, int $length = 3): string
    {
        $sorted = [];
        foreach (self::DIMENSIONS as $dimension) {
            $sorted[$dimension] = (float) ($values[$dimension] ?? 0);
        }

        // arsort() mempertahankan urutan sisip untuk nilai seri, sehingga hasilnya deterministik
        arsort($sorted);

        return implode('', array_slice(array_keys($sorted), 0, $length));
    }
}
