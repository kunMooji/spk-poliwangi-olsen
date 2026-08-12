<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RiasecQuestionRequest;
use App\Models\RiasecQuestion;
use App\Support\Riasec;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pengelolaan butir kuesioner minat bakat RIASEC.
 */
class RiasecQuestionController extends Controller
{
    public function index(Request $request): View
    {
        $dimension = $request->string('dimension')->upper()->value();

        $questions = RiasecQuestion::query()
            ->when(in_array($dimension, Riasec::DIMENSIONS, true), fn ($query) => $query->where('dimension', $dimension))
            ->ordered()
            ->paginate(20)
            ->withQueryString();

        return view('admin.riasec-questions.index', [
            'questions' => $questions,
            'labels' => Riasec::LABELS,
            // Jumlah butir aktif per dimensi — idealnya seimbang supaya
            // persentase RIASEC antar dimensi dapat dibandingkan.
            'counts' => RiasecQuestion::query()->active()
                ->selectRaw('dimension, count(*) as total')
                ->groupBy('dimension')
                ->pluck('total', 'dimension'),
        ]);
    }

    public function create(): View
    {
        return view('admin.riasec-questions.create', [
            'question' => new RiasecQuestion([
                'dimension' => 'R',
                'is_active' => true,
                'sort_order' => (int) RiasecQuestion::query()->max('sort_order') + 1,
            ]),
            'labels' => Riasec::LABELS,
        ]);
    }

    public function store(RiasecQuestionRequest $request): RedirectResponse
    {
        RiasecQuestion::query()->create($request->payload());

        return redirect()
            ->route('admin.questions.index')
            ->with('success', 'Pernyataan kuesioner berhasil ditambahkan.');
    }

    public function edit(RiasecQuestion $riasecQuestion): View
    {
        return view('admin.riasec-questions.edit', [
            'question' => $riasecQuestion,
            'labels' => Riasec::LABELS,
        ]);
    }

    public function update(RiasecQuestionRequest $request, RiasecQuestion $riasecQuestion): RedirectResponse
    {
        $riasecQuestion->update($request->payload());

        return redirect()
            ->route('admin.questions.index')
            ->with('success', 'Pernyataan kuesioner berhasil diperbarui.');
    }

    /**
     * Butir yang sudah pernah dijawab tidak dihapus: jawabannya ikut terhapus
     * berantai sehingga skor RIASEC tes lama tidak lagi dapat ditelusuri.
     */
    public function destroy(RiasecQuestion $riasecQuestion): RedirectResponse
    {
        if ($riasecQuestion->answers()->exists()) {
            return back()->with('error', 'Pernyataan ini sudah pernah dijawab pada tes sehingga tidak dapat dihapus. Nonaktifkan saja agar tidak muncul pada kuesioner berikutnya.');
        }

        $riasecQuestion->delete();

        return redirect()
            ->route('admin.questions.index')
            ->with('success', 'Pernyataan kuesioner berhasil dihapus.');
    }
}
