@php
    use App\Support\MoneyFormatter;
    $placeholderImage = 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=400&h=300&fit=crop';
@endphp

<x-layouts.app active-nav="availability">
    <x-slot name="header">
        <x-layout.header :show-search="true" />
    </x-slot>

    <div class="grid gap-6 xl:grid-cols-[360px_1fr]">
        <aside class="space-y-6">
            <section class="card p-6">
                <h2 class="text-lg font-bold text-ink">Search availability</h2>
                <p class="mt-1 text-sm text-ink-muted">Matches <code class="text-xs">GET /api/v1/availability</code></p>

                <form class="mt-6 space-y-1" method="GET" action="{{ route('tenant.availability') }}">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-ink-muted">Check in</label>
                            <input type="date" name="check_in" value="{{ $checkIn }}" required class="input-field">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-ink-muted">Check out</label>
                            <input type="date" name="check_out" value="{{ $checkOut }}" required class="input-field">
                        </div>
                    </div>

                    <div class="divide-y divide-slate-100">
                        <x-ui.stepper label="Adults" hint="Max per room type applies" name="adults" :value="$adults" :min="1" :max="10" />
                        <x-ui.stepper label="Children" hint="Below 12 years" name="children" :value="$children" />
                    </div>

                    <div class="pt-4">
                        <label class="mb-1.5 block text-xs font-medium text-ink-muted">Room type filter</label>
                        <select name="room_type" class="input-field">
                            <option value="all">All room types</option>
                            @foreach ($roomTypes as $type)
                                <option value="{{ $type->id }}" @selected($roomTypeFilter === $type->id)>{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="btn-primary mt-6 w-full">Search</button>
                </form>
            </section>

            @if ($lastReservation)
                <section>
                    <div class="mb-3 px-1">
                        <h2 class="font-bold text-ink">Latest reservation</h2>
                    </div>
                    <a href="{{ route('tenant.reservations.index') }}" class="card flex items-center gap-4 p-3 transition hover:shadow-[var(--shadow-card-hover)]">
                        <span class="flex size-16 shrink-0 items-center justify-center rounded-xl bg-primary-soft text-lg font-bold text-primary">
                            {{ strtoupper(substr($lastReservation->roomType?->code ?? 'R', 0, 2)) }}
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold text-primary">{{ MoneyFormatter::perNight($lastReservation->daily_rate) }}</span>
                            <span class="block font-semibold text-ink">{{ $lastReservation->roomType?->name }}</span>
                            <span class="block text-xs text-ink-muted">
                                {{ $lastReservation->check_in_date->format('d M') }} – {{ $lastReservation->check_out_date->format('d M') }}
                            </span>
                        </span>
                        <x-icon name="chevron-right" class="size-4 shrink-0 text-ink-subtle" />
                    </a>
                </section>
            @endif
        </aside>

        <section>
            <div class="mb-5">
                <h2 class="text-2xl font-bold text-ink">Available room types</h2>
                <p class="text-sm text-ink-muted">{{ $availability->count() }} type(s) with inventory</p>
            </div>

            <div class="mb-6 flex flex-wrap gap-2">
                <a href="{{ route('tenant.availability', request()->only(['check_in', 'check_out', 'adults', 'children'])) }}" @class(['filter-pill', 'filter-pill-active' => ! $roomTypeFilter || $roomTypeFilter === 'all'])>All</a>
                @foreach ($roomTypes as $type)
                    <a
                        href="{{ route('tenant.availability', array_merge(request()->only(['check_in', 'check_out', 'adults', 'children']), ['room_type' => $type->id])) }}"
                        @class(['filter-pill', 'filter-pill-active' => $roomTypeFilter === $type->id])
                    >{{ $type->name }}</a>
                @endforeach
            </div>

            <div class="space-y-4">
                @forelse ($availability as $row)
                    <x-ui.room-card
                        :name="$row['name']"
                        :code="$row['code']"
                        :price="MoneyFormatter::perNight($row['base_rate'])"
                        :image="$placeholderImage"
                        :amenities="collect($row['amenities'])->map(fn ($a) => ucfirst(str_replace('_', ' ', $a)))->all()"
                        :hot="$row['hot']"
                        :available-count="$row['available_rooms']"
                    />
                @empty
                    <div class="card p-10 text-center">
                        <p class="text-ink-muted">No availability for these dates.</p>
                        @unless (request()->has(['check_in', 'check_out']))
                            <p class="mt-2 text-sm text-ink-subtle">Submit the search form to query availability.</p>
                        @endunless
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</x-layouts.app>
