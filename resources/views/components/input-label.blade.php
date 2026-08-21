@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-ink-700 dark:text-porcelain-200']) }}>
    {{ $value ?? $slot }}
</label>
