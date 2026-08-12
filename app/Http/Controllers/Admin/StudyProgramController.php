<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StudyProgramRequest;
use App\Models\StudyProgram;
use App\Support\Riasec;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pengelolaan alternatif keputusan: identitas prodi, bobot relevansi mata
 * pelajaran (C1..C6), dan profil RIASEC prodi (C7).
 */
class StudyProgramController extends Controller
{
    public function index(Request $request): View
    {
        $programs = StudyProgram::query()
            ->when($request->string('q')->trim()->value(), function ($query, string $keyword) {
                $query->where(fn ($q) => $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('code', 'like', "%{$keyword}%")
                    ->orWhere('department', 'like', "%{$keyword}%"));
            })
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->input('status') === 'aktif'))
            ->orderBy('code')
            ->paginate(15)
            ->withQueryString();

        return view('admin.study-programs.index', [
            'programs' => $programs,
            'total' => StudyProgram::query()->count(),
            'activeTotal' => StudyProgram::query()->active()->count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.study-programs.create', [
            'program' => new StudyProgram([
                'level' => 'D4',
                'is_active' => true,
                'math_relevance' => 0.5,
                'physics_relevance' => 0.5,
                'chemistry_relevance' => 0.5,
                'biology_relevance' => 0.5,
                'indonesian_relevance' => 0.5,
                'english_relevance' => 0.5,
            ]),
            'subjects' => Riasec::SUBJECTS,
            'dimensions' => Riasec::LABELS,
        ]);
    }

    public function store(StudyProgramRequest $request): RedirectResponse
    {
        $program = StudyProgram::query()->create($request->payload());

        return redirect()
            ->route('admin.study-programs.index')
            ->with('success', "Program studi {$program->full_name} berhasil ditambahkan.");
    }

    public function edit(StudyProgram $studyProgram): View
    {
        return view('admin.study-programs.edit', [
            'program' => $studyProgram,
            'subjects' => Riasec::SUBJECTS,
            'dimensions' => Riasec::LABELS,
        ]);
    }

    public function update(StudyProgramRequest $request, StudyProgram $studyProgram): RedirectResponse
    {
        $studyProgram->update($request->payload());

        return redirect()
            ->route('admin.study-programs.index')
            ->with('success', "Program studi {$studyProgram->full_name} berhasil diperbarui.");
    }

    /**
     * Prodi yang pernah dipakai pada tes tidak boleh dihapus: relasi hasil dan
     * prioritas ikut terhapus berantai sehingga arsip perhitungan akan rusak.
     */
    public function destroy(StudyProgram $studyProgram): RedirectResponse
    {
        if ($studyProgram->results()->exists() || $studyProgram->priorities()->exists()) {
            return back()->with('error', 'Program studi ini sudah dipakai pada data tes sehingga tidak dapat dihapus. Nonaktifkan saja agar tidak muncul pada tes berikutnya.');
        }

        $name = $studyProgram->full_name;
        $studyProgram->delete();

        return redirect()
            ->route('admin.study-programs.index')
            ->with('success', "Program studi {$name} berhasil dihapus.");
    }
}
