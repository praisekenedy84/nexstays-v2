@props([
    'label',
    'hint' => null,
    'name',
    'value' => 0,
    'min' => 0,
    'max' => 10,
])

<div
    data-stepper
    data-min="{{ $min }}"
    data-max="{{ $max }}"
    {{ $attributes->merge(['class' => 'flex items-center justify-between gap-4 py-3']) }}
>
    <div class="min-w-0">
        <p class="text-sm font-medium text-ink">{{ $label }}</p>
        @if ($hint)
            <p class="text-xs text-ink-subtle">{{ $hint }}</p>
        @endif
    </div>
    <div class="flex items-center gap-3">
        <button type="button" data-stepper-btn="-1" class="flex size-8 items-center justify-center rounded-lg bg-slate-100 text-ink-muted transition hover:bg-slate-200 hover:text-ink" aria-label="Decrease {{ $label }}">
            <x-icon name="minus" class="size-4" />
        </button>
        <input
            type="number"
            name="{{ $name }}"
            value="{{ $value }}"
            data-stepper-value
            class="w-8 border-0 bg-transparent text-center text-sm font-semibold text-ink focus:ring-0"
            readonly
        >
        <button type="button" data-stepper-btn="1" class="flex size-8 items-center justify-center rounded-lg bg-slate-100 text-ink-muted transition hover:bg-slate-200 hover:text-ink" aria-label="Increase {{ $label }}">
            <x-icon name="plus" class="size-4" />
        </button>
    </div>
</div>
