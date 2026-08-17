<?php

namespace App\Http\Controllers;

use App\Exceptions\CalculationException;
use App\Http\Requests\AutosaveAnswersRequest;
use App\Http\Requests\StoreAnswersRequest;
use App\Http\Requests\StoreAssessmentRequest;
use App\Models\Assessment;
use App\Models\AssessmentAnswer;
use App\Models\Period;
use App\Models\RiasecQuestion;
use App\Models\Setting;
use App\Models\StudyProgram;
use App\Models\Subject;
use App\Services\RecommendationService;
use App\Support\Rapor;
use App\Support\Riasec;
use Illuminate\Http\JsonResponse;
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

        $programs = StudyProgram::query()->active()->with('supportSubjects')->orderBy('name')->get();
        $supportSubjects = Rapor::supportSubjects();

        return view('assessments.create', [
            'programs' => $programs,
            'semesters' => Rapor::SEMESTERS,
            'supportSubjects' => $supportSubjects,
            // Dipakai halaman untuk menyorot mapel milik prodi yang sedang dipilih.
            'programSubjectMap' => $programs
                ->mapWithKeys(fn (StudyProgram $program) => [
                    $program->id => $program->supportSubjects->pluck('id'),
                ]),
            'extraSubjects' => Rapor::selectableExtraSubjects(),
            'addedSubjects' => $this->previouslyAddedSubjects($supportSubjects),
            'minPriorities' => (int) Setting::get('min_priorities'),
            'maxPriorities' => 5,
        ]);
    }

    /**
     * Mata pelajaran yang ditambahkan sendiri responden pada percobaan sebelumnya.
     *
     * Tanpa ini, kiriman yang gagal validasi akan menghapus mapel tambahan dari
     * layar sementara nilai lainnya tetap terisi — responden harus menambahkannya
     * ulang tanpa tahu sebabnya.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\Subject>  $supportSubjects
     * @return array<int, array{id: int, name: string, score: string}>
     */
    private function previouslyAddedSubjects($supportSubjects): array
    {
        $submitted = array_keys((array) old('subject_scores', []));
        $extraIds = array_diff(array_map('intval', $submitted), $supportSubjects->pluck('id')->all());

        if ($extraIds === []) {
            return [];
        }

        return Subject::query()
            ->whereIn('id', $extraIds)
            ->ordered()
            ->get()
            ->map(fn (Subject $subject) => [
                'id' => $subject->id,
                'name' => $subject->display_name,
                'score' => (string) old("subject_scores.{$subject->id}", ''),
            ])
            ->all();
    }

    public function store(StoreAssessmentRequest $request): RedirectResponse
    {
        // Gelombang disalin saat tes dibuat, bukan dibaca ulang saat rekap.
        // Dengan begitu mengganti gelombang aktif tidak memindahkan tes lama.
        $period = Period::current();

        $assessment = DB::transaction(function () use ($request, $period) {
            $assessment = $request->user()->assessments()->create(
                $request->safe()->except('priorities', 'rapor_semesters', 'subject_scores') + [
                    'rapor_average' => $request->raporAverage(),
                    'status' => 'questionnaire',
                    'period_id' => $period?->id,
                ]
            );

            foreach ($request->raporSemesters() as $semester => $average) {
                $assessment->raporSemesters()->create([
                    'semester' => $semester,
                    'average_score' => $average,
                ]);
            }

            foreach ($request->subjectScores() as $subjectId => $score) {
                $assessment->subjectScores()->create([
                    'subject_id' => $subjectId,
                    'score' => $score,
                ]);
            }

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

    /**
     * Simpan sebagian jawaban tanpa menjalankan perhitungan.
     *
     * Dipanggil berkala oleh halaman kuesioner. Tanpa ini, jawaban hanya ada di
     * peramban sampai seluruh butir dikirim — berpindah perangkat, kehabisan
     * baterai, atau membersihkan riwayat peramban berarti mengulang dari awal.
     */
    public function autosave(AutosaveAnswersRequest $request, Assessment $assessment): JsonResponse
    {
        if ($assessment->isCompleted()) {
            return response()->json([
                'message' => 'Tes ini sudah selesai sehingga jawabannya tidak dapat diubah lagi.',
            ], 409);
        }

        $questions = RiasecQuestion::query()->active()->pluck('dimension', 'id');
        $now = now();

        $rows = [];
        foreach ($request->answers() as $questionId => $score) {
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

        // Upsert memakai indeks unik (assessment_id, riasec_question_id),
        // sehingga menjawab ulang butir yang sama menimpa nilai sebelumnya
        // alih-alih menggandakan barisnya.
        if ($rows !== []) {
            AssessmentAnswer::query()->upsert($rows, ['assessment_id', 'riasec_question_id'], ['dimension', 'score', 'updated_at']);
        }

        return response()->json([
            'saved' => count($rows),
            'saved_at' => $now->format('H:i'),
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
