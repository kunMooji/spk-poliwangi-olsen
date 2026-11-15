@props([
    // Urutan kartu; warna gradien berputar mengikuti palet di bawah.
    'index' => 0,
    // Bila diisi, kartu menjadi tautan yang terbuka di tab baru.
    'href' => null,
])

@php
    // Palet kartu berwarna yang sama dengan "Program studi terpopuler" di
    // dasbor admin, supaya ruang admin dan calon mahasiswa terasa satu tema.
    $gradients = [
        'linear-gradient(135deg, #66c8ff 0%, #3188f5 52%, #195cdb 100%)',
        'linear-gradient(135deg, #b579ff 0%, #7c3aed 52%, #5b21b6 100%)',
        'linear-gradient(135deg, #ffd45a 0%, #fb9a2f 52%, #f26a18 100%)',
        'linear-gradient(135deg, #54dfb7 0%, #14b8a6 52%, #0f7490 100%)',
        'linear-gradient(135deg, #fb8baa 0%, #ec4899 52%, #a21caf 100%)',
    ];
    $style = 'background: '.$gradients[$index % count($gradients)];
    $classes = 'relative block overflow-hidden rounded-2xl text-white shadow-lg shadow-ink-950/10';
    if ($href) {
        $classes .= ' transition hover:-translate-y-0.5 hover:shadow-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2';
    }
@endphp

@if ($href)
    <a href="{{ $href }}" target="_blank" rel="noopener noreferrer" style="{{ $style }}" {{ $attributes->merge(['class' => $classes]) }}>
@else
    <div style="{{ $style }}" {{ $attributes->merge(['class' => $classes]) }}>
@endif
        {{-- Ornamen tipis: cincin di pojok kanan atas. --}}
        <span class="pointer-events-none absolute -right-5 -top-6 h-24 w-24 rounded-full border border-white/10" aria-hidden="true"></span>
        <div class="relative">{{ $slot }}</div>
@if ($href)
    </a>
@else
    </div>
@endif
