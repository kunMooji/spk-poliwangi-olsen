@props(['text' => 'SPK REKOMENDASI PRODI', 'size' => 96])
@php($pathId = uniqid('rotating-badge-path-'))

{{--
    Lencana melingkar berputar pelan terus-menerus — teks mengitari lingkaran
    lewat SVG <textPath>, ikon tetap diam di tengah. Murni CSS @keyframes
    (lihat animate-[spin_...] di bawah), tidak butuh library animasi baru.
--}}
<div {{ $attributes->class(['relative inline-flex shrink-0 items-center justify-center']) }} style="width: {{ $size }}px; height: {{ $size }}px;">
    <svg viewBox="0 0 100 100" class="absolute inset-0 h-full w-full animate-[spin_16s_linear_infinite] text-ink-700 dark:text-porcelain-200/80" aria-hidden="true">
        <defs>
            <path id="{{ $pathId }}" d="M 50,50 m -38,0 a 38,38 0 1,1 76,0 a 38,38 0 1,1 -76,0" fill="none" />
        </defs>
        <text font-size="7.4" letter-spacing="1.5" fill="currentColor" class="font-mono uppercase">
            <textPath href="#{{ $pathId }}" startOffset="0%">
                {{ $text }} &#8226; {{ $text }} &#8226;
            </textPath>
        </text>
    </svg>
    <span class="relative flex h-9 w-9 items-center justify-center rounded-full bg-brand-600 text-porcelain-50 shadow-lg dark:bg-brand-300 dark:text-ink-950">
        <x-heroicon-o-sparkles class="h-4 w-4" />
    </span>
</div>
