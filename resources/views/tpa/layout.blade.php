<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        {{-- Area privat: jangan diindeks mesin pencari. --}}
        <meta name="robots" content="noindex, nofollow">

        <title>@yield('title', 'Latihan SKD') — Latihan SKD</title>

        <script>
            (function () {
                document.documentElement.classList.toggle('dark', localStorage.getItem('theme') === 'dark');
            })();
        </script>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700,800|space-mono:400,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}?v=3">
    </head>
    <body class="min-h-[100dvh] bg-porcelain-50 font-sans text-ink-900 antialiased dark:bg-ink-950 dark:text-porcelain-50">
        @auth('tpa')
            <header class="sticky top-0 z-30 border-b border-black/5 bg-white/90 backdrop-blur dark:border-white/10 dark:bg-ink-900/90">
                <div class="mx-auto flex max-w-5xl items-center gap-3 px-4 py-3 sm:px-6">
                    <a href="{{ route('tpa.dashboard') }}" class="flex items-center gap-2 font-bold tracking-tight">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-600 font-mono text-xs text-white">SKD</span>
                        <span class="hidden sm:inline">Latihan SKD CPNS</span>
                    </a>
                    <nav class="ml-auto flex items-center gap-1 text-sm">
                        <a href="{{ route('tpa.dashboard') }}" class="rounded-lg px-3 py-2 font-semibold transition hover:bg-brand-50 dark:hover:bg-white/10 {{ request()->routeIs('tpa.dashboard') ? 'text-brand-600 dark:text-brand-300' : '' }}">Dasbor</a>
                        <a href="{{ route('tpa.history') }}" class="rounded-lg px-3 py-2 font-semibold transition hover:bg-brand-50 dark:hover:bg-white/10 {{ request()->routeIs('tpa.history') ? 'text-brand-600 dark:text-brand-300' : '' }}">Riwayat</a>
                        <form method="POST" action="{{ route('tpa.logout') }}">
                            @csrf
                            <button type="submit" class="rounded-lg px-3 py-2 font-semibold text-ink-500 transition hover:bg-red-50 hover:text-red-600 dark:text-porcelain-300 dark:hover:bg-red-500/10" title="Keluar ({{ auth('tpa')->user()->username }})">Keluar</button>
                        </form>
                    </nav>
                </div>
            </header>
        @endauth

        <main class="mx-auto max-w-5xl px-4 py-6 sm:px-6 sm:py-8">
            @yield('content')
        </main>

        @stack('scripts')
    </body>
</html>
