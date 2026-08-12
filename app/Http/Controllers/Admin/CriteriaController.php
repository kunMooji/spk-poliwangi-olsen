<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CriteriaRequest;
use App\Models\Criteria;
use App\Support\Riasec;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Pengelolaan kriteria C1..C9 beserta bobotnya.
 *
 * Perubahan di sini hanya memengaruhi tes berikutnya — hasil lama memakai
 * `assessments.weights_snapshot` sehingga tetap dapat ditelusuri.
 */
class CriteriaController extends Controller
{
    public function index(): View
    {
        return view('admin.criteria.index', [
            'criteria' => Criteria::query()->ordered()->get(),
            'totalWeight' => Criteria::totalActiveWeight(),
        ]);
    }

    public function create(): View
    {
        return view('admin.criteria.create', [
            'criterion' => new Criteria([
                'type' => 'benefit',
                'source' => 'subject_score',
                'is_active' => true,
                'weight' => 0,
                'sort_order' => (int) Criteria::query()->max('sort_order') + 1,
            ]),
            'sources' => Criteria::SOURCES,
            'subjects' => Riasec::SUBJECTS,
        ]);
    }

    public function store(CriteriaRequest $request): RedirectResponse
    {
        $criterion = Criteria::query()->create($request->payload());

        return redirect()
            ->route('admin.criteria.index')
            ->with('success', "Kriteria {$criterion->code} berhasil ditambahkan.");
    }

    public function edit(Criteria $criteria): View
    {
        return view('admin.criteria.edit', [
            'criterion' => $criteria,
            'sources' => Criteria::SOURCES,
            'subjects' => Riasec::SUBJECTS,
        ]);
    }

    public function update(CriteriaRequest $request, Criteria $criteria): RedirectResponse
    {
        $criteria->update($request->payload());

        return redirect()
            ->route('admin.criteria.index')
            ->with('success', "Kriteria {$criteria->code} berhasil diperbarui.");
    }

    public function destroy(Criteria $criteria): RedirectResponse
    {
        $code = $criteria->code;
        $criteria->delete();

        return redirect()
            ->route('admin.criteria.index')
            ->with('success', "Kriteria {$code} berhasil dihapus. Periksa kembali total bobot.");
    }
}
