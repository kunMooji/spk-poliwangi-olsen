<?php

namespace App\Services;

/**
 * Menerjemahkan angka CoCoSo menjadi alasan yang dapat dibaca calon mahasiswa.
 *
 * Sebuah sistem pendukung keputusan tidak cukup mengumumkan pemenang; pemakainya
 * perlu tahu kriteria mana yang mengangkat dan mana yang menahan sebuah prodi
 * supaya bisa menimbang sendiri.
 *
 * Seluruh angka dibaca dari jejak perhitungan yang tersimpan (`normalized` dan
 * `weights_snapshot`), bukan dihitung ulang, sehingga penjelasan selalu konsisten
 * dengan hasil yang ditampilkan.
 */
final class ExplanationService
{
    /** Batas r_ij untuk menyebut sebuah kriteria kuat atau lemah. */
    private const STRONG = 0.70;

    private const WEAK = 0.30;

    /**
     * Kontribusi tiap kriteria terhadap S_i, yaitu w_j x r_ij, terurut dari
     * penyumbang terbesar.
     *
     * @param  array<string, float>  $normalized  r_ij hasil perhitungan
     * @param  array<string, array{name: string, weight: float, type?: string, source?: string}>  $snapshot
     * @return array<int, array{code: string, name: string, weight: float, normalized: float, contribution: float, share: float, level: string}>
     */
    public function contributions(array $normalized, array $snapshot): array
    {
        $rows = [];
        $total = 0.0;

        foreach ($snapshot as $code => $meta) {
            $r = (float) ($normalized[$code] ?? 0.0);
            $weight = (float) $meta['weight'];
            $contribution = $weight * $r;
            $total += $contribution;

            $rows[] = [
                'code' => $code,
                'name' => $meta['name'],
                'weight' => $weight,
                'normalized' => $r,
                'contribution' => $contribution,
                'share' => 0.0,
                'level' => match (true) {
                    $r >= self::STRONG => 'kuat',
                    $r <= self::WEAK => 'lemah',
                    default => 'sedang',
                },
            ];
        }

        foreach ($rows as $index => $row) {
            $rows[$index]['share'] = $total > 0.0 ? round($row['contribution'] / $total * 100, 1) : 0.0;
        }

        usort($rows, fn (array $a, array $b) => $b['contribution'] <=> $a['contribution']);

        return $rows;
    }

    /**
     * Selisih kontribusi antara dua prodi, terurut dari penyebab terbesar.
     *
     * Dipakai untuk menjawab "kenapa pilihan pertama saya kalah?" — kriteria
     * dengan selisih paling negatif adalah jawabannya.
     *
     * @param  array<string, float>  $subject  r_ij prodi yang ditanyakan
     * @param  array<string, float>  $against  r_ij prodi pembanding
     * @param  array<string, array{name: string, weight: float, type?: string, source?: string}>  $snapshot
     * @return array<int, array{code: string, name: string, subject: float, against: float, delta: float}>
     */
    public function compare(array $subject, array $against, array $snapshot): array
    {
        $rows = [];

        foreach ($snapshot as $code => $meta) {
            $weight = (float) $meta['weight'];
            $subjectValue = $weight * (float) ($subject[$code] ?? 0.0);
            $againstValue = $weight * (float) ($against[$code] ?? 0.0);

            $rows[] = [
                'code' => $code,
                'name' => $meta['name'],
                'subject' => $subjectValue,
                'against' => $againstValue,
                'delta' => $subjectValue - $againstValue,
            ];
        }

        usort($rows, fn (array $a, array $b) => abs($b['delta']) <=> abs($a['delta']));

        return $rows;
    }

    /**
     * Ringkasan satu kalimat: kriteria terkuat dan terlemah sebuah prodi.
     *
     * @param  array<int, array{name: string, level: string}>  $contributions
     * @return array{strengths: array<int, string>, weaknesses: array<int, string>}
     */
    public function highlights(array $contributions, int $limit = 3): array
    {
        $strengths = [];
        $weaknesses = [];

        foreach ($contributions as $row) {
            if ($row['level'] === 'kuat' && count($strengths) < $limit) {
                $strengths[] = $row['name'];
            }
        }

        // Ditelusuri dari belakang supaya kelemahan terparah muncul lebih dulu.
        foreach (array_reverse($contributions) as $row) {
            if ($row['level'] === 'lemah' && count($weaknesses) < $limit) {
                $weaknesses[] = $row['name'];
            }
        }

        return ['strengths' => $strengths, 'weaknesses' => $weaknesses];
    }
}
