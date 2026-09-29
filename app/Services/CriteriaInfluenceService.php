<?php

namespace App\Services;

use App\Models\Assessment;
use Illuminate\Support\Collection;

/**
 * Daya pisah kriteria: seberapa besar sebuah kriteria benar-benar menggeser
 * peringkat, dibandingkan bobot yang tertulis untuknya.
 *
 * Bobot saja tidak menjawab itu. S_i = Sum(w_j * r_ij), sehingga sumbangan sebuah
 * kriteria terhadap *selisih* antar alternatif adalah bobotnya dikali rentang
 * nilai ternormalisasinya — bukan bobotnya saja. Sebuah kriteria yang nilainya
 * nyaris sama untuk semua prodi tidak memisahkan apa pun betapapun besar
 * bobotnya, sementara kriteria yang nilainya terhampar lebar bisa mendominasi
 * jauh melebihi bobotnya.
 *
 * Kesenjangan ini pernah membuat C3 memegang 79% daya pisah dengan bobot 0.35,
 * sementara C1 berbobot 0.25 memegang 0% karena kolomnya konstan — dan tidak ada
 * satu pun layar yang menunjukkannya. Kelas ini menyediakan angkanya supaya
 * keadaan itu terlihat, baik per sesi maupun sebagai rerata lintas sesi.
 */
final class CriteriaInfluenceService
{
    /** Jumlah sesi terakhir yang dirata-ratakan untuk pandangan admin. */
    public const DEFAULT_SAMPLE = 30;

    /**
     * Daya pisah tiap kriteria pada satu sesi.
     *
     * @param  Collection<int, \App\Models\AssessmentResult>  $results
     * @param  array<string, array{weight: float|int|string}>  $weights  weights_snapshot sesi tersebut
     * @return array<string, array{weight: float, spread: float, share: float}>
     */
    public function forResults(Collection $results, array $weights): array
    {
        $influence = [];

        foreach ($weights as $code => $meta) {
            $column = $results
                ->map(fn ($result) => (float) ($result->normalized[$code] ?? 0.0))
                ->all();

            $weight = (float) ($meta['weight'] ?? 0.0);

            $influence[$code] = [
                'weight' => $weight,
                'spread' => $column === [] ? 0.0 : (max($column) - min($column)) * $weight,
            ];
        }

        return $this->withShares($influence);
    }

    /**
     * Rerata daya pisah dari sesi-sesi terakhir yang sudah selesai.
     *
     * Dibaca dari hasil yang tersimpan, bukan dihitung ulang, sehingga
     * menggambarkan apa yang benar-benar terjadi pada responden — termasuk sesi
     * yang dihitung dengan bobot lama, karena tiap sesi memakai snapshot-nya
     * sendiri.
     *
     * @return array<string, array{weight: float, spread: float, share: float, sessions: int}>
     */
    public function recentAverage(int $sessions = self::DEFAULT_SAMPLE): array
    {
        $assessments = Assessment::query()
            ->completed()
            ->with('results')
            ->latest('completed_at')
            ->take($sessions)
            ->get();

        $accumulated = [];
        $counted = 0;

        foreach ($assessments as $assessment) {
            $weights = $assessment->weights_snapshot ?? [];

            if ($weights === [] || $assessment->results->isEmpty()) {
                continue;
            }

            $counted++;

            foreach ($this->forResults($assessment->results, $weights) as $code => $row) {
                $accumulated[$code]['weight'][] = $row['weight'];
                $accumulated[$code]['spread'][] = $row['spread'];
            }
        }

        if ($counted === 0) {
            return [];
        }

        $influence = [];

        foreach ($accumulated as $code => $rows) {
            $influence[$code] = [
                'weight' => array_sum($rows['weight']) / count($rows['weight']),
                'spread' => array_sum($rows['spread']) / count($rows['spread']),
                'sessions' => $counted,
            ];
        }

        return $this->withShares($influence);
    }

    /**
     * Ubah daya pisah mentah menjadi porsi persen terhadap totalnya.
     *
     * @param  array<string, array<string, float|int>>  $influence
     * @return array<string, array<string, float|int>>
     */
    private function withShares(array $influence): array
    {
        $total = array_sum(array_column($influence, 'spread'));

        foreach ($influence as $code => $row) {
            $influence[$code]['share'] = $total > 0.0 ? $row['spread'] / $total * 100 : 0.0;
        }

        return $influence;
    }
}
