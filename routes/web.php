<?php

use App\Http\Controllers\Admin\AssessmentRecapController;
use App\Http\Controllers\Admin\CriteriaController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\RiasecQuestionController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StatisticsController;
use App\Http\Controllers\Admin\StudyProgramController;
use App\Http\Controllers\Admin\TracerStudyController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ResultController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Lembar hasil dibuka pemiliknya maupun admin — pembatasannya di AssessmentPolicy.
    Route::get('/tes/{assessment}/hasil', [ResultController::class, 'show'])->name('assessments.result');
    Route::get('/tes/{assessment}/perhitungan', [ResultController::class, 'calculation'])
        ->name('assessments.calculation');

    // Alur pengisian tes — khusus calon mahasiswa.
    Route::middleware('mahasiswa')->group(function () {
        Route::get('/tes', [AssessmentController::class, 'index'])->name('assessments.index');
        Route::get('/tes/mulai', [AssessmentController::class, 'create'])->name('assessments.create');
        Route::post('/tes', [AssessmentController::class, 'store'])->name('assessments.store');
        Route::delete('/tes/{assessment}', [AssessmentController::class, 'destroy'])->name('assessments.destroy');

        Route::get('/tes/{assessment}/kuesioner', [AssessmentController::class, 'questionnaire'])
            ->name('assessments.questionnaire');
        Route::post('/tes/{assessment}/kuesioner', [AssessmentController::class, 'storeAnswers'])
            ->name('assessments.answers.store');
    });
});

// Panel pengelolaan data — khusus admin.
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminDashboardController::class)->name('dashboard');

    Route::resource('prodi', StudyProgramController::class)
        ->parameters(['prodi' => 'studyProgram'])
        ->names('study-programs')
        ->except('show');

    Route::resource('kriteria', CriteriaController::class)
        ->parameters(['kriteria' => 'criteria'])
        ->names('criteria')
        ->except('show');

    Route::resource('pernyataan', RiasecQuestionController::class)
        ->parameters(['pernyataan' => 'riasecQuestion'])
        ->names('questions')
        ->except('show');

    Route::get('/tracer', [TracerStudyController::class, 'index'])->name('tracer.index');
    Route::put('/tracer', [TracerStudyController::class, 'update'])->name('tracer.update');

    Route::get('/pengaturan', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/pengaturan', [SettingController::class, 'update'])->name('settings.update');

    Route::get('/statistik', StatisticsController::class)->name('statistics');

    Route::get('/rekap', [AssessmentRecapController::class, 'index'])->name('recap.index');
    Route::get('/rekap/{assessment}', [AssessmentRecapController::class, 'show'])->name('recap.show');
    Route::get('/rekap/{assessment}/sensitivitas', [AssessmentRecapController::class, 'sensitivity'])
        ->name('recap.sensitivity');
    Route::delete('/rekap/{assessment}', [AssessmentRecapController::class, 'destroy'])->name('recap.destroy');
});

require __DIR__.'/auth.php';
