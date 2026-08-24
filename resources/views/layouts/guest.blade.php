<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ? $title.' — ' : '' }}{{ config('app.name') }}</title>

        {{-- Menentukan tema sebelum halaman dicat — lihat catatan yang sama
             di layouts/app.blade.php. --}}
        <script>
            (function () {
                // Default tema: krem/terang kecuali pengguna pernah memilih gelap
                // secara eksplisit (bukan lagi mengikuti preferensi sistem).
                var stored = localStorage.getItem('theme');
                var isDark = stored === 'dark';
                document.documentElement.classList.toggle('dark', isDark);
            })();
        </script>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="font-sans text-gray-900 antialiased dark:text-gray-100">
        <x-loading-screen />

        <div class="grid min-h-[100dvh] lg:grid-cols-2">
            {{-- Panel kiri: identitas sistem, disembunyikan pada layar sempit --}}
            <aside class="relative hidden flex-col justify-between bg-gradient-to-br from-brand-600 to-brand-900 p-12 text-white lg:flex">
                <a href="{{ route('home') }}" class="flex items-center gap-3">
                    <img src="{{ asset('images/poliwangi_logo.png') }}" alt="Logo Politeknik Negeri Banyuwangi" class="h-11 w-11 object-contain">
                    <span class="text-sm font-semibold leading-tight">
                        {{ config('app.name') }}
                        <span class="block text-xs font-normal text-brand-100">Politeknik Negeri Banyuwangi</span>
                    </span>
                </a>

                <div>
                    <h2 class="text-3xl font-bold leading-tight">
                        Temukan program studi yang benar-benar sejalan dengan Anda.
                    </h2>
                    <p class="mt-4 max-w-md leading-relaxed text-brand-100">
                        Menimbang nilai rapor, minat bakat RIASEC, urutan pilihan Anda, dan data serapan kerja alumni
                        sekaligus &mdash; lalu menjelaskan alasan di balik setiap peringkatnya.
                    </p>

                    <ul class="mt-8 space-y-3 text-sm text-brand-100">
                        @foreach ([
                            'Kuesioner minat bakat berbasis model Holland',
                            'Perangkingan dengan metode CoCoSo',
                            'Rincian perhitungan terbuka untuk diperiksa',
                        ] as $point)
                            <li class="flex items-start gap-3">
                                <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-white/70"></span>
                                <span>{{ $point }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <p class="text-xs text-brand-200">&copy; {{ date('Y') }} Politeknik Negeri Banyuwangi</p>
            </aside>

            {{-- Panel kanan: formulir --}}
            <main class="flex items-center justify-center bg-gray-50 px-4 py-12 dark:bg-gray-900 sm:px-6 lg:px-12">
                <div class="w-full max-w-md">
                    <div class="mb-8 flex items-center justify-between gap-3">
                        <a href="{{ route('home') }}" class="flex items-center gap-3 lg:hidden">
                            <img src="{{ asset('images/poliwangi_logo.png') }}" alt="Logo Politeknik Negeri Banyuwangi" class="h-10 w-10 object-contain">
                            <span class="text-sm font-semibold leading-tight">
                                {{ config('app.name') }}
                                <span class="block text-xs font-normal text-gray-500 dark:text-gray-400">Politeknik Negeri Banyuwangi</span>
                            </span>
                        </a>

                        <x-theme-toggle class="ms-auto" />
                    </div>

                    @if ($title)
                        <h1 class="text-2xl font-bold tracking-tight">{{ $title }}</h1>
                    @endif

                    @if ($subtitle)
                        <p class="mt-2 text-sm leading-relaxed text-gray-600 dark:text-gray-400">{{ $subtitle }}</p>
                    @endif

                    <div class="mt-8 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-800 sm:p-8">
                        {{ $slot }}
                    </div>

                    <p class="mt-6 text-center text-sm">
                        <a href="{{ route('home') }}" class="text-gray-500 transition hover:text-brand-600 dark:text-gray-400 dark:hover:text-brand-400">
                            &larr; Kembali ke halaman depan
                        </a>
                    </p>
                </div>
            </main>
        </div>
    </body>
</html>
