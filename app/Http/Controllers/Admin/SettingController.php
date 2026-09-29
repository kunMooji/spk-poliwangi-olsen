<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Parameter algoritma: ambang margin, lambda, epsilon, skala Likert, dan
 * jumlah minimal prioritas prodi.
 */
class SettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings.edit', [
            'settings' => Setting::query()->orderBy('id')->get(),
            'values' => Setting::values(),
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        foreach ($request->validated('settings') as $key => $value) {
            // Input HTML selalu berupa string; dikembalikan ke tipe aslinya
            // supaya kolom `settings.type` tetap sesuai isinya.
            Setting::set($key, match ($key) {
                'likert_min', 'likert_max', 'min_priorities' => (int) $value,
                default => (float) $value,
            });
        }

        Setting::forgetCache();

        return redirect()
            ->route('admin.settings.edit')
            ->with('success', 'Pengaturan tersimpan. Perubahan berlaku untuk tes yang dikerjakan setelah ini; hasil lama tetap memakai parameter yang tersimpan saat perhitungan.');
    }
}
