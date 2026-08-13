<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PeriodRequest;
use App\Models\Period;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Pengelolaan gelombang penerimaan mahasiswa baru.
 *
 * Sesi tes menyalin gelombang yang aktif saat tes dibuat, sehingga mengubah
 * gelombang di sini tidak memindahkan tes yang sudah tercatat sebelumnya.
 */
class PeriodController extends Controller
{
    public function index(): View
    {
        return view('admin.periods.index', [
            'periods' => Period::query()
                ->withCount('assessments')
                ->orderByDesc('is_active')
                ->orderByDesc('starts_at')
                ->orderByDesc('id')
                ->get(),
            'current' => Period::current(),
        ]);
    }

    public function create(): View
    {
        return view('admin.periods.create', [
            'period' => new Period([
                'academic_year' => $this->suggestedAcademicYear(),
                'is_active' => false,
            ]),
        ]);
    }

    public function store(PeriodRequest $request): RedirectResponse
    {
        $period = $this->persist(fn () => Period::query()->create($request->payload()), $request->boolean('is_active'));

        return redirect()
            ->route('admin.periods.index')
            ->with('success', "Gelombang {$period->name} berhasil ditambahkan.");
    }

    public function edit(Period $period): View
    {
        return view('admin.periods.edit', ['period' => $period]);
    }

    public function update(PeriodRequest $request, Period $period): RedirectResponse
    {
        $this->persist(function () use ($period, $request) {
            $period->update($request->payload());

            return $period;
        }, $request->boolean('is_active'));

        return redirect()
            ->route('admin.periods.index')
            ->with('success', "Gelombang {$period->name} berhasil diperbarui.");
    }

    public function destroy(Period $period): RedirectResponse
    {
        // Sesi tes memakai `nullOnDelete`, sehingga menghapus gelombang tidak
        // menghilangkan hasil tes — tetapi penandaannya ikut hilang dan tidak
        // dapat dipulihkan. Karena itu penghapusan diblokir.
        if ($period->assessments()->exists()) {
            return back()->with('error', "Gelombang {$period->name} sudah dipakai sesi tes sehingga tidak dapat dihapus. Nonaktifkan saja agar tes baru tidak lagi masuk ke gelombang ini.");
        }

        $name = $period->name;
        $period->delete();

        return redirect()
            ->route('admin.periods.index')
            ->with('success', "Gelombang {$name} berhasil dihapus.");
    }

    /**
     * Menyimpan gelombang sambil menjaga hanya ada satu gelombang aktif.
     *
     * Dua gelombang aktif membuat `Period::current()` ambigu — sesi tes bisa
     * masuk ke gelombang yang salah tanpa disadari.
     *
     * @param  \Closure(): Period  $save
     */
    private function persist(\Closure $save, bool $shouldActivate): Period
    {
        return DB::transaction(function () use ($save, $shouldActivate) {
            $period = $save();

            if ($shouldActivate) {
                Period::query()
                    ->whereKeyNot($period->getKey())
                    ->where('is_active', true)
                    ->each(fn (Period $other) => $other->update(['is_active' => false]));
            }

            return $period;
        });
    }

    /** Tebakan tahun akademik berjalan, sekadar mengurangi ketikan. */
    private function suggestedAcademicYear(): string
    {
        $year = (int) now()->year;

        // Tahun akademik baru dianggap dimulai Juli.
        return now()->month >= 7
            ? $year.'/'.($year + 1)
            : ($year - 1).'/'.$year;
    }
}
