<?php

namespace App\Providers;

use App\Models\Setting;
use App\Services\RiasecService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Batas skala Likert diambil dari pengaturan yang dapat diubah admin.
        // Closure ini baru dijalankan saat service benar-benar dibutuhkan, sehingga
        // perintah seperti `migrate` tidak ikut menyentuh tabel settings.
        $this->app->bind(RiasecService::class, fn () => new RiasecService(
            (int) Setting::get('likert_min'),
            (int) Setting::get('likert_max'),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
