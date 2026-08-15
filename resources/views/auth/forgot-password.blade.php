<x-guest-layout title="Lupa kata sandi?"
                subtitle="Masukkan alamat surel akun Anda. Kami akan mengirimkan tautan untuk menyetel kata sandi baru.">

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" value="Alamat Surel" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                          :value="old('email')" required autofocus placeholder="nama@contoh.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <button type="submit"
                class="w-full rounded-lg bg-brand-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">
            Kirim Tautan Setel Ulang
        </button>
    </form>

    <p class="mt-6 border-t border-gray-100 pt-6 text-center text-sm text-gray-600 dark:border-gray-700 dark:text-gray-400">
        Ingat kata sandinya?
        <a href="{{ route('login') }}" class="font-semibold text-brand-600 transition hover:text-brand-500 dark:text-brand-400">
            Kembali ke halaman masuk
        </a>
    </p>
</x-guest-layout>
