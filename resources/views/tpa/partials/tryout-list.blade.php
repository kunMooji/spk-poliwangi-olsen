@if ($tryouts->isEmpty())
    <p class="mt-4 text-sm text-ink-500 dark:text-porcelain-300/70">Belum ada tryout. Mulai dari salah satu jenis tes di atas.</p>
@else
    <ul class="mt-3 divide-y divide-black/5 dark:divide-white/10">
        @foreach ($tryouts as $t)
            <li>
                <a href="{{ $t->isFinished() ? route('tpa.tryout.result', $t) : route('tpa.tryout.quiz', $t) }}" class="flex items-center gap-3 rounded-xl px-2 py-3 transition hover:bg-porcelain-100 dark:hover:bg-white/5">
                    <span class="flex h-10 w-12 shrink-0 items-center justify-center rounded-lg bg-brand-50 font-mono text-xs font-bold text-brand-700 dark:bg-brand-500/15 dark:text-brand-200">{{ $t->categoryLabel() }}</span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-semibold">Kesulitan {{ $t->difficulty }}%</span>
                        <span class="block text-xs text-ink-500 dark:text-porcelain-300/70">{{ ($t->finished_at ?? $t->created_at)->translatedFormat('d M Y, H:i') }}</span>
                    </span>
                    @if ($t->isFinished())
                        <span class="text-right">
                            <span class="block font-mono text-sm font-bold">{{ $t->score }}/{{ $t->max_score }}</span>
                            <span class="block text-xs text-ink-500 dark:text-porcelain-300/70">{{ $t->accuracy() }}% · {{ $t->averageSecondsPerQuestion() }} dtk/soal</span>
                        </span>
                    @else
                        <span class="rounded-lg bg-gold-100 px-2 py-1 text-xs font-bold text-gold-700 dark:bg-gold-500/15 dark:text-gold-200">Berjalan</span>
                    @endif
                </a>
            </li>
        @endforeach
    </ul>
@endif
