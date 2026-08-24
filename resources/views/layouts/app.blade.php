<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        {{-- Menentukan tema sebelum halaman dicat, supaya tidak ada kedipan
             tema terang lalu berganti gelap sesaat setelah dimuat. --}}
        <script>
            (function () {
                // Default tema: krem/terang kecuali pengguna pernah memilih gelap
                // secara eksplisit (bukan lagi mengikuti preferensi sistem).
                var stored = localStorage.getItem('theme');
                var isDark = stored === 'dark';
                document.documentElement.classList.toggle('dark', isDark);
            })();
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800|unbounded:600,700,800|space-mono:400,700|caveat:600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-porcelain-50 dark:bg-ink-950">
            @include('layouts.navigation')

            <!-- App bar konsisten untuk administrator dan calon mahasiswa. -->
            <header class="sticky top-0 z-30 border-b border-black/5 bg-white/90 backdrop-blur dark:border-white/10 dark:bg-ink-900/90">
                <div class="flex w-full items-center gap-3 px-4 py-3 pr-40 sm:px-6 sm:pr-56">
                    <img src="{{ asset('images/poliwangi_logo.png') }}" alt="Logo Politeknik Negeri Banyuwangi" class="h-10 w-10 shrink-0 object-contain">
                    <div class="min-w-0 flex-1">
                        @isset($header)
                            {{ $header }}
                        @else
                            <h2 class="font-display text-[10px] font-bold uppercase tracking-wide text-ink-900 dark:text-porcelain-50 sm:text-sm">SPK Poliwangi</h2>
                        @endisset
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        @stack('scripts')
    </body>
</html>
