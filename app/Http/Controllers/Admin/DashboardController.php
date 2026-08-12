<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Criteria;
use App\Models\RiasecQuestion;
use App\Models\StudyProgram;
use App\Models\User;
use App\Support\Riasec;
use Illuminate\View\View;

/**
 * Ringkasan pengelolaan: kesehatan data master + sebaran hasil tes.
 */
class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $completed = Assessment::query()->completed();

        return view('admin.dashboard', [
            'totalStudents' => User::query()->mahasiswa()->count(),
            'totalAssessments' => Assessment::query()->count(),
            'totalCompleted' => (clone $completed)->count(),
            'totalOngoing' => Assessment::query()->where('status', '!=', 'completed')->count(),

            'programCount' => StudyProgram::query()->active()->count(),
            'criteriaCount' => Criteria::query()->active()->count(),
            'questionCount' => RiasecQuestion::query()->active()->count(),
            'totalWeight' => Criteria::totalActiveWeight(),

            // Sebaran tipe kepribadian dominan calon mahasiswa.
            'dominantDistribution' => (clone $completed)
                ->selectRaw('dominant_type, count(*) as total')
                ->groupBy('dominant_type')
                ->pluck('total', 'dominant_type'),
            'dimensionLabels' => Riasec::LABELS,

            // Prodi yang paling sering keluar sebagai rekomendasi utama.
            'popularPrograms' => (clone $completed)
                ->with('recommendedProgram')
                ->selectRaw('recommended_program_id, count(*) as total')
                ->whereNotNull('recommended_program_id')
                ->groupBy('recommended_program_id')
                ->orderByDesc('total')
                ->take(5)
                ->get(),

            // Seberapa sering rekomendasi sistem sejalan dengan pilihan pertama.
            'matchCount' => (clone $completed)->where('matches_preference', true)->count(),
            'recent' => Assessment::query()->with(['user', 'recommendedProgram'])->latest()->take(5)->get(),
        ]);
    }
}
