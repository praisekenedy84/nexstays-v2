@props(['column', 'label', 'sort' => '', 'dir' => 'asc'])

@php
    $active  = $sort === $column;
    $newDir  = ($active && $dir === 'asc') ? 'desc' : 'asc';
    $url     = request()->fullUrlWithQuery(['sort' => $column, 'direction' => $newDir, 'page' => null]);
@endphp

<th {{ $attributes->class(['px-5 py-3 text-left']) }}>
    <a href="{{ $url }}"
       class="group inline-flex items-center gap-1 text-xs font-medium uppercase tracking-wide text-ink-muted transition hover:text-ink">
        {{ $label }}
        <span @class(['transition', 'text-primary' => $active, 'opacity-30 group-hover:opacity-70' => ! $active])>
            @if ($active && $dir === 'asc')
                <svg class="size-3" viewBox="0 0 12 12" fill="currentColor"><path d="M6 2l4 5H2z"/></svg>
            @elseif ($active && $dir === 'desc')
                <svg class="size-3" viewBox="0 0 12 12" fill="currentColor"><path d="M6 10 2 5h8z"/></svg>
            @else
                <svg class="size-3" viewBox="0 0 12 12" fill="currentColor"><path d="M6 1l3 4H3zm0 10L3 7h6z"/></svg>
            @endif
        </span>
    </a>
</th>
