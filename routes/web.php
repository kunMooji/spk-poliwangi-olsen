<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AssessmentRecapController;
use App\Http\Controllers\Admin\CriteriaController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\PeriodController;
use App\Http\Controllers\Admin\RiasecQuestionController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StatisticsController;
use App\Http\Controllers\Admin\StudyProgramController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\TracerStudyController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AssessmentComparisonController;
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
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar.update');
    Route::delete('/profile/avatar', [ProfileController::class, 'destroyAvatar'])->name('profile.avatar.destroy');

    // Lembar hasil dibuka pemiliknya maupun admin — pembatasannya di AssessmentPolicy.
    Route::get('/tes/{assessment}/hasil', [ResultController::class, 'show'])->name('assessments.result');
    Route::get('/tes/{assessment}/perhitungan', [ResultController::class, 'calculation'])
        ->name('assessments.calculation');
    Route::get('/tes/{assessment}/cetak', [ResultController::class, 'print'])->name('assessments.print');

    // Alur pengisian tes — khusus calon mahasiswa.
    Route::middleware('mahasiswa')->group(function () {
        Route::get('/tes', [AssessmentController::class, 'index'])->name('assessments.index');
        Route::get('/tes/mulai', [AssessmentController::class, 'create'])->name('assessments.create');

        // Diletakkan sebelum rute ber-parameter agar "bandingkan" tidak
        // tertangkap sebagai kode sesi tes.
        Route::get('/tes/bandingkan', AssessmentComparisonController::class)->name('assessments.compare');

        Route::post('/tes', [AssessmentController::class, 'store'])->name('assessments.store');
        Route::delete('/tes/{assessment}', [AssessmentController::class, 'destroy'])->name('assessments.destroy');

        Route::get('/tes/{assessment}/kuesioner', [AssessmentController::class, 'questionnaire'])
            ->name('assessments.questionnaire');
        Route::post('/tes/{assessment}/kuesioner', [AssessmentController::class, 'storeAnswers'])
            ->name('assessments.answers.store');
        Route::post('/tes/{assessment}/kuesioner/simpan', [AssessmentController::class, 'autosave'])
            ->name('assessments.answers.autosave');
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

    Route::resource('mata-pelajaran', SubjectController::class)
        ->parameters(['mata-pelajaran' => 'subject'])
        ->names('subjects')
        ->except('show');

    Route::resource('pernyataan', RiasecQuestionController::class)
        ->parameters(['pernyataan' => 'riasecQuestion'])
        ->names('questions')
        ->except('show');

    Route::resource('periode', PeriodController::class)
        ->parameters(['periode' => 'period'])
        ->names('periods')
        ->except('show');

    Route::get('/pengguna', [UserController::class, 'index'])->name('users.index');
    Route::put('/pengguna/{user}/status', [UserController::class, 'toggleStatus'])->name('users.status');
    Route::put('/pengguna/{user}/kata-sandi', [UserController::class, 'resetPassword'])->name('users.password');
    Route::delete('/pengguna/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    Route::get('/log', [ActivityLogController::class, 'index'])->name('activity-logs.index');

    Route::get('/tracer', [TracerStudyController::class, 'index'])->name('tracer.index');
    Route::put('/tracer', [TracerStudyController::class, 'update'])->name('tracer.update');

    Route::get('/pengaturan', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/pengaturan', [SettingController::class, 'update'])->name('settings.update');

    Route::get('/statistik', StatisticsController::class)->name('statistics');

    Route::get('/rekap', [AssessmentRecapController::class, 'index'])->name('recap.index');

    // Didaftarkan sebelum /rekap/{assessment} agar "ekspor" tidak dibaca
    // sebagai kode sesi tes.
    Route::get('/rekap/ekspor', [AssessmentRecapController::class, 'export'])->name('recap.export');

    Route::get('/rekap/{assessment}', [AssessmentRecapController::class, 'show'])->name('recap.show');
    Route::get('/rekap/{assessment}/sensitivitas', [AssessmentRecapController::class, 'sensitivity'])
        ->name('recap.sensitivity');
    Route::delete('/rekap/{assessment}', [AssessmentRecapController::class, 'destroy'])->name('recap.destroy');
});

require __DIR__.'/auth.php';
