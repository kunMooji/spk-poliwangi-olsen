<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center rounded-xl border border-black/10 bg-white px-4 py-2 text-sm font-semibold text-ink-700 shadow-sm transition ease-brand-out duration-150 hover:bg-black/[0.03] focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 active:scale-[0.98] disabled:opacity-50 dark:border-white/15 dark:bg-ink-900 dark:text-porcelain-200 dark:hover:bg-white/5 dark:focus:ring-offset-ink-900']) }}>
    {{ $slot }}
</button>
