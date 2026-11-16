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
        <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700,800|space-mono:400,700|caveat:600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}?v=3">
    </head>
    <body class="font-sans antialiased">
        @php($dashboardRoute = Auth::user()->isAdmin() ? route('admin.dashboard') : route('dashboard'))

        <div class="min-h-screen bg-porcelain-50 dark:bg-ink-950">
            @include('layouts.navigation')

            <!-- App bar konsisten untuk administrator dan calon mahasiswa. -->
            <header class="sticky top-0 z-30 border-b border-black/5 bg-white/90 backdrop-blur dark:border-white/10 dark:bg-ink-900/90">
                <div class="flex w-full items-center gap-3 px-4 py-3 pr-40 sm:px-6 sm:pr-56">
                    <a href="{{ $dashboardRoute }}" class="shrink-0 rounded-lg transition hover:scale-105 focus:outline-none focus:ring-2 focus:ring-brand-500/60 focus:ring-offset-2 dark:focus:ring-offset-ink-900" aria-label="Kembali ke dasbor">
                        <img src="{{ asset('images/poliwangi_logo.png') }}" alt="Logo Politeknik Negeri Banyuwangi" class="h-10 w-10 shrink-0 object-contain">
                    </a>
                    <div class="group relative min-w-0 max-w-full cursor-pointer rounded-lg outline-none [&_h2]:min-w-0 [&_h2]:truncate max-sm:[&_h2]:text-sm transition hover:text-brand-700 focus-visible:ring-2 focus-visible:ring-brand-500/60 dark:hover:text-brand-200"
                         role="link" tabindex="0" aria-label="Kembali ke dasbor"
                         x-data
                         @click="if (!$event.target.closest('a, button, input, select, textarea, label')) window.location.href = '{{ $dashboardRoute }}'"
                         @keydown.enter.prevent="window.location.href = '{{ $dashboardRoute }}'"
                         @keydown.space.prevent="window.location.href = '{{ $dashboardRoute }}'">
                        <span class="pointer-events-none absolute left-[calc(100%+0.75rem)] top-1/2 z-50 whitespace-nowrap rounded-md bg-white px-3 py-2 text-sm font-medium text-ink-700 opacity-0 shadow-lg shadow-ink-950/15 transition duration-200 -translate-x-1 -translate-y-1/2 group-hover:translate-x-0 group-hover:opacity-100 group-focus-visible:translate-x-0 group-focus-visible:opacity-100 dark:bg-ink-800 dark:text-porcelain-50">Back to Home</span>
                        @isset($header)
                            {{ $header }}
                        @else
                            <h2 class="font-display text-[10px] font-extrabold tracking-wide text-ink-900 dark:text-porcelain-50 sm:text-sm">SPK Poliwangi</h2>
                        @endisset
                    </div>
                </div>

                {{-- Slot opsional di sisi bawah app bar (mis. bilah kemajuan tes di HP). --}}
                @isset($progressBar)
                    {{ $progressBar }}
                @endisset
            </header>

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        @stack('scripts')
    </body>
</html>
