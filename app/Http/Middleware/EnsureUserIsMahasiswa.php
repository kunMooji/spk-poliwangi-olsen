<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pengisian tes adalah alur calon mahasiswa. Admin dialihkan ke panelnya
 * supaya data rekapitulasi tidak tercampur sesi tes milik pengelola.
 */
class EnsureUserIsMahasiswa
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isAdmin()) {
            return redirect()
                ->route('admin.dashboard')
                ->with('info', 'Pengisian tes hanya untuk calon mahasiswa. Riwayat tes dapat dilihat pada menu Rekap Hasil Tes.');
        }

        abort_unless($request->user()?->isMahasiswa(), 403);

        return $next($request);
    }
}
