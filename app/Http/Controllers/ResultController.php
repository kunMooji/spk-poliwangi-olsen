<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Services\ExplanationService;
use App\Support\Riasec;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Dasbor hasil tes: profil kepribadian RIASEC dan peringkat rekomendasi prodi.
 */
class ResultController extends Controller
{
    public function show(Assessment $assessment, ExplanationService $explanation): View|RedirectResponse
    {
        $this->authorize('view', $assessment);

        if (! $assessment->isCompleted()) {
            return redirect()
                ->route('assessments.questionnaire', $assessment)
                ->with('info', 'Kuesioner belum selesai, sehingga hasil belum dapat ditampilkan.');
        }

        return view('assessments.result', $this->resultData($assessment, $explanation));
    }

    /**
     * Data lembar hasil, dipakai bersama tampilan layar dan lembar cetak.
     *
     * Keduanya harus menampilkan angka yang persis sama — lembar cetak yang
     * berbeda dari layar akan menimbulkan keraguan atas hasilnya.
     *
     * @return array<string, mixed>
     */
    private function resultData(Assessment $assessment, ExplanationService $explanation): array
    {
        $assessment->load([
            'results.studyProgram',
            'priorities.studyProgram',
            'recommendedProgram',
            'primaryProgram',
            'period',
        ]);

        $percentages = $assessment->riasecPercentages();

        $snapshot = $assessment->weights_snapshot ?? [];

        $recommendedResult = $assessment->results
            ->firstWhere('study_program_id', $assessment->recommended_program_id);

        $primaryResult = $assessment->results
            ->firstWhere('study_program_id', $assessment->primary_program_id);

        // Kontribusi w_j x r_ij menjelaskan kriteria mana yang mengangkat dan
        // mana yang menahan prodi rekomendasi.
        $contributions = $recommendedResult
            ? $explanation->contributions($recommendedResult->normalized ?? [], $snapshot)
            : [];

        // Bila rekomendasi berbeda dari pilihan pertama, tunjukkan penyebabnya
        // alih-alih membiarkan calon mahasiswa menebak.
        $comparison = $primaryResult && $recommendedResult && ! $assessment->matches_preference
            ? $explanation->compare(
                $primaryResult->normalized ?? [],
                $recommendedResult->normalized ?? [],
                $snapshot,
            )
            : [];

        return [
            'assessment' => $assessment,
            'percentages' => $percentages,
            'chart' => [
                'labels' => array_map(fn ($d) => Riasec::name($d), Riasec::DIMENSIONS),
                'values' => array_values($percentages),
                'colors' => array_map(fn ($d) => Riasec::color($d), Riasec::DIMENSIONS),
            ],
            'topResults' => $assessment->results->take(5),
            'primaryResult' => $primaryResult,
            'recommendedResult' => $recommendedResult,
            'contributions' => $contributions,
            'highlights' => $explanation->highlights($contributions),
            'comparison' => \array_slice($comparison, 0, 4),
        ];
    }

    /**
     * Lembar hasil siap cetak.
     *
     * Dibuat sebagai halaman HTML ber-`@media print`, bukan lewat pustaka PDF,
     * supaya tidak menambah dependensi: peramban mencetaknya ke PDF sendiri.
     * Berkas inilah yang dibawa calon mahasiswa untuk didiskusikan dengan orang
     * tua atau guru BK.
     */
    public function print(Assessment $assessment, ExplanationService $explanation): View|RedirectResponse
    {
        $this->authorize('view', $assessment);

        if (! $assessment->isCompleted()) {
            return redirect()
                ->route('assessments.questionnaire', $assessment)
                ->with('info', 'Kuesioner belum selesai, sehingga lembar hasil belum dapat dicetak.');
        }

        return view('assessments.print', $this->resultData($assessment, $explanation));
    }

    /**
     * Rincian tahapan CoCoSo — bahan lampiran laporan.
     *
     * Seluruh angka dibaca dari kolom yang sudah tersimpan saat perhitungan,
     * bukan dihitung ulang, sehingga tetap konsisten dengan hasil aslinya.
     */
    public function calculation(Assessment $assessment): View|RedirectResponse
    {
        $this->authorize('view', $assessment);

        if (! $assessment->isCompleted()) {
            return redirect()->route('assessments.questionnaire', $assessment);
        }

        $assessment->load('results.studyProgram');

        $weights = $assessment->weights_snapshot ?? [];

        return view('assessments.calculation', [
            'assessment' => $assessment,
            'weights' => $weights,
            'codes' => array_keys($weights),
            'results' => $assessment->results,
        ]);
    }
}
