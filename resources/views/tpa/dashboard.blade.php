@extends('tpa.layout')

@section('title', 'Dasbor')

@php
    $accent = ['twk' => 'from-brand-500 to-brand-700', 'tiu' => 'from-ink-500 to-ink-700', 'tkp' => 'from-gold-400 to-gold-600'];
    $fmt = fn ($v, $suffix = '') => $v === null ? '–' : rtrim(rtrim(number_format($v, 1, ',', '.'), '0'), ',').$suffix;
@endphp

@section('content')
    <div class="space-y-6">
        <section>
            <p class="text-sm text-ink-500 dark:text-porcelain-300/80">Halo,</p>
            <h1 class="text-2xl font-extrabold capitalize tracking-tight sm:text-3xl">{{ $user->username }} 👋</h1>
            <p class="mt-1 text-sm text-ink-500 dark:text-porcelain-300/80">Pilih jenis tes, atur tingkat kesulitan, dan kerjakan 15 soal yang disusun khusus untukmu.</p>
        </section>

        @if ($unfinished)
            <div class="flex flex-col gap-3 rounded-2xl border border-gold-200 bg-gold-50 p-4 dark:border-gold-500/30 dark:bg-gold-500/10 sm:flex-row sm:items-center">
                <p class="flex-1 text-sm"><span class="font-bold">Tryout {{ $unfinished->categoryLabel() }} ({{ $unfinished->difficulty }}%) belum selesai.</span> Lanjutkan dari soal terakhir.</p>
                <div class="flex gap-2">
                    <a href="{{ route('tpa.tryout.quiz', $unfinished) }}" class="rounded-xl bg-gold-500 px-4 py-2 text-sm font-bold text-white transition hover:bg-gold-600">Lanjutkan</a>
                    <form method="POST" action="{{ route('tpa.tryout.destroy', $unfinished) }}" onsubmit="return confirm('Buang tryout yang belum selesai ini?')">
                        @csrf @method('DELETE')
                        <button class="rounded-xl px-4 py-2 text-sm font-semibold text-ink-600 transition hover:bg-black/5 dark:text-porcelain-200 dark:hover:bg-white/10">Buang</button>
                    </form>
                </div>
            </div>
        @endif

        {{-- Pilihan tryout --}}
        <section class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            @foreach ($categories as $key => $cat)
                <a href="{{ route('tpa.tryout.start', $key) }}" class="group relative overflow-hidden rounded-2xl bg-gradient-to-br {{ $accent[$key] }} p-5 text-white shadow-lg shadow-ink-950/10 transition hover:-translate-y-0.5 active:scale-[.98]">
                    <p class="font-mono text-xs font-bold tracking-[.18em] text-white/80">{{ $cat['short'] }}</p>
                    <h2 class="mt-1 text-lg font-bold leading-snug">{{ $cat['name'] }}</h2>
                    <p class="mt-3 text-xs text-white/80">{{ count($cat['subtopics']) }} sub-materi · 15 soal</p>
                    @php $bp = $bankProgress[$key] ?? ['total' => 0, 'answered' => 0]; @endphp
                    @if ($bp['total'] > 0)
                        <div class="mt-3 rounded-xl bg-white/15 p-3">
                            <div class="flex items-baseline justify-between gap-2 text-xs">
                                <span><span class="font-mono text-base font-bold">{{ number_format($bp['total'], 0, ',', '.') }}</span> soal tersedia</span>
                                <span><span class="font-mono font-bold">{{ number_format($bp['answered'], 0, ',', '.') }}</span> sudah dikerjakan</span>
                            </div>
                            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-white/25" role="progressbar" aria-label="Soal {{ $cat['short'] }} yang sudah dikerjakan" aria-valuemin="0" aria-valuemax="{{ $bp['total'] }}" aria-valuenow="{{ $bp['answered'] }}">
                                <div class="h-full rounded-full bg-white" style="width: {{ min(100, round($bp['answered'] / $bp['total'] * 100)) }}%"></div>
                            </div>
                        </div>
                    @endif
                    <span class="mt-4 inline-flex items-center gap-1 text-sm font-bold">Mulai tryout <x-heroicon-o-arrow-right class="h-4 w-4 transition group-hover:translate-x-1" /></span>
                </a>
            @endforeach
        </section>

        {{-- Ringkasan ketepatan per jendela waktu --}}
        <section x-data="{ win: 7 }" class="rounded-[1.5rem] border border-black/5 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-ink-900/60">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-base font-bold">Ketepatan menjawab</h2>
                <div class="flex rounded-xl bg-porcelain-100 p-1 text-xs font-semibold dark:bg-white/5">
                    @foreach ($stats['windows'] as $days => $w)
                        <button type="button" @click="win = {{ $days }}" :class="win === {{ $days }} ? 'bg-white text-brand-700 shadow-sm dark:bg-ink-800 dark:text-brand-200' : 'text-ink-500 dark:text-porcelain-300'" class="rounded-lg px-3 py-1.5 transition">{{ $days === 30 ? '1 bulan' : ($days === 7 ? '1 minggu' : '3 hari') }}</button>
                    @endforeach
                </div>
            </div>

            @foreach ($stats['windows'] as $days => $w)
                <div x-show="win === {{ $days }}" @if ($days !== 7) x-cloak style="display:none" @endif class="mt-4">
                    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                        <div class="rounded-xl bg-brand-50 p-4 dark:bg-brand-500/10">
                            <p class="text-xs font-semibold text-brand-700 dark:text-brand-200">Semua tes</p>
                            <p class="mt-1 font-mono text-2xl font-bold">{{ $fmt($w['all']['accuracy'], '%') }}</p>
                            <p class="mt-1 text-[11px] text-ink-500 dark:text-porcelain-300/70">{{ $w['all']['tryouts'] }} tryout · {{ $fmt($w['all']['avg_seconds'], ' dtk') }}/soal</p>
                        </div>
                        @foreach ($w['categories'] as $cat => $s)
                            <div class="rounded-xl bg-porcelain-100 p-4 dark:bg-white/5">
                                <p class="text-xs font-semibold text-ink-600 dark:text-porcelain-200">{{ $categories[$cat]['short'] }}</p>
                                <p class="mt-1 font-mono text-2xl font-bold">{{ $fmt($s['accuracy'], '%') }}</p>
                                <p class="mt-1 text-[11px] text-ink-500 dark:text-porcelain-300/70">{{ $s['tryouts'] }} tryout · {{ $fmt($s['avg_seconds'], ' dtk') }}/soal</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
            <p class="mt-3 text-[11px] text-ink-500 dark:text-porcelain-300/70">TWK/TIU: persentase jawaban benar. TKP: skor yang diperoleh dibanding skor maksimum (5 per soal).</p>
        </section>

        {{-- Grafik 30 hari --}}
        <section class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <article class="rounded-[1.5rem] border border-black/5 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-ink-900/60">
                <h2 class="text-base font-bold">Peningkatan akurasi</h2>
                <p class="text-xs text-ink-500 dark:text-porcelain-300/70">Rata-rata ketepatan harian, 30 hari terakhir (%)</p>
                <div class="mt-3 h-64"><canvas data-tpa-chart='@json(['metric' => 'accuracy', ...$stats['chart']])'></canvas></div>
            </article>
            <article class="rounded-[1.5rem] border border-black/5 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-ink-900/60">
                <h2 class="text-base font-bold">Kecepatan menjawab</h2>
                <p class="text-xs text-ink-500 dark:text-porcelain-300/70">Rata-rata detik per soal, 30 hari terakhir (makin rendah makin cepat)</p>
                <div class="mt-3 h-64"><canvas data-tpa-chart='@json(['metric' => 'speed', ...$stats['chart']])'></canvas></div>
            </article>
        </section>

        {{-- Tryout terakhir --}}
        <section class="rounded-[1.5rem] border border-black/5 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-ink-900/60">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-bold">Tryout terakhir</h2>
                <a href="{{ route('tpa.history') }}" class="text-xs font-bold text-brand-600 dark:text-brand-300">Lihat semua</a>
            </div>
            @include('tpa.partials.tryout-list', ['tryouts' => $recent])
        </section>
    </div>
@endsection
