<?php

namespace App\Http\Controllers;

use App\Models\RiasecQuestion;
use App\Models\StudyProgram;
use App\Support\Riasec;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        // Admin tidak mengikuti tes, sehingga beranda miliknya adalah panel pengelolaan.
        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        $history = $user->assessments()
            ->completed()
            ->with('recommendedProgram')
            ->orderByDesc('completed_at')
            ->get();

        $latestCompleted = $history->first();
        $riasecDistribution = $latestCompleted
            ? collect(Riasec::DIMENSIONS)->map(function (string $dimension) use ($latestCompleted): array {
                return [
                    'label' => Riasec::name($dimension),
                    'value' => $latestCompleted->riasecPercentages()[$dimension],
                    'color' => Riasec::color($dimension),
                ];
            })->all()
            : [];

        return view('dashboard', [
            'latest' => $user->assessments()->with('recommendedProgram')->latest()->first(),
            'unfinished' => $user->assessments()->where('status', '!=', 'completed')->latest()->first(),
            'totalCompleted' => $history->count(),
            'programCount' => StudyProgram::query()->active()->count(),
            'questionCount' => RiasecQuestion::query()->active()->count(),
            'recentHistory' => $history->take(4),
            'riasecDistribution' => $riasecDistribution,
        ]);
    }
}
