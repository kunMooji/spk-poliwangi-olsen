<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Support\Rapor;
use App\Support\Riasec;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Perbandingan antar sesi tes milik calon mahasiswa yang sama.
 *
 * Calon mahasiswa yang mengulang tes ingin tahu apa yang berubah: apakah
 * profil minatnya bergeser, apakah nilai rapornya membaik, dan apakah
 * rekomendasinya ikut berpindah. Tanpa halaman ini, satu-satunya cara adalah
 * membuka dua lembar hasil bergantian dan membandingkannya sendiri.
 *
 * Seluruh angka dibaca dari sesi tes masing-masing, tidak ada yang dihitung
 * ulang, sehingga perbandingannya setia pada hasil yang pernah terbit.
 */
class AssessmentComparisonController extends Controller
{
    public function __invoke(Request $request): View
    {
        /** @var Collection<int, Assessment> $sessions */
        $sessions = $request->user()->assessments()
            ->completed()
            ->with(['recommendedProgram', 'primaryProgram', 'period', 'subjectScores'])
            ->orderByDesc('completed_at')
            ->get();

        // Bawaannya membandingkan dua tes terakhir — pertanyaan yang paling
        // sering muncul adalah "apa yang berubah sejak terakhir kali".
        $left = $this->pick($sessions, $request->input('a')) ?? $sessions->get(1);
        $right = $this->pick($sessions, $request->input('b')) ?? $sessions->get(0);

        return view('assessments.compare', [
            'sessions' => $sessions,
            'left' => $left,
            'right' => $right,
            'riasecDiff' => $left && $right ? $this->riasecDiff($left, $right) : [],
            'subjectDiff' => $left && $right ? $this->subjectDiff($left, $right) : [],
            'timeline' => $this->timeline($sessions),
        ]);
    }

    /**
     * @param  Collection<int, Assessment>  $sessions
     */
    private function pick(Collection $sessions, mixed $id): ?Assessment
    {
        return $id ? $sessions->firstWhere('id', (int) $id) : null;
    }

    /**
     * Pergeseran profil RIASEC antar dua sesi.
     *
     * @return array<int, array{dimension: string, label: string, color: string, left: float, right: float, delta: float}>
     */
    private function riasecDiff(Assessment $left, Assessment $right): array
    {
        $before = $left->riasecPercentages();
        $after = $right->riasecPercentages();

        $rows = [];
        foreach (Riasec::DIMENSIONS as $dimension) {
            $rows[] = [
                'dimension' => $dimension,
                'label' => Riasec::label($dimension),
                'color' => Riasec::color($dimension),
                'left' => $before[$dimension],
                'right' => $after[$dimension],
                'delta' => round($after[$dimension] - $before[$dimension], 2),
            ];
        }

        return $rows;
    }

    /**
     * Pergeseran nilai rapor antar dua sesi.
     *
     * @return array<int, array{label: string, left: float, right: float, delta: float}>
     */
    private function subjectDiff(Assessment $left, Assessment $right): array
    {
        $before = $left->subjectScoreMap();
        $after = $right->subjectScoreMap();

        $rows = [[
            'label' => 'Rerata seluruh mapel',
            'left' => (float) $left->rapor_average,
            'right' => (float) $right->rapor_average,
            'delta' => round((float) $right->rapor_average - (float) $left->rapor_average, 2),
        ]];

        // Hanya mapel yang tercatat di kedua sesi yang dapat dibandingkan. Daftar
        // mapel pendukung dapat berubah di antara dua sesi, dan nilainya boleh
        // kosong bila responden tidak menempuh mapel tersebut.
        foreach (Rapor::supportSubjects() as $subject) {
            $leftScore = $before[$subject->id] ?? null;
            $rightScore = $after[$subject->id] ?? null;

            if ($leftScore === null || $rightScore === null) {
                continue;
            }

            $rows[] = [
                'label' => $subject->name,
                'left' => $leftScore,
                'right' => $rightScore,
                'delta' => round($rightScore - $leftScore, 2),
            ];
        }

        return $rows;
    }

    /**
     * Riwayat singkat seluruh sesi, terurut dari yang terlama.
     *
     * @param  Collection<int, Assessment>  $sessions
     * @return Collection<int, Assessment>
     */
    private function timeline(Collection $sessions): Collection
    {
        return $sessions->sortBy('completed_at')->values();
    }
}
