<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SubjectRequest;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Pengelolaan master mata pelajaran rapor.
 *
 * Daftarnya tidak dikunci di kode karena kurikulum SMA dan SMK berbeda: mapel
 * produktif SMK berbeda per konsentrasi keahlian dan penamaannya tidak seragam
 * antar sekolah, sehingga hanya admin yang tahu mapel apa yang relevan bagi
 * prodi yang benar-benar ditawarkan.
 */
class SubjectController extends Controller
{
    /**
     * Dikelompokkan per rumpun (kolom `group`) supaya daftar yang jumlahnya
     * banyak tetap mudah disisir admin — tiap rumpun tampil sebagai accordion
     * bernomor, mapel tanpa rumpun ditampung di grup "Tanpa Kelompok" di akhir.
     */
    public function index(): View
    {
        $subjects = Subject::query()
            ->withCount('studyPrograms')
            ->ordered()
            ->get();

        $grouped = $subjects->groupBy(fn (Subject $subject) => $subject->group ?: '');

        // Urut alfabet, tapi grup tanpa nama (group kosong) selalu ditaruh
        // paling akhir alih-alih di depan (mana kala "" < huruf apa pun).
        $groups = $grouped->keys()
            ->sort(fn ($a, $b) => $a === '' ? 1 : ($b === '' ? -1 : strcasecmp($a, $b)))
            ->mapWithKeys(fn ($key) => [$key => $grouped[$key]]);

        return view('admin.subjects.index', [
            'groups' => $groups,
            'subject' => new Subject([
                'education_level' => 'umum',
                'is_active' => true,
                'sort_order' => (int) Subject::query()->max('sort_order') + 1,
            ]),
            'levels' => Subject::EDUCATION_LEVELS,
        ]);
    }

    public function create(): View
    {
        return view('admin.subjects.create', [
            'subject' => new Subject([
                'education_level' => 'umum',
                'is_active' => true,
                'sort_order' => (int) Subject::query()->max('sort_order') + 1,
            ]),
            'levels' => Subject::EDUCATION_LEVELS,
        ]);
    }

    public function store(SubjectRequest $request): RedirectResponse
    {
        $subject = Subject::query()->create($request->payload());

        return redirect()
            ->route('admin.subjects.index')
            ->with('success', "Mata pelajaran {$subject->name} berhasil ditambahkan.");
    }

    public function edit(Subject $subject): View
    {
        return view('admin.subjects.edit', [
            'subject' => $subject,
            'levels' => Subject::EDUCATION_LEVELS,
        ]);
    }

    public function update(SubjectRequest $request, Subject $subject): RedirectResponse
    {
        $subject->update($request->payload());

        return redirect()
            ->route('admin.subjects.index')
            ->with('success', "Mata pelajaran {$subject->name} berhasil diperbarui.");
    }

    /**
     * Mapel yang masih dipakai prodi tidak boleh dihapus: penghapusan berantai
     * akan mencabut mapel pendukung prodi tersebut tanpa sepengetahuan admin,
     * sehingga C2-nya diam-diam jatuh ke rerata rapor umum.
     */
    public function destroy(Subject $subject): RedirectResponse
    {
        if ($subject->studyPrograms()->exists()) {
            return back()->with('error', 'Mata pelajaran ini masih dipakai sebagai mapel pendukung program studi. Lepaskan dulu dari prodi terkait, atau nonaktifkan saja.');
        }

        $name = $subject->name;
        $subject->delete();

        return redirect()
            ->route('admin.subjects.index')
            ->with('success', "Mata pelajaran {$name} berhasil dihapus.");
    }
}
