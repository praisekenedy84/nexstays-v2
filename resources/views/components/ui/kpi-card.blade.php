@props([
    'label',
    'value',
    'trend' => null,
    'period' => 'Last 7 days',
    'accent' => 'orange',
])

@php
    $accentClasses = match ($accent) {
        'blue' => 'bg-blue-100 text-blue-600',
        'sky' => 'bg-sky-100 text-sky-600',
        default => 'bg-orange-100 text-orange-600',
    };
@endphp

<article {{ $attributes->merge(['class' => 'card p-4 sm:p-5']) }}>
    <div class="flex items-start gap-3 sm:gap-4">
        @isset($icon)
            <div @class(['flex size-10 shrink-0 items-center justify-center rounded-full sm:size-12', $accentClasses])>
                {{ $icon }}
            </div>
        @endisset
        <div class="min-w-0 flex-1">
            <p class="text-sm text-ink-muted">{{ $label }}</p>
            <p class="mt-1 break-words text-2xl font-bold tracking-tight text-ink sm:text-3xl">{{ $value }}</p>
            @if ($trend)
                <p class="mt-2 flex items-center gap-1 text-xs">
                    <span class="font-medium text-success">{{ $trend }}</span>
                    <span class="text-ink-subtle">{{ $period }}</span>
                </p>
            @endif
        </div>
    </div>
</article>
