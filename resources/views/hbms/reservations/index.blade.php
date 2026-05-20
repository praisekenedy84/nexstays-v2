<x-layouts.app active-nav="reservations" title="Reservations" subtitle="Booking lifecycle — inquiry → confirmed → checked in → checked out">
    @can('manage-reservations')
        <div class="mb-4 flex justify-end">
            <a href="{{ route('tenant.reservations.create') }}" class="btn-primary">New reservation</a>
        </div>
    @endcan

    <div class="mb-6 flex flex-wrap gap-2">
        @php
            $statuses = ['all' => 'All', 'inquiry' => 'Inquiry', 'confirmed' => 'Confirmed', 'checked_in' => 'Checked in', 'checked_out' => 'Checked out', 'cancelled' => 'Cancelled', 'no_show' => 'No show'];
        @endphp
        @foreach ($statuses as $key => $label)
            <a
                href="{{ route('tenant.reservations.index', array_merge(request()->except('page'), ['status' => $key === 'all' ? null : $key])) }}"
                @class(['filter-pill', 'filter-pill-active' => ($status ?? 'all') === $key || ($key === 'all' && ! $status)])
            >
                {{ $label }}
                @if ($key !== 'all' && isset($statusCounts[$key]))
                    <span class="ml-1 text-ink-subtle">({{ $statusCounts[$key] }})</span>
                @endif
            </a>
        @endforeach
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="border-b border-slate-100 bg-slate-50/80 text-xs font-medium uppercase tracking-wide text-ink-muted">
                    <tr>
                        <th class="px-5 py-3">Booking ref</th>
                        <th class="px-5 py-3">Guest</th>
                        <th class="px-5 py-3">Room / type</th>
                        <th class="px-5 py-3">Stay</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Rate</th>
                        <th class="px-5 py-3 text-right"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($reservations as $reservation)
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-5 py-4 font-mono text-xs font-semibold text-primary">{{ $reservation->booking_ref }}</td>
                            <td class="px-5 py-4">
                                {{ $reservation->guest?->first_name }} {{ $reservation->guest?->last_name }}
                            </td>
                            <td class="px-5 py-4 text-ink-muted">
                                @if ($reservation->room)
                                    Room {{ $reservation->room->room_number }}
                                @else
                                    {{ $reservation->roomType?->name }}
                                @endif
                            </td>
                            <td class="px-5 py-4 text-ink-muted">
                                {{ $reservation->check_in_date->format('d M Y') }} – {{ $reservation->check_out_date->format('d M Y') }}
                            </td>
                            <td class="px-5 py-4">
                                <x-ui.status-badge :status="$reservation->status" />
                            </td>
                            <td class="px-5 py-4 text-right font-medium">@money($reservation->daily_rate)</td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('tenant.reservations.show', $reservation) }}" class="text-primary hover:underline">View</a>
                                @can('manage-reservations')
                                    @if (in_array($reservation->status, ['inquiry', 'confirmed'], true))
                                        <a href="{{ route('tenant.reservations.edit', $reservation) }}" class="ml-2 text-primary hover:underline">Edit</a>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-ink-muted">No reservations found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($reservations->hasPages())
            <div class="border-t border-slate-100 px-5 py-3">
                {{ $reservations->links() }}
            </div>
        @endif
    </div>

    <p class="mt-4 text-xs text-ink-subtle">
        API: <code class="rounded bg-slate-100 px-1">GET /api/v1/reservations</code>
        @can('manage-reservations') · <code class="rounded bg-slate-100 px-1">POST /api/v1/reservations</code>@endcan
        @can('check-in-guests') · <code class="rounded bg-slate-100 px-1">POST …/check-in</code>@endcan
    </p>
</x-layouts.app>
