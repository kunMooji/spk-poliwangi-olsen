<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Melindungi area latihan SKD. Memakai guard "tpa", bukan "web", jadi login
 * SPK (mahasiswa/admin) tidak membuka area ini dan sebaliknya.
 */
class EnsureTpaAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('tpa')->check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Sesi berakhir, silakan masuk lagi.'], 401);
            }

            return redirect()->route('tpa.login');
        }

        return $next($request);
    }
}
