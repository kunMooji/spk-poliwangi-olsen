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
        'R' => 'Menyukai pekerjaan konkret yang melibatkan mesin, alat, dan aktivitas fisik.',
        'I' => 'Menyukai kegiatan meneliti, menganalisis data, dan memecahkan masalah abstrak.',
        'A' => 'Menyukai kegiatan bebas dan ekspresif yang menuntut imajinasi serta estetika.',
        'S' => 'Menyukai kegiatan membantu, mengajar, dan berinteraksi dengan orang lain.',
        'E' => 'Menyukai kegiatan memimpin, memengaruhi, menjual, dan mengambil keputusan.',
        'C' => 'Menyukai kegiatan yang tertib, sistematis, dan berhubungan dengan data serta administrasi.',
    ];

    public const COLORS = [
        'R' => '#2563eb',
        'I' => '#0d9488',
        'A' => '#c026d3',
        'S' => '#ea580c',
        'E' => '#dc2626',
        'C' => '#65a30d',
    ];

    /** Label skala Likert 1-5 pada kuesioner. */
    public const LIKERT_LABELS = [
        1 => 'Sangat Tidak Sesuai',
        2 => 'Tidak Sesuai',
        3 => 'Netral',
        4 => 'Sesuai',
        5 => 'Sangat Sesuai',
    ];

    /** Versi ringkas untuk tombol pilihan pada layar sempit. */
    public const LIKERT_SHORT = [
        1 => 'STS',
        2 => 'TS',
        3 => 'N',
        4 => 'S',
        5 => 'SS',
    ];

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
