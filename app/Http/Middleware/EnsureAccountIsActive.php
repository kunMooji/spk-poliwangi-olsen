<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Memutus sesi pengguna yang akunnya dinonaktifkan.
 *
 * Pemeriksaan saat masuk saja tidak cukup: penonaktifan dapat terjadi ketika
 * pengguna sedang masuk, dan sesinya akan tetap hidup sampai kedaluwarsa.
 */
class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && ! Auth::user()->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Akun Anda dinonaktifkan. Silakan hubungi administrator.']);
        }

        return $next($request);
    }
}
