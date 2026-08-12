<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Setting;
use App\Models\StudyProgram;
use App\Services\SensitivityService;
use App\Support\Riasec;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
        $assessments = Assessment::query()
            ->with(['user', 'recommendedProgram', 'primaryProgram'])
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
            ->when($request->filled('match'), fn ($query) => $query->where('matches_preference', $request->input('match') === 'sesuai'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.recap.index', [
            'assessments' => $assessments,
            'programs' => StudyProgram::query()->orderBy('name')->get(),
            'dimensions' => Riasec::LABELS,
            'totalAll' => Assessment::query()->count(),
            'totalCompleted' => Assessment::query()->completed()->count(),
        ]);
    }

    public function show(Assessment $assessment): View
    {
        $assessment->load([
            'user',
            'priorities.studyProgram',
            'results.studyProgram',
            'recommendedProgram',
            'primaryProgram',
        ]);

        return view('admin.recap.show', [
            'assessment' => $assessment,
            'subjects' => Riasec::SUBJECTS,
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
