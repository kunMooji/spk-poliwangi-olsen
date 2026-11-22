@extends('tpa.layout')

@section('title', 'Tryout '.$meta['short'])

@section('content')
    <div x-data="tpaStart(@js(route('tpa.tryout.generate', $category)))" class="mx-auto max-w-2xl">
        <a href="{{ route('tpa.dashboard') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-ink-500 hover:text-brand-600 dark:text-porcelain-300">
            <x-heroicon-o-arrow-left class="h-4 w-4" /> Dasbor
        </a>

        <section x-show="!loading" class="mt-4 rounded-[1.5rem] border border-black/5 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-ink-900/60 sm:p-8">
            <p class="font-mono text-xs font-bold tracking-[.18em] text-brand-600 dark:text-brand-300">{{ $meta['short'] }} · 15 SOAL</p>
            <h1 class="mt-1 text-2xl font-extrabold tracking-tight">{{ $meta['name'] }}</h1>
            <p class="mt-2 text-sm text-ink-500 dark:text-porcelain-300/80">{{ $meta['scoring'] }}</p>

            <div class="mt-4 flex flex-wrap gap-2">
                @foreach (array_keys($meta['subtopics']) as $sub)
                    <span class="rounded-lg bg-porcelain-100 px-2.5 py-1 text-xs font-medium dark:bg-white/5">{{ $sub }}</span>
                @endforeach
            </div>

            <div class="mt-8">
                <div class="flex items-end justify-between">
                    <label for="difficulty" class="text-sm font-bold">Tingkat kesulitan</label>
                    <span class="font-mono text-3xl font-bold text-brand-600 dark:text-brand-300" x-text="difficulty + '%'"></span>
                </div>
                <input id="difficulty" type="range" min="10" max="100" step="10" x-model.number="difficulty" class="mt-3 w-full accent-brand-600">
                <div class="mt-1 flex justify-between font-mono text-[10px] text-ink-400 dark:text-porcelain-400">
                    @foreach (range(10, 100, 10) as $n)<span>{{ $n }}</span>@endforeach
                </div>
                <div class="mt-4 rounded-xl bg-brand-50 p-4 dark:bg-brand-500/10">
                    <p class="text-sm font-bold text-brand-700 dark:text-brand-200" x-text="level[1]"></p>
                    <p class="mt-1 text-sm text-ink-600 dark:text-porcelain-200/80" x-text="level[2]"></p>
                </div>
            </div>

            <template x-if="error">
                <p class="mt-4 rounded-xl bg-red-50 px-4 py-3 text-sm font-medium text-red-700 dark:bg-red-500/10 dark:text-red-300" x-text="error"></p>
            </template>

            <button type="button" @click="generate" class="mt-6 w-full rounded-xl bg-brand-600 px-4 py-3.5 text-sm font-bold text-white shadow-lg shadow-brand-600/20 transition hover:bg-brand-700 active:scale-[.98]">
                <span x-text="error ? 'Coba lagi' : 'Buat soal & mulai'"></span>
            </button>
            <p class="mt-2 text-center text-xs text-ink-500 dark:text-porcelain-300/70">Soal, hint, dan pembahasan disusun oleh Claude AI. Proses ini butuh sekitar 1-3 menit.</p>
        </section>

        <section x-show="loading" x-cloak style="display:none" class="mt-4 flex flex-col items-center rounded-[1.5rem] border border-black/5 bg-white px-6 py-16 text-center shadow-sm dark:border-white/10 dark:bg-ink-900/60">
            <span class="h-12 w-12 animate-spin rounded-full border-4 border-brand-100 border-t-brand-600 motion-reduce:animate-none dark:border-white/10 dark:border-t-brand-300"></span>
            <p class="mt-6 text-lg font-bold">Claude sedang menyusun 15 soal {{ $meta['short'] }}…</p>
            <p class="mt-1 text-sm text-ink-500 dark:text-porcelain-300/80">Tingkat kesulitan <span x-text="difficulty + '%'"></span>. Jangan tutup halaman ini.</p>
            <p class="mt-4 font-mono text-sm text-ink-400" x-text="Math.floor(elapsed / 60) + ':' + String(elapsed % 60).padStart(2, '0')"></p>
        </section>
    </div>
@endsection
