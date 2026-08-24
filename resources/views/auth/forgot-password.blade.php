<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Lupa kata sandi — {{ config('app.name') }}</title>

        <script>
            (function () {
                var stored = localStorage.getItem('theme');
                document.documentElement.classList.toggle('dark', stored === 'dark');
            })();
        </script>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800|unbounded:600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="bg-porcelain-50 font-sans text-ink-900 antialiased dark:bg-ink-950 dark:text-porcelain-100">
        <x-loading-screen />

        <div class="flex min-h-[100dvh] items-center justify-center px-4 py-10 sm:px-6 lg:px-8">
            <div class="w-full max-w-5xl">
                <div class="mb-6 flex items-center justify-between">
                    <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                        <img src="{{ asset('images/poliwangi_logo.png') }}" alt="Logo Politeknik Negeri Banyuwangi" class="h-9 w-9 object-contain">
                        <span class="font-display text-sm font-extrabold uppercase tracking-wide text-ink-900 dark:text-porcelain-100">
                            SPK <span class="text-brand-600">Poliwangi</span>
                        </span>
                    </a>
                    <x-theme-toggle />
                </div>

                <div class="grid grid-cols-1 overflow-hidden rounded-[2rem] border border-black/5 bg-white shadow-2xl shadow-ink-950/10 dark:border-white/10 dark:bg-ink-900/60 lg:grid-cols-2">
                    <main class="animate-fade-up p-8 sm:p-10 lg:p-12">
                        <span class="inline-flex items-center gap-2 rounded-full border border-black/10 bg-black/[0.03] px-3 py-1 font-mono text-[11px] uppercase tracking-[0.2em] text-ink-500 dark:border-white/10 dark:bg-white/5 dark:text-porcelain-300/70">
                            <x-heroicon-o-key class="h-3.5 w-3.5 text-brand-600" aria-hidden="true" />
                            Pemulihan akun
                        </span>

                        <h1 class="mt-6 font-display text-4xl font-extrabold uppercase leading-[1.05] tracking-tight text-ink-950 dark:text-porcelain-50 sm:text-5xl">
                            Atur ulang<br>kata sandi.
                        </h1>

                        <p class="mt-4 max-w-sm text-sm leading-relaxed text-ink-600 dark:text-porcelain-300/70">
                            Masukkan alamat surel akun Anda. Kami akan mengirimkan tautan untuk membuat kata sandi baru.
                        </p>

                        <x-auth-session-status class="mt-6" :status="session('status')" />

                        <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-5">
                            @csrf

                            <div class="rounded-2xl border border-black/10 bg-black/[0.02] px-4 py-1 dark:border-white/10 dark:bg-white/5">
                                <div class="flex items-center gap-3 py-3">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-black/10 bg-white dark:border-white/10 dark:bg-ink-900">
                                        <x-heroicon-o-envelope class="h-3.5 w-3.5 text-ink-500 dark:text-porcelain-300" aria-hidden="true" />
                                    </span>
                                    <input id="email" name="email" type="email" required autofocus autocomplete="username"
                                           value="{{ old('email') }}" placeholder="Alamat surel"
                                           class="w-full border-0 bg-transparent p-0 text-sm text-ink-900 placeholder:text-ink-400 focus:outline-none focus:ring-0 dark:text-porcelain-100 dark:placeholder:text-porcelain-400/50">
                                </div>
                            </div>
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />

                            <div class="flex flex-wrap items-center gap-5 pt-2">
                                <button type="submit"
                                        class="inline-flex h-12 items-center justify-center gap-2 rounded-full bg-brand-600 px-8 text-sm font-bold uppercase tracking-wide text-porcelain-50 shadow-[0_0_0_1px_rgba(21,115,140,0.35)] transition duration-300 ease-brand-out hover:-translate-y-0.5 hover:bg-brand-700 hover:shadow-lg active:scale-[0.98]">
                                    <x-heroicon-o-paper-airplane class="h-4 w-4" aria-hidden="true" />
                                    Kirim tautan
                                </button>

                                <a href="{{ route('login') }}" class="group inline-flex items-center text-sm text-ink-500 transition duration-150 ease-brand-out hover:text-brand-600 dark:text-porcelain-300/70 dark:hover:text-brand-300">
                                    Ingat kata sandinya? Masuk
                                    <x-heroicon-o-arrow-right class="ml-1 h-4 w-4 transition-transform duration-200 ease-brand-out group-hover:translate-x-1" aria-hidden="true" />
                                </a>
                            </div>
                        </form>
                    </main>

                    <aside class="relative hidden lg:block">
                        <img src="{{ asset('images/hotel_poliwangi.png') }}"
                             alt="Gedung kampus Politeknik Negeri Banyuwangi"
                             class="absolute inset-0 h-full w-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-ink-950/40 via-transparent to-transparent"></div>
                    </aside>
                </div>

                <p class="mt-6 text-center text-sm">
                    <a href="{{ route('home') }}"
                       class="inline-flex items-center gap-1.5 text-ink-500 transition duration-150 ease-brand-out hover:text-brand-600 dark:text-porcelain-400/70 dark:hover:text-brand-300">
                        <x-heroicon-o-arrow-left class="h-3.5 w-3.5" aria-hidden="true" />
                        Kembali ke halaman depan
                    </a>
                </p>
            </div>
        </div>
    </body>
</html>
