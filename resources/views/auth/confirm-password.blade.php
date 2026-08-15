<x-guest-layout title="Konfirmasi kata sandi"
                subtitle="Bagian ini terlindungi. Masukkan kembali kata sandi Anda sebelum melanjutkan.">

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="password" value="Kata Sandi" />
            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full"
                          required autocomplete="current-password" placeholder="••••••••" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <button type="submit"
                class="w-full rounded-lg bg-brand-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">
            Konfirmasi
        </button>
    </form>
</x-guest-layout>
