@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-brand-500 dark:border-brand-300 text-start text-base font-medium text-brand-700 dark:text-brand-300 bg-brand-50 dark:bg-brand-900/30 focus:outline-none focus:text-brand-800 dark:focus:text-brand-200 focus:bg-brand-100 dark:focus:bg-brand-900 focus:border-brand-700 dark:focus:border-brand-300 transition duration-150 ease-brand-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-ink-600 dark:text-porcelain-300 hover:text-ink-800 dark:hover:text-porcelain-100 hover:bg-black/5 dark:hover:bg-white/5 hover:border-black/15 dark:hover:border-white/15 focus:outline-none focus:text-ink-800 dark:focus:text-porcelain-100 focus:bg-black/5 dark:focus:bg-white/5 focus:border-black/15 dark:focus:border-white/15 transition duration-150 ease-brand-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
