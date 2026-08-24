@props(['eyebrow', 'title', 'description' => null])

<section class="relative overflow-hidden rounded-[1.75rem] border border-brand-100 bg-[radial-gradient(circle_at_86%_0%,rgba(179,227,236,.65),transparent_28%),linear-gradient(135deg,#ffffff,#eff9fb)] p-5 shadow-xl shadow-ink-950/5 dark:border-white/10 dark:bg-[radial-gradient(circle_at_78%_7%,rgba(27,137,163,.30),transparent_24%),linear-gradient(135deg,#071b29,#0b1627_55%,#14243a)] dark:shadow-2xl dark:shadow-ink-950/20 sm:p-7 lg:p-9">
    <div class="pointer-events-none absolute inset-0 bg-grain opacity-0 dark:opacity-20"></div>
    <div class="relative flex flex-wrap items-start justify-between gap-5">
        <div class="max-w-2xl">
            <p class="font-mono text-[10px] font-bold uppercase tracking-[0.22em] text-brand-600 dark:text-brand-200">{{ $eyebrow }}</p>
            <h1 class="mt-3 text-3xl font-bold tracking-tight text-ink-950 dark:text-white sm:text-4xl">{{ $title }}</h1>
            @if ($description)
                <p class="mt-3 text-sm leading-relaxed text-ink-500 dark:text-porcelain-200/75 sm:text-base">{{ $description }}</p>
            @endif
        </div>
        @isset($action)
            <div class="shrink-0">{{ $action }}</div>
        @endisset
    </div>
    @isset($content)
        <div class="relative mt-8">{{ $content }}</div>
    @endisset
</section>
