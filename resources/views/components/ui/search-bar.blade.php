@props([
    'value' => '',
    'placeholder' => 'Search…',
    /** When true, render inputs only (parent already provides the GET form). */
    'embedded' => false,
])

@if ($embedded)
    <div {{ $attributes->merge(['class' => 'flex w-full max-w-md gap-2 sm:max-w-xs']) }}>
        <div class="relative min-w-0 flex-1">
            <svg class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-ink-muted"
                 fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
            </svg>
            <input type="search" name="search" value="{{ $value }}"
                   placeholder="{{ $placeholder }}"
                   class="input-field w-full pl-9">
        </div>
        <button type="submit" class="btn-primary shrink-0 px-4">Search</button>
    </div>
@else
    <form method="GET" {{ $attributes->merge(['class' => 'flex w-full max-w-md gap-2 sm:max-w-xs']) }}>
        {{-- Preserve other active filters; reset page on new search --}}
        @foreach (request()->except(['search', 'page']) as $key => $val)
            @if (is_scalar($val))
                <input type="hidden" name="{{ $key }}" value="{{ $val }}">
            @endif
        @endforeach
        <div class="relative min-w-0 flex-1">
            <svg class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-ink-muted"
                 fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
            </svg>
            <input type="search" name="search" value="{{ $value }}"
                   placeholder="{{ $placeholder }}"
                   class="input-field w-full pl-9">
        </div>
        <button type="submit" class="btn-primary shrink-0 px-4">Search</button>
    </form>
@endif
