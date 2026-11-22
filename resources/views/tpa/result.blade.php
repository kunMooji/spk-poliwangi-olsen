@extends('tpa.layout')

@section('title', 'Pembahasan '.$meta['short'])

@section('content')
    @php
        $isTkp = $tryout->isTkp();
        $hintCount = $tryout->questions->where('hint_used', true)->count();
    @endphp

    <div class="mx-auto max-w-3xl space-y-5">
        <a href="{{ route('tpa.dashboard') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-ink-500 hover:text-brand-600 dark:text-porcelain-300">
            <x-heroicon-o-arrow-left class="h-4 w-4" /> Dasbor
        </a>

        {{-- Ringkasan skor --}}
        <section class="rounded-[1.5rem] bg-gradient-to-br from-brand-600 to-ink-800 p-6 text-white shadow-xl shadow-ink-950/10 sm:p-8">
            <p class="font-mono text-xs font-bold tracking-[.18em] text-white/75">{{ $meta['short'] }} · KESULITAN {{ $tryout->difficulty }}% · {{ $tryout->finished_at->translatedFormat('d M Y, H:i') }}</p>
            <div class="mt-3 flex flex-wrap items-end gap-x-8 gap-y-4">
                <div>
                    <p class="font-mono text-5xl font-bold">{{ $tryout->score }}<span class="text-2xl text-white/60">/{{ $tryout->max_score }}</span></p>
                    <p class="text-sm text-white/80">Skor</p>
                </div>
                <div>
                    <p class="font-mono text-3xl font-bold">{{ $tryout->accuracy() }}%</p>
                    <p class="text-sm text-white/80">Ketepatan</p>
                </div>
                <div>
                    <p class="font-mono text-3xl font-bold">{{ $tryout->correct_count }}/{{ $tryout->questions->count() }}</p>
                    <p class="text-sm text-white/80">{{ $isTkp ? 'Pilihan terbaik (5 poin)' : 'Benar' }}</p>
                </div>
                <div>
                    <p class="font-mono text-3xl font-bold">{{ $tryout->averageSecondsPerQuestion() }}<span class="text-lg"> dtk</span></p>
                    <p class="text-sm text-white/80">Rata-rata/soal</p>
                </div>
            </div>
            <p class="mt-4 text-xs text-white/70">Total waktu {{ gmdate('i:s', intdiv($tryout->total_time_ms, 1000)) }} · Hint dipakai {{ $hintCount }} kali</p>
            <a href="{{ route('tpa.tryout.start', $tryout->category) }}" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-brand-700 transition hover:bg-brand-50">Tryout {{ $meta['short'] }} lagi <x-heroicon-o-arrow-path class="h-4 w-4" /></a>
        </section>

        {{-- Pembahasan per soal --}}
        @foreach ($tryout->questions as $q)
            @php
                $best = $q->bestKey();
                $state = $q->user_answer === null ? 'empty' : ($q->is_correct ? 'right' : 'wrong');
                $badge = [
                    'right' => ['bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300', $isTkp ? 'Terbaik' : 'Benar'],
                    'wrong' => ['bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300', $isTkp ? 'Kurang tepat' : 'Salah'],
                    'empty' => ['bg-porcelain-200 text-ink-600 dark:bg-white/10 dark:text-porcelain-200', 'Kosong'],
                ][$state];
            @endphp
            <article x-data="{ open: {{ $state === 'right' ? 'false' : 'true' }} }" class="rounded-[1.5rem] border border-black/5 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-ink-900/60 sm:p-6">
                <button type="button" @click="open = !open" class="flex w-full flex-wrap items-center gap-2 text-left">
                    <span class="font-mono text-xs font-bold tracking-[.14em] text-brand-600 dark:text-brand-300">SOAL {{ $q->number }}</span>
                    <span class="text-xs text-ink-500 dark:text-porcelain-300/70">{{ $q->sub_topic }}</span>
                    <span class="ml-auto rounded-lg px-2 py-0.5 text-xs font-bold {{ $badge[0] }}">{{ $badge[1] }} · {{ $q->points }} poin</span>
                    <span class="font-mono text-xs text-ink-400">{{ round($q->time_spent_ms / 1000) }} dtk</span>
                    @if ($q->hint_used)<span class="text-xs" title="Hint dipakai">💡</span>@endif
                    <x-heroicon-o-chevron-down class="h-4 w-4 text-ink-400 transition" ::class="open && 'rotate-180'" />
                </button>

                <div x-show="open" x-transition.opacity.duration.150ms @if ($state === 'right') style="display:none" @endif>
                    <p class="mt-4 whitespace-pre-line text-[15px] leading-relaxed">{{ $q->question }}</p>

                    <ul class="mt-4 space-y-2">
                        @foreach ($q->options as $opt)
                            @php
                                $isUser = $q->user_answer === $opt['key'];
                                $isBest = $best === $opt['key'];
                                $cls = $isBest
                                    ? 'border-emerald-400 bg-emerald-50 dark:bg-emerald-500/10'
                                    : ($isUser ? 'border-red-300 bg-red-50 dark:bg-red-500/10' : 'border-black/5 dark:border-white/10');
                            @endphp
                            <li class="flex items-start gap-3 rounded-xl border-2 p-3 text-sm {{ $cls }}">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-porcelain-100 font-mono text-xs font-bold dark:bg-white/10">{{ $opt['key'] }}</span>
                                <span class="flex-1 pt-1 leading-relaxed">{{ $opt['text'] }}</span>
                                <span class="shrink-0 pt-1 text-right text-xs font-bold">
                                    @if ($isTkp)<span class="font-mono">{{ $opt['score'] }} poin</span>@endif
                                    @if ($isUser)<span class="block text-ink-500 dark:text-porcelain-300">Jawabanmu</span>@endif
                                    @if ($isBest && ! $isTkp)<span class="block text-emerald-700 dark:text-emerald-300">Kunci</span>@endif
                                </span>
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-4 rounded-xl bg-porcelain-100 p-4 text-sm dark:bg-white/5">
                        <p class="font-bold">💡 Hint</p>
                        <p class="mt-1 text-ink-600 dark:text-porcelain-200/80">{{ $q->hint }}</p>
                    </div>
                    <div class="mt-3 rounded-xl bg-brand-50 p-4 text-sm dark:bg-brand-500/10">
                        <p class="font-bold text-brand-700 dark:text-brand-200">Pembahasan</p>
                        <p class="mt-1 whitespace-pre-line leading-relaxed text-ink-700 dark:text-porcelain-100/90">{{ $q->explanation }}</p>
                    </div>
                </div>
            </article>
        @endforeach
    </div>
@endsection
