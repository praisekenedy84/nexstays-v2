@props(['value' => '', 'placeholder' => 'Search…'])

<form method="GET" class="flex max-w-xs gap-2">
    {{-- Preserve sort, direction, and any other active filters; reset page on new search --}}
    @foreach (request()->except(['search', 'page']) as $key => $val)
        <input type="hidden" name="{{ $key }}" value="{{ $val }}">
    @endforeach
    <div class="relative flex-1">
        <svg class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-ink-muted"
             fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
        </svg>
        <input type="search" name="search" value="{{ $value }}"
               placeholder="{{ $placeholder }}"
               class="input-field w-full pl-9">
    </div>
    <button type="submit" class="btn-primary px-4">Search</button>
</form>
