<?php

namespace App\Http\Controllers\Tpa;

use App\Http\Controllers\Controller;
use App\Models\TpaTryout;
use App\Services\Tpa\StatsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(StatsService $stats): View
    {
        $user = Auth::guard('tpa')->user();

        return view('tpa.dashboard', [
            'user' => $user,
            'stats' => $stats->forUser($user),
            'categories' => config('tpa.categories'),
            'unfinished' => $user->tryouts()->where('status', TpaTryout::STATUS_IN_PROGRESS)->latest()->first(),
            'recent' => $user->tryouts()->where('status', TpaTryout::STATUS_FINISHED)->latest('finished_at')->take(5)->get(),
        ]);
    }

    public function history(): View
    {
        $user = Auth::guard('tpa')->user();

        return view('tpa.history', [
            'tryouts' => $user->tryouts()->latest()->paginate(20),
        ]);
    }
}
