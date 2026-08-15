@props(['color' => 'gray', 'href' => null])

{{--
    Tombol aksi berbentuk ikon bulat — pengganti tautan/tombol teks "Ubah",
    "Hapus", dst. Render sebagai <a> bila diberi `href`, selain itu <button>
    (dipakai di dalam <form> untuk aksi hapus/POST).

    `title` wajib diisi pemanggil: dipakai sebagai tooltip native sekaligus
    label aksesibilitas, karena tidak ada teks terlihat lagi di tombolnya.

    Warna literal per cabang (bukan dirangkai dari variabel) supaya class-nya
    tetap terbaca oleh pemindai Tailwind — kelas yang dirangkai lewat string
    interpolation tidak akan ikut ter-generate.
--}}

@php
    $colorClasses = match ($color) {
        'brand' => 'text-brand-600 hover:bg-brand-50 dark:text-brand-400 dark:hover:bg-brand-900/30',
        'rose' => 'text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-900/30',
        'emerald' => 'text-emerald-600 hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-900/30',
        'amber' => 'text-amber-600 hover:bg-amber-50 dark:text-amber-400 dark:hover:bg-amber-900/30',
        default => 'text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700',
    };

    $base = 'inline-flex h-8 w-8 items-center justify-center rounded-lg transition '.$colorClasses;
    $title = $attributes->get('title');
@endphp

@if ($href)
    <a href="{{ $href }}"
       title="{{ $title }}"
       aria-label="{{ $title }}"
       {{ $attributes->except('title')->merge(['class' => $base]) }}>
        {{ $slot }}
    </a>
@else
    <button type="button"
            title="{{ $title }}"
            aria-label="{{ $title }}"
            {{ $attributes->except('title')->merge(['class' => $base]) }}>
        {{ $slot }}
    </button>
@endif
