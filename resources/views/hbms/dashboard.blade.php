<x-layouts.app active-nav="dashboard" title="Overview" subtitle="Front office snapshot for {{ $tenantLabel ?? tenant('id') }}">
    <div class="grid gap-6 xl:grid-cols-[1fr_340px]">
        <div class="space-y-6">
            <div class="grid gap-4 sm:grid-cols-3">
                <x-ui.kpi-card label="Today Arrival" :value="(string) $todayArrivals" accent="orange">
                    <x-slot:icon>
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0110.5 3h3a2.25 2.25 0 012.25 2.25v3.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </x-slot:icon>
                </x-ui.kpi-card>
                <x-ui.kpi-card label="Today Departure" :value="(string) $todayDepartures" accent="blue">
                    <x-slot:icon>
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v3.75M3 12a9 9 0 1018 0 9 9 0 00-18 0z" /></svg>
                    </x-slot:icon>
                </x-ui.kpi-card>
                <x-ui.kpi-card label="In-house & confirmed" :value="(string) $totalBooked" period="Active stays" accent="sky">
                    <x-slot:icon>
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 4.5h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 011.875 1.875v9.75a1.875 1.875 0 01-1.875 1.875H5.625A1.875 1.875 0 013.75 16.125v-9.75A1.875 1.875 0 015.625 4.5z" /></svg>
                    </x-slot:icon>
                </x-ui.kpi-card>
            </div>

            <section class="card p-6">
                <h2 class="text-lg font-bold text-ink">Quick actions</h2>
                <p class="mt-1 text-sm text-ink-muted">HBMS modules available on this property</p>
                <div class="mt-4 flex flex-wrap gap-3">
                    @can('view-availability')
                        <a href="{{ route('tenant.availability') }}" class="btn-primary">Search availability</a>
                    @endcan
                    @can('view-reservations')
                        <a href="{{ route('tenant.reservations.index') }}" class="btn-outline">Reservations</a>
                    @endcan
                    @can('view-rooms')
                        <a href="{{ route('tenant.rooms.index') }}" class="btn-outline">Room board</a>
                    @endcan
                    @can('view-guests')
                        <a href="{{ route('tenant.guests.index') }}" class="btn-outline">Guests</a>
                    @endcan
                    @can('view-orders')
                        <a href="{{ route('tenant.restaurant.index') }}" class="btn-outline">Restaurant</a>
                        <a href="{{ route('tenant.bar.index') }}" class="btn-outline">Bar</a>
                    @endcan
                    @can('view-till')
                        <a href="{{ route('tenant.till.index') }}" class="btn-outline">Till</a>
                    @endcan
                    @can('view-inventory')
                        <a href="{{ route('tenant.stock-items.index') }}" class="btn-outline">Inventory</a>
                    @endcan
                </div>
                <p class="mt-6 text-xs text-ink-subtle">
                    REST API: <code class="rounded bg-slate-100 px-1">{{ url('/api/v1') }}</code> — use Sanctum tokens for integrations.
                </p>
            </section>
        </div>

        <aside class="space-y-6">
            @if ($lastReservation)
                <section class="card p-5">
                    <h2 class="font-bold text-ink">Latest reservation</h2>
                    <p class="mt-2 text-sm font-semibold text-primary">{{ $lastReservation->booking_ref }}</p>
                    <p class="text-ink">{{ $lastReservation->guest?->first_name }} {{ $lastReservation->guest?->last_name }}</p>
                    <p class="text-sm text-ink-muted">{{ $lastReservation->roomType?->name }}</p>
                    <p class="mt-1 text-xs text-ink-subtle">
                        {{ $lastReservation->check_in_date->format('d M') }} – {{ $lastReservation->check_out_date->format('d M') }}
                    </p>
                    <x-ui.status-badge :status="$lastReservation->status" class="mt-3" />
                </section>
            @endif

            <section class="card p-5">
                <div class="flex items-center justify-between">
                    <h2 class="font-bold text-ink">Upcoming arrival</h2>
                    @can('view-reservations')
                        <a href="{{ route('tenant.reservations.index') }}" class="text-xs font-medium text-primary hover:underline">View all</a>
                    @endcan
                </div>
                <ul class="mt-4 divide-y divide-slate-100">
                    @forelse ($upcomingArrivals as $reservation)
                        <li>
                            <div class="flex items-center gap-3 py-4">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary-soft text-sm font-bold text-primary">
                                    {{ strtoupper(substr($reservation->guest?->first_name ?? '?', 0, 1)) }}
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-semibold text-ink">
                                        {{ $reservation->guest?->first_name }} {{ $reservation->guest?->last_name }}
                                    </span>
                                    <span class="block text-xs text-ink-muted">
                                        {{ $reservation->room?->room_number ? 'Room '.$reservation->room->room_number.' • ' : '' }}
                                        {{ $reservation->check_in_date->format('d M') }} – {{ $reservation->check_out_date->format('d M') }}
                                    </span>
                                </span>
                                <x-ui.status-badge :status="$reservation->status" />
                            </div>
                        </li>
                    @empty
                        <li class="py-6 text-center text-sm text-ink-muted">No upcoming arrivals</li>
                    @endforelse
                </ul>
            </section>
        </aside>
    </div>
</x-layouts.app>
