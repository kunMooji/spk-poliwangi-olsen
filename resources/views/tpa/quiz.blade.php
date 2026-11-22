@extends('tpa.layout')

@section('title', 'Mengerjakan '.$meta['short'])

@section('content')
    <div x-data="tpaQuiz(@js(['questions' => $questions, 'answerUrl' => route('tpa.tryout.answer', $tryout)]))" class="mx-auto max-w-3xl">
        {{-- Status atas --}}
        <div class="flex flex-wrap items-center gap-3">
            <span class="rounded-lg bg-brand-600 px-2.5 py-1 font-mono text-xs font-bold text-white">{{ $meta['short'] }} · {{ $tryout->difficulty }}%</span>
            <span class="text-sm font-semibold"><span x-text="answeredCount"></span>/<span x-text="questions.length"></span> terjawab</span>
            <span class="ml-auto inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-1.5 font-mono text-sm font-bold shadow-sm dark:bg-ink-900" title="Waktu di soal ini">
                <x-heroicon-o-clock class="h-4 w-4 text-brand-600" /> <span x-text="formatTime(currentSeconds)"></span>
            </span>
        </div>
        <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-black/5 dark:bg-white/10">
            <div class="h-full rounded-full bg-brand-500 transition-all duration-300" :style="`width: ${answeredCount / questions.length * 100}%`"></div>
        </div>

        {{-- Kartu soal --}}
        <article class="mt-5 rounded-[1.5rem] border border-black/5 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-ink-900/60 sm:p-7">
            <div class="flex items-start justify-between gap-3">
                <p class="font-mono text-xs font-bold tracking-[.14em] text-brand-600 dark:text-brand-300">
                    SOAL <span x-text="current.number"></span> · <span x-text="current.sub_topic"></span>
                </p>
                <button type="button" @click="toggleHint" class="inline-flex shrink-0 items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-bold transition"
                    :class="showHint ? 'bg-gold-500 text-white' : 'bg-gold-50 text-gold-700 hover:bg-gold-100 dark:bg-gold-500/15 dark:text-gold-200'">
                    <x-heroicon-o-light-bulb class="h-4 w-4" /> Hint
                </button>
            </div>

            <div x-show="showHint" x-transition.opacity class="mt-3 rounded-xl border border-gold-200 bg-gold-50 p-3 text-sm text-gold-900 dark:border-gold-500/30 dark:bg-gold-500/10 dark:text-gold-100" x-text="current.hint"></div>

            <p class="mt-4 whitespace-pre-line text-[15px] leading-relaxed" x-text="current.question"></p>

            <div class="mt-5 space-y-2.5">
                <template x-for="opt in current.options" :key="current.id + opt.key">
                    <button type="button" @click="choose(opt.key)"
                        class="flex w-full items-start gap-3 rounded-xl border-2 p-3.5 text-left text-sm transition active:scale-[.99]"
                        :class="current.answer === opt.key
                            ? 'border-brand-500 bg-brand-50 dark:bg-brand-500/15'
                            : 'border-black/5 hover:border-brand-200 hover:bg-porcelain-50 dark:border-white/10 dark:hover:bg-white/5'">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg font-mono text-xs font-bold"
                            :class="current.answer === opt.key ? 'bg-brand-600 text-white' : 'bg-porcelain-100 dark:bg-white/10'" x-text="opt.key"></span>
                        <span class="pt-1 leading-relaxed" x-text="opt.text"></span>
                    </button>
                </template>
            </div>

            <div class="mt-6 flex items-center gap-3">
                <button type="button" @click="go(index - 1)" :disabled="index === 0" class="rounded-xl px-4 py-2.5 text-sm font-semibold transition hover:bg-black/5 disabled:opacity-30 dark:hover:bg-white/10">← Sebelumnya</button>
                <span class="ml-auto text-xs text-ink-400" x-show="saving">menyimpan…</span>
                <button type="button" x-show="index < questions.length - 1" @click="go(index + 1)" class="rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-brand-700 active:scale-[.98]">Berikutnya →</button>
                <button type="button" x-show="index === questions.length - 1" @click="finish" :disabled="finishing" class="rounded-xl bg-gold-500 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-gold-600 disabled:opacity-50">Selesai</button>
            </div>

            <p x-show="error" class="mt-3 text-sm font-medium text-red-600" x-text="error"></p>
        </article>

        {{-- Navigasi nomor soal --}}
        <section class="mt-5 rounded-[1.5rem] border border-black/5 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-ink-900/60">
            <div class="grid grid-cols-5 gap-2 sm:grid-cols-[repeat(15,minmax(0,1fr))]">
                <template x-for="(q, i) in questions" :key="q.id">
                    <button type="button" @click="go(i)" class="aspect-square rounded-lg font-mono text-xs font-bold transition"
                        :class="i === index ? 'ring-2 ring-brand-500 ring-offset-2 dark:ring-offset-ink-900 ' + (q.answer ? 'bg-brand-600 text-white' : 'bg-porcelain-100 dark:bg-white/10')
                            : (q.answer ? 'bg-brand-600 text-white' : 'bg-porcelain-100 hover:bg-porcelain-200 dark:bg-white/10')"
                        x-text="q.number"></button>
                </template>
            </div>
            <button type="button" @click="finish" :disabled="finishing" class="mt-4 w-full rounded-xl border-2 border-gold-500 px-4 py-2.5 text-sm font-bold text-gold-700 transition hover:bg-gold-50 disabled:opacity-50 dark:text-gold-200 dark:hover:bg-gold-500/10">Selesaikan tryout</button>
        </section>

        <form x-ref="finishForm" method="POST" action="{{ route('tpa.tryout.finish', $tryout) }}" class="hidden">@csrf</form>
    </div>
@endsection
