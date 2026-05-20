@props([
    'name',
    'price',
    'image',
    'amenities' => [],
    'hot' => false,
    'availableCount' => null,
    'code' => null,
])

<article {{ $attributes->merge(['class' => 'card flex flex-col gap-4 p-4 transition hover:shadow-[var(--shadow-card-hover)] sm:flex-row sm:items-center']) }}>
    <div class="relative shrink-0 overflow-hidden rounded-2xl sm:w-44">
        <img src="{{ $image }}" alt="" class="aspect-[4/3] w-full object-cover sm:aspect-auto sm:h-28">
        @if ($hot)
            <span class="absolute top-3 left-3 inline-flex items-center gap-1 rounded-full bg-hot px-2.5 py-1 text-xs font-semibold text-white">
                <x-icon name="flame" class="size-3.5" />
                Hot
            </span>
        @endif
    </div>

    <div class="min-w-0 flex-1">
        <p class="text-sm font-semibold text-primary">{{ $price }}</p>
        <h3 class="mt-0.5 text-lg font-bold text-ink">{{ $name }}@if ($code)<span class="ml-2 text-sm font-normal text-ink-subtle">{{ $code }}</span>@endif</h3>
        @if ($availableCount !== null)
            <p class="mt-1 text-xs text-ink-muted">{{ $availableCount }} room(s) available</p>
        @endif
        @if (count($amenities))
            <ul class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-ink-muted">
                @foreach ($amenities as $amenity)
                    <li class="flex items-center gap-1.5">
                        <span class="size-1.5 rounded-full bg-slate-300"></span>
                        {{ $amenity }}
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="flex shrink-0 flex-col items-stretch gap-2 sm:items-end">
        @can('manage-reservations')
            <span class="text-center text-xs text-ink-subtle sm:text-right">Create via API</span>
            <code class="rounded bg-slate-100 px-2 py-1 text-[10px] text-ink-muted">POST /reservations</code>
        @else
            <span class="text-center text-sm text-ink-muted sm:text-right">View only</span>
        @endcan
    </div>
</article>
