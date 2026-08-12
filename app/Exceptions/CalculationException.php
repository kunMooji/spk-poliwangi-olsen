<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Dilempar ketika perhitungan rekomendasi tidak dapat dijalankan, misalnya
 * karena data master belum lengkap atau kuesioner belum selesai diisi.
 */
class CalculationException extends RuntimeException
{
    public static function noActiveCriteria(): self
    {
        return new self('Belum ada kriteria aktif. Aktifkan minimal satu kriteria pada menu Kriteria.');
    }

    public static function notEnoughPrograms(int $count): self
    {
        return new self(
            "Perhitungan CoCoSo membutuhkan minimal 2 program studi aktif, saat ini tersedia {$count}."
        );
    }

    public static function noAnswers(): self
    {
        return new self('Kuesioner RIASEC belum diisi, sehingga rekomendasi tidak dapat dihitung.');
    }

    public static function zeroTotalWeight(): self
    {
        return new self('Total bobot kriteria aktif bernilai 0. Perbarui bobot pada menu Kriteria.');
    }
}
