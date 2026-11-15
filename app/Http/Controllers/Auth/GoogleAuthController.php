<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Akun dicocokkan lewat `google_id`, lalu lewat surel. Penautan lewat surel
     * hanya dilakukan bila Google menyatakan surel itu terverifikasi — jika tidak,
     * siapa pun yang bisa membuat akun Google dengan surel orang lain dapat
     * mengambil alih akunnya. Akun baru selalu berperan calon mahasiswa; akun
     * administrator hanya bisa masuk bila sudah ada di basis data.
     */
    public function callback(): RedirectResponse
    {
        try {
            $google = Socialite::driver('google')->user();
        } catch (Throwable) {
            return $this->failed('Masuk dengan Google dibatalkan atau gagal. Silakan coba lagi.');
        }

        if (! ($google->user['email_verified'] ?? false) || ! $google->getEmail()) {
            return $this->failed('Surel akun Google Anda belum terverifikasi.');
        }

        $email = strtolower($google->getEmail());

        $user = User::where('google_id', $google->getId())->first()
            ?? User::where('email', $email)->first();

        if ($user && ! $user->is_active) {
            return $this->failed('Akun Anda dinonaktifkan. Silakan hubungi administrator.');
        }

        if (! $user) {
            $user = new User([
                'name' => $google->getName() ?: $email,
                'email' => $email,
                'role' => User::ROLE_MAHASISWA,
            ]);
        }

        $user->google_id = $google->getId();
        $user->email_verified_at ??= now();
        $user->save();

        Auth::login($user, remember: true);
        request()->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function failed(string $message): RedirectResponse
    {
        return redirect()->route('login')->withErrors(['email' => $message]);
    }
}
