@props(['class' => ''])

{{--
    Tombol ganti tema terang/gelap.

    Ikon mengikuti class `dark` pada <html> lewat varian dark: Tailwind — tidak
    perlu state Alpine terpisah untuk itu, cukup toggle class-nya di klik.
    Preferensi disimpan di localStorage supaya bertahan lintas halaman dan
    kunjungan; skrip anti-flash di <head> (lihat layouts/app.blade.php dan
    layouts/guest.blade.php) yang membaca nilainya sebelum halaman dicat.

    Pakai `onclick` polos (bukan `@click` Alpine) dengan sengaja — Alpine
    cuma mengikat direktif di dalam pohon elemen yang punya leluhur
    `x-data`, dan komponen ini dipakai lepas di header tanpa leluhur
    seperti itu (welcome.blade.php, guest.blade.php) sehingga `@click`
    diam saja tanpa error terlihat. Logikanya sendiri JS vanilla murni,
    jadi tidak butuh Alpine sama sekali.
--}}
<button
    type="button"
    onclick="
        const next = document.documentElement.classList.contains('dark') ? 'light' : 'dark';
        document.documentElement.classList.toggle('dark', next === 'dark');
        localStorage.setItem('theme', next);
    "
    class="{{ $class }} inline-flex h-9 w-9 items-center justify-center rounded-lg text-ink-500 transition ease-brand-out duration-150 hover:bg-black/5 hover:text-ink-700 active:scale-[0.94] dark:text-porcelain-300 dark:hover:bg-white/10 dark:hover:text-porcelain-100"
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
