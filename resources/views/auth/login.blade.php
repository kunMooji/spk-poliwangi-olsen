<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Masuk ke akun Anda — {{ config('app.name') }}</title>

        {{-- Menentukan tema sebelum halaman dicat — lihat catatan yang sama
             di layouts/app.blade.php. --}}
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

        <div class="flex min-h-[100dvh] items-center justify-center px-4 py-6 sm:px-6 lg:px-8 lg:py-6">
            <div class="w-full max-w-5xl">
                <div class="mb-4 flex items-center justify-between">
                    <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                        <img src="{{ asset('images/poliwangi_logo.png') }}" alt="Logo Politeknik Negeri Banyuwangi" class="h-9 w-9 object-contain">
                        <span class="font-display text-sm font-extrabold uppercase tracking-wide text-ink-900 dark:text-porcelain-100">
                            SPK <span class="text-brand-600">Poliwangi</span>
                        </span>
                    </a>
                    <x-theme-toggle />
                </div>

                {{-- Satu kartu, dua kolom: formulir di kiri, foto kampus di kanan
                     — bukan panel identitas gelap terpisah seperti sebelumnya. --}}
                <div class="grid grid-cols-1 overflow-hidden rounded-[2rem] border border-black/5 bg-white shadow-2xl shadow-ink-950/10 dark:border-white/10 dark:bg-ink-900/60 lg:grid-cols-2">

                    <div class="animate-fade-up p-8 sm:p-10 lg:px-10 lg:py-6">
                        <span class="inline-flex items-center gap-2 rounded-full border border-black/10 bg-black/[0.03] px-3 py-1 font-mono text-[11px] uppercase tracking-[0.2em] text-ink-500 dark:border-white/10 dark:bg-white/5 dark:text-porcelain-300/70">
                            <x-heroicon-o-map-pin class="h-3.5 w-3.5 text-brand-600" aria-hidden="true" />
                            Politeknik Negeri Banyuwangi
                        </span>

                        <h1 class="mt-4 font-display text-4xl font-extrabold uppercase leading-[1.05] tracking-tight text-ink-950 dark:text-porcelain-50">
                            Masuk ke<br>akun Anda.
                        </h1>

                        <p class="mt-3 max-w-sm text-sm leading-relaxed text-ink-600 dark:text-porcelain-300/70">
                            Lanjutkan tes yang tertunda atau buka kembali hasil rekomendasi program studi Anda.
                        </p>

                        <x-auth-session-status class="mt-4" :status="session('status')" />

                        <form method="POST" action="{{ route('login') }}"
                              x-data="{ submitting: false }" @submit="submitting = true"
                              class="mt-6 space-y-4">
                            @csrf

                            {{-- Surel & kata sandi digambar sebagai satu "jalur" —
                                 dua titik terhubung garis putus-putus — alih-alih dua
                                 kotak input terpisah yang generik. --}}
                            <div class="relative rounded-2xl border border-black/10 bg-black/[0.02] px-4 py-1 dark:border-white/10 dark:bg-white/5">
                                <div class="pointer-events-none absolute bottom-[34px] left-[27px] top-[34px] w-px border-l border-dashed border-black/15 dark:border-white/15"></div>

                                <div class="relative flex items-center gap-3 py-2">
                                    <span class="z-10 flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-black/10 bg-white dark:border-white/10 dark:bg-ink-900">
                                        <x-heroicon-o-envelope class="h-3.5 w-3.5 text-ink-500 dark:text-porcelain-300" />
                                    </span>
                                    <input id="email" name="email" type="email" required autofocus autocomplete="username"
                                           value="{{ old('email') }}" placeholder="Alamat surel"
                                           class="w-full border-0 bg-transparent p-0 text-sm text-ink-900 placeholder:text-ink-400 focus:outline-none focus:ring-0 dark:text-porcelain-100 dark:placeholder:text-porcelain-400/50">
                                </div>

                                <hr class="ml-10 border-black/10 dark:border-white/10">

                                <div class="relative flex items-center gap-3 py-2" x-data="{ visible: false }">
                                    <span class="z-10 flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-black/10 bg-white dark:border-white/10 dark:bg-ink-900">
                                        <x-heroicon-o-lock-closed class="h-3.5 w-3.5 text-ink-500 dark:text-porcelain-300" />
                                    </span>
                                    <input id="password" name="password" required autocomplete="current-password"
                                           placeholder="Kata sandi" x-bind:type="visible ? 'text' : 'password'" type="password"
                                           class="w-full border-0 bg-transparent p-0 text-sm text-ink-900 placeholder:text-ink-400 focus:outline-none focus:ring-0 dark:text-porcelain-100 dark:placeholder:text-porcelain-400/50">
                                    <button type="button" @click="visible = !visible"
                                            class="shrink-0 text-ink-400 transition duration-150 ease-brand-out hover:text-ink-600 dark:text-porcelain-400/60 dark:hover:text-porcelain-200"
                                            :aria-label="visible ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'">
                                        <x-heroicon-o-eye x-show="!visible" class="h-4 w-4" />
                                        <x-heroicon-o-eye-slash x-show="visible" x-cloak class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                            <x-input-error :messages="array_merge($errors->get('email'), $errors->get('password'))" class="mt-2" />

                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <label for="remember_me" class="flex cursor-pointer select-none items-center gap-2.5">
                                    <input id="remember_me" name="remember" type="checkbox"
                                           class="rounded border-black/15 text-brand-600 shadow-sm transition duration-150 ease-brand-out focus:ring-brand-500 dark:border-white/15 dark:bg-ink-900">
                                    <span class="text-sm text-ink-600 dark:text-porcelain-300/80">Ingat saya</span>
                                </label>

                                @if (Route::has('password.request'))
                                    <a href="{{ route('password.request') }}"
                                       class="text-sm font-medium text-brand-600 transition duration-150 ease-brand-out hover:text-brand-500 dark:text-brand-400">
                                        Lupa kata sandi?
                                    </a>
                                @endif
                            </div>

                            <div class="flex flex-wrap items-center gap-5 pt-2">
                                <button type="submit" :disabled="submitting"
                                        class="inline-flex h-11 items-center justify-center gap-2 rounded-full bg-brand-600 px-8 text-sm font-bold uppercase tracking-wide text-porcelain-50 shadow-[0_0_0_1px_rgba(21,115,140,0.35)] transition duration-300 ease-brand-out hover:-translate-y-0.5 hover:bg-brand-700 hover:shadow-lg active:scale-[0.98] disabled:pointer-events-none disabled:opacity-70">
                                    <x-heroicon-o-arrow-path x-show="submitting" x-cloak class="h-4 w-4 animate-spin" />
                                    <span x-text="submitting ? 'Memproses…' : 'Masuk'"></span>
                                </button>

                                <a href="{{ route('register') }}" class="group inline-flex items-center text-sm text-ink-500 transition duration-150 ease-brand-out hover:text-brand-600 dark:text-porcelain-300/70 dark:hover:text-brand-300">
                                    Belum punya akun? Daftar sebagai calon mahasiswa
                                    <x-heroicon-o-arrow-right class="ml-1 h-4 w-4 transform transition-transform duration-200 ease-brand-out group-hover:translate-x-1" />
                                </a>
                            </div>
                        </form>
                    </div>

                    <div class="relative hidden lg:block">
                        <img src="{{ asset('images/hotel_poliwangi.png') }}"
                             alt="Gedung kampus Politeknik Negeri Banyuwangi"
                             class="absolute inset-0 h-full w-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-ink-950/40 via-transparent to-transparent"></div>
                    </div>
                </div>

                <p class="mt-4 text-center text-sm">
                    <a href="{{ route('home') }}"
                       class="inline-flex items-center gap-1.5 text-ink-500 transition duration-150 ease-brand-out hover:text-brand-600 dark:text-porcelain-400/70 dark:hover:text-brand-300">
                        <x-heroicon-o-arrow-left class="h-3.5 w-3.5" />
                        Kembali ke halaman depan
                    </a>
                </p>
            </div>
        </div>
    </body>
</html>
