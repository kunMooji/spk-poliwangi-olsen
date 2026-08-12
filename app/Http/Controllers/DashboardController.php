<?php

namespace App\Http\Controllers;

use App\Models\RiasecQuestion;
use App\Models\StudyProgram;
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

        return view('dashboard', [
            'latest' => $user->assessments()->with('recommendedProgram')->latest()->first(),
            'unfinished' => $user->assessments()->where('status', '!=', 'completed')->latest()->first(),
            'totalCompleted' => $user->assessments()->completed()->count(),
            'programCount' => StudyProgram::query()->active()->count(),
            'questionCount' => RiasecQuestion::query()->active()->count(),
        ]);
    }
}
