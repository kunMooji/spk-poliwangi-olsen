<?php

namespace App\Http\Controllers\Tpa;

use App\Http\Controllers\Controller;
use App\Models\TpaTryout;
use App\Services\Tpa\BankPicker;
use App\Services\Tpa\StatsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(StatsService $stats, BankPicker $bank): View
    {
        $user = Auth::guard('tpa')->user();
        $categories = config('tpa.categories');

        return view('tpa.dashboard', [
            'user' => $user,
            'stats' => $stats->forUser($user),
            'categories' => $categories,
            'bankProgress' => collect(array_keys($categories))->mapWithKeys(fn ($c) => [$c => $bank->progress($user, $c)])->all(),
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
