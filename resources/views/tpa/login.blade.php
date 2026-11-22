@extends('tpa.layout')

@section('title', 'Masuk')

@section('content')
    <div class="flex min-h-[80dvh] items-center justify-center">
        <div class="w-full max-w-sm rounded-[1.5rem] border border-black/5 bg-white p-6 shadow-xl shadow-ink-950/[.05] dark:border-white/10 dark:bg-ink-900/70 sm:p-8">
            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-600 font-mono text-sm font-bold text-white">SKD</span>
            <h1 class="mt-5 text-2xl font-extrabold tracking-tight">Latihan SKD CPNS</h1>
            <p class="mt-1 text-sm text-ink-500 dark:text-porcelain-300/80">Masuk untuk melanjutkan latihan TWK, TIU, dan TKP.</p>

            <form method="POST" action="{{ route('tpa.login.store') }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label for="username" class="text-sm font-semibold">Username</label>
                    <input id="username" name="username" type="text" value="{{ old('username') }}" required autofocus autocomplete="username" autocapitalize="none"
                        class="mt-1 block w-full rounded-xl border-black/10 bg-porcelain-50 px-4 py-3 text-sm focus:border-brand-500 focus:ring-brand-500 dark:border-white/10 dark:bg-ink-950">
                </div>
                <div>
                    <label for="password" class="text-sm font-semibold">Kata sandi</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password"
                        class="mt-1 block w-full rounded-xl border-black/10 bg-porcelain-50 px-4 py-3 text-sm focus:border-brand-500 focus:ring-brand-500 dark:border-white/10 dark:bg-ink-950">
                </div>

                @error('username')
                    <p class="rounded-xl bg-red-50 px-4 py-3 text-sm font-medium text-red-700 dark:bg-red-500/10 dark:text-red-300">{{ $message }}</p>
                @enderror

                <label class="flex items-center gap-2 text-sm text-ink-600 dark:text-porcelain-300">
                    <input type="checkbox" name="remember" class="rounded border-black/20 text-brand-600 focus:ring-brand-500">
                    Ingat saya
                </label>

                <button type="submit" class="w-full rounded-xl bg-brand-600 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-brand-600/20 transition hover:bg-brand-700 active:scale-[.98]">Masuk</button>
            </form>
        </div>
    </div>
@endsection
