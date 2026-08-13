<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Manajemen akun calon mahasiswa.
 *
 * Akun admin sengaja hanya ditampilkan, tidak dapat disunting dari sini.
 * Pembuatan dan pengubahan admin tetap lewat seeder atau SQL langsung, sesuai
 * rancangan otorisasi: tidak ada jalur peningkatan hak akses lewat antarmuka.
 */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->withCount('assessments')
            ->when($request->string('q')->trim()->value(), function ($query, string $keyword) {
                $query->where(fn ($q) => $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%"));
            })
            ->when($request->input('role'), fn ($query, $role) => $query->where('role', $role))
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->input('status') === 'aktif'))
            ->orderBy('role')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'totalMahasiswa' => User::query()->mahasiswa()->count(),
            'totalNonaktif' => User::query()->where('is_active', false)->count(),
        ]);
    }

    /**
     * Menonaktifkan akun, bukan menghapusnya.
     *
     * Sesi tes memakai `cascadeOnDelete`, sehingga menghapus akun akan ikut
     * menghapus seluruh riwayat tesnya.
     */
    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        if ($denied = $this->guard($request, $user)) {
            return $denied;
        }

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with(
            'success',
            $user->is_active
                ? "Akun {$user->name} diaktifkan kembali."
                : "Akun {$user->name} dinonaktifkan. Pengguna tersebut tidak dapat masuk sampai diaktifkan lagi."
        );
    }

    /**
     * Membuat kata sandi sementara dan menampilkannya sekali kepada admin.
     *
     * Pemulihan lewat surel tidak dapat diandalkan selama pengiriman surel
     * belum dikonfigurasi, sementara calon mahasiswa yang terkunci tetap harus
     * bisa ditolong.
     */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        if ($denied = $this->guard($request, $user)) {
            return $denied;
        }

        $password = Str::password(10, symbols: false);

        $user->forceFill(['password' => $password])->save();

        // Perubahan kata sandi tidak masuk `activityAttributes()` supaya hash-nya
        // tidak ikut tersimpan; peristiwanya dicatat di sini sebagai tindakan.
        ActivityLog::query()->create([
            'user_id' => $request->user()->id,
            'user_name' => $request->user()->name,
            'action' => 'updated',
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'subject_label' => $user->name,
            'changes' => ['password' => ['from' => '(disembunyikan)', 'to' => '(disetel ulang admin)']],
        ]);

        return back()->with('success', "Kata sandi {$user->name} berhasil disetel ulang menjadi: {$password} — catat sekarang, kata sandi ini tidak ditampilkan lagi. Minta yang bersangkutan segera menggantinya setelah masuk.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($denied = $this->guard($request, $user)) {
            return $denied;
        }

        // Menghapus akun ikut menghapus seluruh sesi tesnya. Arsip hasil tes
        // tidak boleh hilang, jadi akun yang sudah pernah tes hanya boleh
        // dinonaktifkan.
        if ($user->assessments()->exists()) {
            return back()->with('error', "Akun {$user->name} sudah memiliki riwayat tes sehingga tidak dapat dihapus. Nonaktifkan saja agar arsip hasil tesnya tetap utuh.");
        }

        $name = $user->name;
        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Akun {$name} berhasil dihapus.");
    }

    /**
     * Pagar bersama seluruh tindakan pengubahan akun.
     *
     * Admin tidak boleh menyentuh akun admin lain maupun akunnya sendiri dari
     * panel ini — keduanya jalur peningkatan hak akses yang tidak perlu ada.
     */
    private function guard(Request $request, User $user): ?RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Anda tidak dapat mengubah akun Anda sendiri dari halaman ini. Gunakan menu Profil.');
        }

        if ($user->isAdmin()) {
            return back()->with('error', 'Akun administrator tidak dapat diubah lewat antarmuka. Pengelolaannya dilakukan langsung di basis data.');
        }

        return null;
    }
}
