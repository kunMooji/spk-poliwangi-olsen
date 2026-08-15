<x-guest-layout title="Verifikasi alamat surel"
                subtitle="Terima kasih sudah mendaftar. Sebelum mulai, buka tautan verifikasi yang baru saja kami kirimkan ke surel Anda.">

    @if (session('status') == 'verification-link-sent')
        <x-alert type="success" class="mb-5">
            Tautan verifikasi baru telah dikirim ke alamat surel yang Anda daftarkan.
        </x-alert>
    @endif

    <p class="text-sm leading-relaxed text-gray-600 dark:text-gray-400">
        Belum menerima surelnya? Periksa folder spam, atau minta kami mengirim ulang.
    </p>

    <form method="POST" action="{{ route('verification.send') }}" class="mt-5">
        @csrf
        <button type="submit"
                class="w-full rounded-lg bg-brand-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">
            Kirim Ulang Surel Verifikasi
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
        @csrf
        <button type="submit" class="text-sm text-gray-500 transition hover:text-brand-600 dark:text-gray-400 dark:hover:text-brand-400">
            Keluar
        </button>
    </form>
</x-guest-layout>
