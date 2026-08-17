<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Period;
use App\Models\Setting;
use App\Models\StudyProgram;
use App\Services\DecisionMatrixBuilder;
use App\Services\SensitivityService;
use App\Support\Rapor;
use App\Support\Riasec;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Rekapitulasi seluruh sesi tes calon mahasiswa untuk keperluan monitoring.
 *
 * Admin hanya membaca dan menghapus — data tes tidak pernah diubah dari sini
 * supaya hasil perhitungan tetap dapat dipertanggungjawabkan.
 */
class AssessmentRecapController extends Controller
{
    public function index(Request $request): View
    {
        $assessments = $this->filtered($request)
            ->with(['user', 'period', 'recommendedProgram', 'primaryProgram'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.recap.index', [
            'assessments' => $assessments,
            'programs' => StudyProgram::query()->orderBy('name')->get(),
            'periods' => Period::query()->orderByDesc('starts_at')->orderByDesc('id')->get(),
            'dimensions' => Riasec::LABELS,
            'totalAll' => Assessment::query()->count(),
            'totalCompleted' => Assessment::query()->completed()->count(),
        ]);
    }

    /**
     * Rekap dalam format CSV, mengikuti penyaringan yang sedang aktif di layar.
     *
     * Dialirkan baris demi baris lewat `streamDownload` supaya rekap besar tidak
     * perlu dirakit seluruhnya di memori lebih dulu.
     */
    public function export(Request $request): StreamedResponse
    {
        $query = $this->filtered($request)
            ->with(['user', 'period', 'recommendedProgram', 'primaryProgram', 'raporSemesters', 'subjectScores']);

        $filename = 'rekap-tes-'.now()->format('Ymd-His').'.csv';

        // Kolom mapel pendukung mengikuti mapel yang benar-benar dipakai prodi,
        // sehingga rekap tidak menyimpan judul mapel yang sudah tidak relevan.
        $supportSubjects = Rapor::supportSubjects();

        return response()->streamDownload(function () use ($query, $supportSubjects) {
            $handle = fopen('php://output', 'wb');

            // BOM UTF-8 supaya Excel di Windows tidak salah membaca huruf beraksen.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Kode Tes', 'Gelombang', 'Tahun Akademik', 'Nama Lengkap', 'Surel Akun',
                'Jenis Kelamin', 'Asal Sekolah', 'Jurusan Sekolah', 'Tahun Lulus',
                'Rerata Rapor',
                ...array_map(fn (int $semester) => 'Semester '.$semester, Rapor::SEMESTERS),
                ...$supportSubjects->pluck('name')->all(),
                'Kode Holland', 'Tipe Dominan',
                'Pilihan Pertama', 'Prodi Rekomendasi', 'Nilai K', 'Nilai K Ternormalisasi',
                'Sesuai Pilihan', 'Ambang Batas', 'Status', 'Tanggal Selesai',
            ]);

            $query->chunk(200, function ($rows) use ($handle, $supportSubjects) {
                foreach ($rows as $assessment) {
                    $semesterScores = $assessment->raporSemesters->pluck('average_score', 'semester');
                    $subjectScores = $assessment->subjectScoreMap();

                    fputcsv($handle, [
                        $assessment->code,
                        $assessment->period?->name ?? '-',
                        $assessment->period?->academic_year ?? '-',
                        $assessment->full_name,
                        $assessment->user?->email ?? '-',
                        match ($assessment->gender) {
                            'L' => 'Laki-laki',
                            'P' => 'Perempuan',
                            default => '-',
                        },
                        $assessment->school_name ?? '-',
                        $assessment->school_major ?? '-',
                        $assessment->graduation_year ?? '-',
                        $assessment->rapor_average,
                        ...array_map(
                            fn (int $semester) => $semesterScores[$semester] ?? '-',
                            Rapor::SEMESTERS,
                        ),
                        // Mapel yang tidak ditempuh responden ditandai "-", bukan 0,
                        // supaya tidak terbaca sebagai nilai nol yang sebenarnya.
                        ...$supportSubjects
                            ->map(fn ($subject) => $subjectScores[$subject->id] ?? '-')
                            ->all(),
                        $assessment->holland_code ?? '-',
                        $assessment->dominant_type ? Riasec::name($assessment->dominant_type) : '-',
                        $assessment->primaryProgram?->full_name ?? '-',
                        $assessment->recommendedProgram?->full_name ?? '-',
                        $assessment->recommended_k_value !== null ? number_format($assessment->recommended_k_value, 6, ',', '') : '-',
                        $assessment->recommended_k_normal !== null ? number_format($assessment->recommended_k_normal, 4, ',', '') : '-',
                        $assessment->matches_preference === null ? '-' : ($assessment->matches_preference ? 'Sesuai' : 'Tidak sesuai'),
                        $assessment->threshold_used !== null ? number_format($assessment->threshold_used, 2, ',', '') : '-',
                        $assessment->isCompleted() ? 'Selesai' : 'Belum selesai',
                        $assessment->completed_at?->format('d/m/Y H:i') ?? '-',
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Penyaringan bersama halaman rekap dan ekspor CSV.
     *
     * Dipakai keduanya supaya berkas yang diunduh benar-benar berisi baris yang
     * sedang dilihat admin, bukan seluruh tabel.
     *
     * @return Builder<Assessment>
     */
    private function filtered(Request $request): Builder
    {
        return Assessment::query()
            ->when($request->string('q')->trim()->value(), function ($query, string $keyword) {
                $query->where(fn ($q) => $q->where('full_name', 'like', "%{$keyword}%")
                    ->orWhere('code', 'like', "%{$keyword}%")
                    ->orWhere('school_name', 'like', "%{$keyword}%")
                    ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$keyword}%")));
            })
            ->when($request->input('status'), fn ($query, $status) => $status === 'completed'
                ? $query->completed()
                : $query->where('status', '!=', 'completed'))
            ->when($request->input('program'), fn ($query, $program) => $query->where('recommended_program_id', $program))
            ->when($request->input('dominant'), fn ($query, $dominant) => $query->where('dominant_type', $dominant))
            ->when($request->input('period'), fn ($query, $period) => $period === 'none'
                ? $query->whereNull('period_id')
                : $query->where('period_id', $period))
            ->when($request->filled('match'), fn ($query) => $query->where('matches_preference', $request->input('match') === 'sesuai'));
    }

    public function show(Assessment $assessment): View
    {
        $assessment->load([
            'user',
            'priorities.studyProgram',
            'results.studyProgram',
            'recommendedProgram',
            'primaryProgram',
            'raporSemesters',
            'subjectScores.subject',
        ]);

        return view('admin.recap.show', [
            'assessment' => $assessment,
            'percentages' => $assessment->riasecPercentages(),
            'topResults' => $assessment->results->take(5),
        ]);
    }

    /**
     * Uji ketahanan hasil terhadap perubahan λ dan bobot kriteria.
     *
     * Matriks keputusan dibaca dari jejak yang tersimpan saat perhitungan, bukan
     * disusun ulang dari data master, supaya simulasi ini benar-benar mengukur
     * sesi tes tersebut apa adanya meski data master sudah berubah sejak itu.
     */
    public function sensitivity(Assessment $assessment, SensitivityService $sensitivity): View|RedirectResponse
    {
        if (! $assessment->isCompleted()) {
            return redirect()
                ->route('admin.recap.show', $assessment)
                ->with('info', 'Analisis sensitivitas hanya tersedia untuk tes yang sudah selesai.');
        }

        $assessment->load('results.studyProgram');

        $snapshot = $assessment->weights_snapshot ?? [];

        $analysis = $sensitivity->analyze(
            matrix: $assessment->results->mapWithKeys(fn ($result) => [$result->study_program_id => $result->matrix])->all(),
            weights: array_map(fn (array $meta) => (float) $meta['weight'], $snapshot),
            types: array_map(fn (array $meta) => $meta['type'] ?? 'benefit', $snapshot),
            lambda: (float) $assessment->lambda_used,
            epsilon: (float) Setting::get('epsilon'),
            // Wajib sama dengan batas yang dipakai saat perhitungan asli, kalau
            // tidak simulasi ini menormalisasi kolom nilai rapor dengan cara yang
            // berbeda dan pemenang baseline-nya bisa meleset dari hasil tersimpan.
            bounds: DecisionMatrixBuilder::boundsFor(
                array_map(fn (array $meta) => $meta['source'] ?? null, $snapshot)
            ),
        );

        return view('admin.recap.sensitivity', [
            'assessment' => $assessment,
            'analysis' => $analysis,
            'snapshot' => $snapshot,
            'programs' => $assessment->results->pluck('studyProgram', 'study_program_id'),
        ]);
    }

    public function destroy(Assessment $assessment): RedirectResponse
    {
        $this->authorize('delete', $assessment);

        $code = $assessment->code;
        $assessment->delete();

        return redirect()
            ->route('admin.recap.index')
            ->with('success', "Data tes {$code} berhasil dihapus.");
    }
}
