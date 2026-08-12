<?php

namespace App\Http\Controllers;

use App\Models\Criteria;
use App\Models\RiasecQuestion;
use App\Models\StudyProgram;
use App\Support\Riasec;
use Illuminate\View\View;

/**
 * Halaman depan publik.
 *
 * Angka yang ditampilkan dibaca dari data master supaya keterangan di halaman
 * ini tidak pernah berselisih dengan isi sistem yang sebenarnya.
 */
class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('welcome', [
            'programs' => StudyProgram::query()->active()->orderBy('name')->get(),
            'criteria' => Criteria::query()->active()->ordered()->get(),
            'questionCount' => RiasecQuestion::query()->active()->count(),
            'dimensions' => Riasec::LABELS,
            'descriptions' => Riasec::DESCRIPTIONS,
        ]);
    }
}
