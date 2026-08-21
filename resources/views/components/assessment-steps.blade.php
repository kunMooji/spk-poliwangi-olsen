@props(['current' => 1])

@php
    $steps = [
        1 => ['label' => 'Biodata & Rapor', 'icon' => 'document-text'],
        2 => ['label' => 'Kuesioner RIASEC', 'icon' => 'clipboard-document-check'],
    ];
@endphp

<ol class="flex items-center gap-2 text-xs font-medium">
    @foreach ($steps as $number => $step)
        <li @class([
                'flex items-center gap-1.5',
                'text-brand-600 dark:text-brand-400' => $number === $current,
                'text-emerald-600 dark:text-emerald-400' => $number < $current,
                'text-ink-400 dark:text-porcelain-400' => $number > $current,
            ])>
            @if ($number < $current)
                <x-heroicon-s-check-circle class="h-4 w-4" />
            @else
                <x-dynamic-component :component="'heroicon-o-'.$step['icon']" class="h-4 w-4" />
            @endif
            <span>{{ $step['label'] }}</span>
        </li>
        @if ($number < count($steps))
            <li class="h-px w-4 shrink-0 bg-black/15 dark:bg-white/15" aria-hidden="true"></li>
        @endif
    @endforeach
</ol>
