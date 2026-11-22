<?php

namespace App\Http\Controllers\Tpa;

use App\Http\Controllers\Controller;
use App\Models\TpaQuestion;
use App\Models\TpaTryout;
use App\Services\Tpa\QuestionGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class TryoutController extends Controller
{
    /** Batas waktu wajar per soal agar timer yang tertinggal di tab terbuka tidak merusak statistik. */
    private const MAX_MS_PER_QUESTION = 15 * 60 * 1000;

    public function start(string $category): View
    {
        $this->ensureCategory($category);

        return view('tpa.start', [
            'category' => $category,
            'meta' => config("tpa.categories.{$category}"),
        ]);
    }

    public function generate(Request $request, string $category, QuestionGenerator $generator): JsonResponse
    {
        $this->ensureCategory($category);

        $data = $request->validate([
            'difficulty' => ['required', 'integer', 'min:10', 'max:100', 'multiple_of:10'],
        ]);

        // Menyusun 15 soal bisa memakan waktu 1-3 menit.
        @set_time_limit(300);
        @ignore_user_abort(true);

        try {
            $questions = $generator->generate($category, (int) $data['difficulty']);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        $tryout = DB::transaction(function () use ($category, $data, $questions) {
            $tryout = TpaTryout::create([
                'tpa_user_id' => Auth::guard('tpa')->id(),
                'category' => $category,
                'difficulty' => $data['difficulty'],
                'status' => TpaTryout::STATUS_IN_PROGRESS,
                'max_score' => count($questions) * 5,
                'started_at' => now(),
            ]);

            foreach ($questions as $i => $q) {
                $tryout->questions()->create([...$q, 'number' => $i + 1]);
            }

            return $tryout;
        });

        return response()->json(['redirect' => route('tpa.tryout.quiz', $tryout)]);
    }

    public function quiz(TpaTryout $tryout): View|RedirectResponse
    {
        $this->authorizeTryout($tryout);

        if ($tryout->isFinished()) {
            return redirect()->route('tpa.tryout.result', $tryout);
        }

        // Kunci, skor opsi, dan pembahasan TIDAK dikirim ke browser sebelum selesai.
        $questions = $tryout->questions->map(fn (TpaQuestion $q) => [
            'id' => $q->id,
            'number' => $q->number,
            'sub_topic' => $q->sub_topic,
            'question' => $q->question,
            'options' => collect($q->options)->map(fn ($o) => ['key' => $o['key'], 'text' => $o['text']])->all(),
            'hint' => $q->hint,
            'answer' => $q->user_answer,
            'time_ms' => $q->time_spent_ms,
            'hint_used' => $q->hint_used,
        ]);

        return view('tpa.quiz', [
            'tryout' => $tryout,
            'questions' => $questions,
            'meta' => config("tpa.categories.{$tryout->category}"),
        ]);
    }

    public function answer(Request $request, TpaTryout $tryout): JsonResponse
    {
        $this->authorizeTryout($tryout);

        if ($tryout->isFinished()) {
            return response()->json(['message' => 'Tryout sudah selesai.'], 409);
        }

        $data = $request->validate([
            'question_id' => ['required', 'integer', Rule::exists('tpa_questions', 'id')->where('tpa_tryout_id', $tryout->id)],
            'answer' => ['nullable', Rule::in(['A', 'B', 'C', 'D', 'E'])],
            'elapsed_ms' => ['required', 'integer', 'min:0', 'max:'.self::MAX_MS_PER_QUESTION],
            'hint_used' => ['boolean'],
        ]);

        $question = $tryout->questions()->findOrFail($data['question_id']);

        $question->fill([
            'user_answer' => $data['answer'] ?? $question->user_answer,
            'time_spent_ms' => min(self::MAX_MS_PER_QUESTION, $question->time_spent_ms + $data['elapsed_ms']),
            'hint_used' => $question->hint_used || ($data['hint_used'] ?? false),
        ]);

        if (array_key_exists('answer', $data) && $data['answer'] !== null) {
            $question->answered_at = now();
        }

        $question->save();

        return response()->json(['ok' => true]);
    }

    public function finish(TpaTryout $tryout): RedirectResponse
    {
        $this->authorizeTryout($tryout);

        if (! $tryout->isFinished()) {
            DB::transaction(function () use ($tryout) {
                $score = 0;
                $correct = 0;
                $time = 0;

                foreach ($tryout->questions as $q) {
                    $points = $q->pointsFor($q->user_answer);
                    $isCorrect = $q->user_answer !== null && $points === 5;

                    $q->update(['points' => $points, 'is_correct' => $isCorrect]);

                    $score += $points;
                    $correct += $isCorrect ? 1 : 0;
                    $time += $q->time_spent_ms;
                }

                $tryout->update([
                    'status' => TpaTryout::STATUS_FINISHED,
                    'score' => $score,
                    'correct_count' => $correct,
                    'total_time_ms' => $time,
                    'finished_at' => now(),
                ]);
            });
        }

        return redirect()->route('tpa.tryout.result', $tryout);
    }

    public function result(TpaTryout $tryout): View|RedirectResponse
    {
        $this->authorizeTryout($tryout);

        if (! $tryout->isFinished()) {
            return redirect()->route('tpa.tryout.quiz', $tryout);
        }

        return view('tpa.result', [
            'tryout' => $tryout->load('questions'),
            'meta' => config("tpa.categories.{$tryout->category}"),
        ]);
    }

    public function destroy(TpaTryout $tryout): RedirectResponse
    {
        $this->authorizeTryout($tryout);
        abort_if($tryout->isFinished(), 403);

        $tryout->delete();

        return redirect()->route('tpa.dashboard');
    }

    private function ensureCategory(string $category): void
    {
        abort_unless(array_key_exists($category, config('tpa.categories')), 404);
    }

    private function authorizeTryout(TpaTryout $tryout): void
    {
        abort_unless((int) $tryout->tpa_user_id === (int) Auth::guard('tpa')->id(), 404);
    }
}
