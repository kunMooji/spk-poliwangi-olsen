@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-black/10 dark:border-white/15 dark:bg-ink-900 dark:text-porcelain-100 focus:border-brand-500 dark:focus:border-brand-300 focus:ring-brand-500 dark:focus:ring-brand-300 rounded-lg shadow-sm transition ease-brand-out duration-150']) }}>
