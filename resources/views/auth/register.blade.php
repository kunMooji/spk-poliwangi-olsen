<x-guest-layout title="Daftar sebagai calon mahasiswa"
                subtitle="Buat akun untuk mengerjakan tes rekomendasi program studi. Gratis, dan hasilnya tersimpan untuk Anda buka kembali.">

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="name" value="Nama Lengkap" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                          :value="old('name')" required autofocus autocomplete="name"
                          placeholder="Nama sesuai ijazah" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="email" value="Alamat Surel" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                          :value="old('email')" required autocomplete="username"
                          placeholder="nama@contoh.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" value="Kata Sandi" />
            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full"
                          required autocomplete="new-password" placeholder="Minimal 8 karakter" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Ulangi Kata Sandi" />
            <x-text-input id="password_confirmation" name="password_confirmation" type="password"
                          class="mt-1 block w-full" required autocomplete="new-password" placeholder="••••••••" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <button type="submit"
                class="w-full rounded-lg bg-indigo-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700">
            Buat Akun
        </button>
    </form>

    <p class="mt-5 rounded-lg bg-gray-50 px-4 py-3 text-xs leading-relaxed text-gray-500 dark:bg-gray-900/50 dark:text-gray-400">
        Pendaftaran mandiri hanya untuk calon mahasiswa. Akun administrator tidak dapat dibuat melalui halaman ini
        dan disiapkan langsung oleh pengelola sistem.
    </p>

    <p class="mt-6 border-t border-gray-100 pt-6 text-center text-sm text-gray-600 dark:border-gray-700 dark:text-gray-400">
        Sudah punya akun?
        <a href="{{ route('login') }}" class="font-semibold text-indigo-600 transition hover:text-indigo-500 dark:text-indigo-400">
            Masuk di sini
        </a>
    </p>
</x-guest-layout>
