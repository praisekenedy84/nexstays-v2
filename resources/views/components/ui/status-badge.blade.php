@props(['status'])

@php
    $classes = match ($status) {
        'confirmed' => 'bg-blue-100 text-blue-700',
        'checked_in' => 'bg-emerald-100 text-emerald-700',
        'checked_out' => 'bg-slate-100 text-slate-600',
        'cancelled', 'no_show' => 'bg-red-100 text-red-700',
        'inquiry' => 'bg-amber-100 text-amber-800',
        'vacant_clean' => 'bg-emerald-100 text-emerald-700',
        'vacant_dirty' => 'bg-amber-100 text-amber-800',
        'occupied' => 'bg-blue-100 text-blue-700',
        'out_of_order' => 'bg-red-100 text-red-700',
        default => 'bg-slate-100 text-slate-600',
    };
    $label = str_replace('_', ' ', ucwords((string) $status));
@endphp

<span {{ $attributes->merge(['class' => "inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {$classes}"]) }}>
    {{ $label }}
</span>
