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
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800|unbounded:600,700,800|space-mono:400,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-porcelain-50 dark:bg-ink-950">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="border-b border-black/5 bg-white/70 backdrop-blur dark:border-white/10 dark:bg-ink-900/60">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        @stack('scripts')
    </body>
</html>
