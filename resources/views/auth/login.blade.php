<x-guest-layout title="Masuk ke akun Anda"
                subtitle="Lanjutkan tes yang tertunda atau buka kembali hasil rekomendasi Anda.">

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" value="Alamat Surel" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                          :value="old('email')" required autofocus autocomplete="username"
                          placeholder="nama@contoh.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <div class="flex items-center justify-between">
                <x-input-label for="password" value="Kata Sandi" />
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}"
                       class="text-xs font-medium text-indigo-600 transition hover:text-indigo-500 dark:text-indigo-400">
                        Lupa kata sandi?
                    </a>
                @endif
            </div>
            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full"
                          required autocomplete="current-password" placeholder="••••••••" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label for="remember_me" class="flex items-center gap-2">
            <input id="remember_me" name="remember" type="checkbox"
                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900">
            <span class="text-sm text-gray-600 dark:text-gray-400">Ingat saya di perangkat ini</span>
        </label>

        <button type="submit"
                class="w-full rounded-lg bg-indigo-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700">
            Masuk
        </button>
    </form>

    <p class="mt-6 border-t border-gray-100 pt-6 text-center text-sm text-gray-600 dark:border-gray-700 dark:text-gray-400">
        Belum punya akun?
        <a href="{{ route('register') }}" class="font-semibold text-indigo-600 transition hover:text-indigo-500 dark:text-indigo-400">
            Daftar sebagai calon mahasiswa
        </a>
    </p>
</x-guest-layout>
