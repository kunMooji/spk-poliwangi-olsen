<?php

namespace App\Http\Controllers\Tpa;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::guard('tpa')->check()) {
            return redirect()->route('tpa.dashboard');
        }

        return view('tpa.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:30'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = 'tpa-login|'.Str::lower($credentials['username']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'username' => 'Terlalu banyak percobaan. Coba lagi dalam '.RateLimiter::availableIn($throttleKey).' detik.',
            ]);
        }

        $credentials['username'] = Str::lower(trim($credentials['username']));

        if (! Auth::guard('tpa')->attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages(['username' => 'Username atau kata sandi salah.']);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        return redirect()->intended(route('tpa.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('tpa')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('tpa.login');
    }
}
