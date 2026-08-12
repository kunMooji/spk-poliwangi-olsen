<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateTracerRequest;
use App\Models\StudyProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Pemutakhiran data serapan kerja alumni (C9) untuk seluruh prodi sekaligus.
 *
 * `employment_rate` tidak pernah diisi manual, selalu diturunkan dari
 * employed_count / alumni_count supaya angkanya tidak saling bertentangan.
 */
class TracerStudyController extends Controller
{
    public function index(): View
    {
        return view('admin.tracer.index', [
            'programs' => StudyProgram::query()->orderBy('code')->get(),
        ]);
    }

    public function update(UpdateTracerRequest $request): RedirectResponse
    {
        $rows = $request->validated('programs');

        DB::transaction(function () use ($rows) {
            $programs = StudyProgram::query()->findMany(array_keys($rows))->keyBy('id');

            foreach ($rows as $id => $row) {
                $program = $programs->get((int) $id);

                if (! $program) {
                    continue;
                }

                $alumni = (int) $row['alumni_count'];
                $employed = (int) $row['employed_count'];

                $program->update([
                    'alumni_count' => $alumni,
                    'employed_count' => $employed,
                    'tracer_year' => $row['tracer_year'] ?? null,
                    'employment_rate' => $alumni > 0 ? round($employed / $alumni, 3) : 0,
                    'tracer_updated_at' => now(),
                ]);
            }
        });

        return redirect()
            ->route('admin.tracer.index')
            ->with('success', 'Data tracer study berhasil diperbarui. Persentase serapan kerja dihitung ulang otomatis.');
    }
}
