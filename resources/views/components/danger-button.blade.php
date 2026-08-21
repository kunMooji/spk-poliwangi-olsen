<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition ease-brand-out duration-150 hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 active:scale-[0.98] disabled:opacity-50 dark:focus:ring-offset-gray-800']) }}>
    {{ $slot }}
</button>
