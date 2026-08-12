<?php

namespace App\Http\Controllers;

use App\Exceptions\CalculationException;
use App\Http\Requests\StoreAnswersRequest;
use App\Http\Requests\StoreAssessmentRequest;
use App\Models\Assessment;
use App\Models\RiasecQuestion;
use App\Models\Setting;
use App\Models\StudyProgram;
use App\Services\RecommendationService;
use App\Support\Riasec;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Alur tes calon mahasiswa: biodata & nilai rapor -> kuesioner RIASEC -> hasil.
 */
class AssessmentController extends Controller
{
    /** Riwayat tes milik pengguna yang sedang masuk. */
    public function index(Request $request): View
    {
        $assessments = $request->user()->assessments()
            ->with('recommendedProgram')
            ->latest()
            ->paginate(10);

        return view('assessments.index', compact('assessments'));
    }

    /** Langkah 1 — formulir biodata, nilai rapor, dan urutan prioritas prodi. */
    public function create(Request $request): View|RedirectResponse
    {
        // Sesi yang belum selesai dilanjutkan, bukan dibuat ulang, supaya jawaban
        // kuesioner yang sudah tersimpan tidak hilang.
        $unfinished = $request->user()->assessments()->where('status', '!=', 'completed')->latest()->first();

        if ($unfinished) {
            return redirect()
                ->route('assessments.questionnaire', $unfinished)
                ->with('info', 'Anda masih memiliki tes yang belum selesai. Silakan lanjutkan pengisian kuesioner.');
        }

        return view('assessments.create', [
            'programs' => StudyProgram::query()->active()->orderBy('name')->get(),
            'subjects' => Riasec::SUBJECTS,
            'minPriorities' => (int) Setting::get('min_priorities'),
            'maxPriorities' => 5,
        ]);
    }

    public function store(StoreAssessmentRequest $request): RedirectResponse
    {
        $assessment = DB::transaction(function () use ($request) {
            $assessment = $request->user()->assessments()->create(
                $request->safe()->except('priorities') + ['status' => 'questionnaire']
            );

            foreach ($request->priorityIds() as $index => $programId) {
                $assessment->priorities()->create([
                    'study_program_id' => $programId,
                    'priority_order' => $index + 1,
                ]);
            }

            return $assessment;
        });

        return redirect()
            ->route('assessments.questionnaire', $assessment)
            ->with('success', 'Biodata tersimpan. Lanjutkan dengan mengisi kuesioner minat bakat.');
    }

    /** Langkah 2 — kuesioner RIASEC skala Likert. */
    public function questionnaire(Assessment $assessment): View|RedirectResponse
    {
        $this->authorize('update', $assessment);

        if ($assessment->isCompleted()) {
            return redirect()->route('assessments.result', $assessment);
        }

        return view('assessments.questionnaire', [
            'assessment' => $assessment,
            'questions' => RiasecQuestion::query()->active()->ordered()->get(),
            'saved' => $assessment->answers()->pluck('score', 'riasec_question_id'),
            'likert' => Riasec::LIKERT_LABELS,
        ]);
    }

    /** Simpan jawaban lalu jalankan perhitungan RIASEC + CoCoSo. */
    public function storeAnswers(
        StoreAnswersRequest $request,
        Assessment $assessment,
        RecommendationService $recommendation,
    ): RedirectResponse {
        $questions = RiasecQuestion::query()->active()->pluck('dimension', 'id');

        DB::transaction(function () use ($request, $assessment, $questions) {
            $assessment->answers()->delete();

            $rows = [];
            $now = now();

            foreach ($request->validated('answers') as $questionId => $score) {
                if (! $questions->has($questionId)) {
                    continue;
                }

                $rows[] = [
                    'assessment_id' => $assessment->id,
                    'riasec_question_id' => $questionId,
                    'dimension' => $questions[$questionId],
                    'score' => $score,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            $assessment->answers()->insert($rows);
        });

        try {
            $recommendation->calculate($assessment->fresh(['priorities', 'answers']));
        } catch (CalculationException $exception) {
            return back()->withErrors(['answers' => $exception->getMessage()]);
        }

        return redirect()
            ->route('assessments.result', $assessment)
            ->with('success', 'Perhitungan selesai. Berikut hasil rekomendasi program studi untuk Anda.');
    }

    public function destroy(Assessment $assessment): RedirectResponse
    {
        $this->authorize('delete', $assessment);

        $assessment->delete();

        return redirect()->route('assessments.index')->with('success', 'Data tes berhasil dihapus.');
    }
}
