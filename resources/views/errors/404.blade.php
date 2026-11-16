<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#dcecff">
        <title>Halaman tidak ditemukan · {{ config('app.name', 'SPK Poliwangi') }}</title>

        <script>
            (function () {
                document.documentElement.classList.toggle('dark', localStorage.getItem('theme') === 'dark');
            })();
        </script>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css'])
        <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}?v=3">
    </head>
    <body class="min-h-screen bg-[#dcecff] font-sans antialiased transition-colors dark:bg-ink-950">
        <main class="mx-auto flex min-h-screen max-w-[1600px] items-center p-3 sm:p-5 lg:p-7">
            <section class="relative w-full overflow-hidden rounded-[2rem] bg-white px-6 py-7 shadow-[0_24px_70px_rgba(34,104,160,0.16)] dark:bg-ink-900 sm:px-10 sm:py-9 lg:min-h-[calc(100dvh-3.5rem)] lg:px-14 xl:px-20" aria-labelledby="not-found-title">
                <div class="pointer-events-none absolute -left-20 -top-20 h-64 w-64 rounded-full bg-sky-100/80 blur-3xl dark:bg-brand-500/10"></div>
                <div class="pointer-events-none absolute -bottom-28 -right-20 h-72 w-72 rounded-full bg-brand-100/70 blur-3xl dark:bg-brand-500/10"></div>

                <header class="relative z-10 flex items-center justify-between gap-5">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-2.5 rounded-xl text-ink-900 transition hover:opacity-75 focus:outline-none focus:ring-2 focus:ring-brand-500/60 focus:ring-offset-2 dark:text-porcelain-50 dark:focus:ring-offset-ink-900" aria-label="Kembali ke halaman utama SPK Poliwangi">
                        <img src="{{ asset('images/poliwangi_logo.png') }}" alt="Logo Politeknik Negeri Banyuwangi" class="h-10 w-10 object-contain sm:h-11 sm:w-11">
                        <span class="font-display text-xs font-extrabold tracking-tight sm:text-sm">SPK Poliwangi</span>
                    </a>
                    <a href="{{ route('home') }}" class="text-sm font-semibold text-ink-500 transition hover:text-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500/60 focus:ring-offset-2 dark:text-porcelain-300 dark:hover:text-brand-300 dark:focus:ring-offset-ink-900">Beranda</a>
                </header>

                <div class="relative z-10 mx-auto grid grid-cols-1 max-w-6xl items-center gap-3 pt-6 lg:grid-cols-[1.04fr_0.96fr] lg:gap-12 lg:pt-0">
                    <div class="order-2 flex justify-center lg:order-1 lg:justify-start">
                        <img src="{{ asset('images/not-found-student.png') }}" alt="Mahasiswa mengenakan toga yang kebingungan melihat pesan 404 di ponsel" class="w-full max-w-[37rem] object-contain mix-blend-multiply dark:mix-blend-normal lg:w-[52rem] lg:max-w-none lg:translate-x-4 lg:scale-110">
                    </div>

                    <div class="order-1 max-w-xl lg:order-2">
                        <p class="font-mono text-xs font-bold uppercase tracking-[0.2em] text-brand-600 dark:text-brand-300">Halaman tidak ditemukan</p>
                        <h1 id="not-found-title" class="mt-4 font-display text-4xl font-extrabold leading-[1.12] tracking-tight text-ink-900 dark:text-porcelain-50 sm:text-5xl xl:text-6xl">Waduh, Halaman Ini Tidak Ada.</h1>
                        <p class="mt-5 max-w-lg text-base leading-7 text-ink-600 dark:text-porcelain-300 sm:text-lg">Tautan yang Anda buka mungkin sudah dipindahkan, dihapus, atau alamatnya tidak tepat. Mari kembali ke halaman utama.</p>

                        <div class="mt-8 flex flex-wrap items-center gap-4">
                            <a href="{{ route('home') }}" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-700/20 transition hover:-translate-y-0.5 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500/60 focus:ring-offset-2 active:translate-y-0 dark:focus:ring-offset-ink-900">
                                Kembali ke beranda
                                <x-heroicon-o-arrow-right class="h-4 w-4" />
                            </a>
                            @auth
                                <a href="{{ Auth::user()->isAdmin() ? route('admin.dashboard') : route('dashboard') }}" class="text-sm font-semibold text-brand-700 transition hover:text-brand-900 focus:outline-none focus:ring-2 focus:ring-brand-500/60 focus:ring-offset-2 dark:text-brand-300 dark:hover:text-brand-100 dark:focus:ring-offset-ink-900">Buka dasbor</a>
                            @else
                                <a href="{{ route('login') }}" class="text-sm font-semibold text-brand-700 transition hover:text-brand-900 focus:outline-none focus:ring-2 focus:ring-brand-500/60 focus:ring-offset-2 dark:text-brand-300 dark:hover:text-brand-100 dark:focus:ring-offset-ink-900">Masuk ke akun</a>
                            @endauth
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </body>
</html>
