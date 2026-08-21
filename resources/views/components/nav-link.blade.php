@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-2 border-brand-500 dark:border-brand-300 text-sm font-medium leading-5 text-ink-900 dark:text-porcelain-50 focus:outline-none focus:border-brand-700 transition duration-150 ease-brand-out'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-ink-500 dark:text-porcelain-300 hover:text-ink-700 dark:hover:text-porcelain-100 hover:border-black/15 dark:hover:border-white/15 focus:outline-none focus:text-ink-700 dark:focus:text-porcelain-100 focus:border-black/15 dark:focus:border-white/15 transition duration-150 ease-brand-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
