@props(['class' => ''])

{{--
    Tombol ganti tema terang/gelap.

    Ikon mengikuti class `dark` pada <html> lewat varian dark: Tailwind — tidak
    perlu state Alpine terpisah untuk itu, cukup toggle class-nya di klik.
    Preferensi disimpan di localStorage supaya bertahan lintas halaman dan
    kunjungan; skrip anti-flash di <head> (lihat layouts/app.blade.php dan
    layouts/guest.blade.php) yang membaca nilainya sebelum halaman dicat.
--}}
<button
    type="button"
    @click="
        const next = document.documentElement.classList.contains('dark') ? 'light' : 'dark';
        document.documentElement.classList.toggle('dark', next === 'dark');
        localStorage.setItem('theme', next);
    "
    class="{{ $class }} inline-flex h-9 w-9 items-center justify-center rounded-lg text-gray-500 transition hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200"
    aria-label="Ganti tema terang/gelap"
>
    {{-- Bulan: terlihat pada tema terang, mengajak pindah ke gelap --}}
    <svg class="h-5 w-5 dark:hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z" />
    </svg>
    {{-- Matahari: terlihat pada tema gelap, mengajak pindah ke terang --}}
    <svg class="hidden h-5 w-5 dark:block" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
        <circle cx="12" cy="12" r="4" stroke-linecap="round" stroke-linejoin="round" />
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41m11.32-11.32l1.41-1.41" />
    </svg>
</button>
