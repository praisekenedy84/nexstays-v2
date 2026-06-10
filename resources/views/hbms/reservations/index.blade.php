<x-layouts.app active-nav="reservations" title="Reservations" subtitle="Booking lifecycle — inquiry → confirmed → checked in → checked out">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-4">
        <x-ui.search-bar :value="$search" placeholder="Booking ref or guest name…" />
        @can('manage-reservations')
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('tenant.reservations.settings.edit') }}" class="btn-outline">Reservation settings</a>
                <a href="{{ route('tenant.reservations.create') }}" class="btn-primary">New reservation</a>
            </div>
        @endcan
    </div>

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

    <form method="GET" class="mb-6 flex flex-wrap items-end gap-3">
        @foreach (request()->except(['date_field', 'date_from', 'date_to', 'page']) as $key => $val)
            <input type="hidden" name="{{ $key }}" value="{{ $val }}">
        @endforeach
        <div>
            <label class="mb-1 block text-xs font-medium text-ink-muted">Date filter</label>
            <select name="date_field" class="input-field w-auto" onchange="this.form.submit()">
                <option value="check_in_date" @selected($dateField === 'check_in_date')>Reservation check-in date</option>
                <option value="checked_in_at" @selected($dateField === 'checked_in_at')>Checked-in date</option>
                <option value="check_out_date" @selected($dateField === 'check_out_date')>Reservation check-out date</option>
                <option value="checked_out_at" @selected($dateField === 'checked_out_at')>Checked-out date</option>
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-ink-muted">From</label>
            <input type="date" name="date_from" value="{{ $dateFrom }}" class="input-field w-auto" onchange="this.form.submit()">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-ink-muted">To</label>
            <input type="date" name="date_to" value="{{ $dateTo }}" class="input-field w-auto" onchange="this.form.submit()">
        </div>
        @if ($dateFrom || $dateTo)
            <a href="{{ route('tenant.reservations.index', array_merge(request()->except(['date_field', 'date_from', 'date_to', 'page']))) }}" class="btn-outline">Clear dates</a>
        @endif
    </form>

    <div class="card overflow-hidden">
        @can('manage-reservations')
            <form id="bulk-reservation-action-form" method="POST" action="{{ route('tenant.reservations.bulk-action') }}" class="flex flex-wrap items-center gap-3 border-b border-slate-100 bg-slate-50/60 px-5 py-3">
                @csrf
                <span class="text-xs font-medium uppercase tracking-wide text-ink-muted">Bulk actions</span>
                <select name="action" required class="input-field max-w-[220px]">
                    <option value="">Choose action</option>
                    <option value="cancel">Cancel selected</option>
                    <option value="delete">Delete selected permanently</option>
                </select>
                <button type="submit" class="btn-primary">Apply</button>
            </form>
        @endcan
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="border-b border-slate-100 bg-slate-50/80 text-xs font-medium uppercase tracking-wide text-ink-muted">
                    <tr>
                        @can('manage-reservations')
                            <th class="px-5 py-3">
                                <input id="select-all-reservations" type="checkbox" class="rounded border-slate-300 text-primary">
                            </th>
                        @endcan
                        <x-ui.sort-th column="booking_ref" label="Booking ref" :sort="$sort" :dir="$dir" />
                        <th class="px-5 py-3 text-left">Guest</th>
                        <th class="px-5 py-3 text-left">Booked by</th>
                        <th class="px-5 py-3 text-left">Room / type</th>
                        <x-ui.sort-th column="check_in_date" label="Check-in" :sort="$sort" :dir="$dir" />
                        <x-ui.sort-th column="check_out_date" label="Check-out" :sort="$sort" :dir="$dir" />
                        <th class="min-w-[9.5rem] px-5 py-3 text-left">Status</th>
                        <th class="px-5 py-3 text-right">Total</th>
                        <th class="px-5 py-3 text-right"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($reservations as $reservation)
                        <tr class="hover:bg-slate-50/50">
                            @can('manage-reservations')
                                <td class="px-5 py-4">
                                    <input
                                        type="checkbox"
                                        name="reservation_ids[]"
                                        value="{{ $reservation->id }}"
                                        form="bulk-reservation-action-form"
                                        class="reservation-row-checkbox rounded border-slate-300 text-primary"
                                    >
                                </td>
                            @endcan
                            <td class="px-5 py-4">
                                <p class="font-mono text-xs font-semibold text-primary">{{ $reservation->booking_ref }}</p>
                                <p class="mt-0.5 text-[11px] text-ink-subtle" title="{{ $reservation->created_at->format('d M Y H:i') }}">
                                    Booked {{ $reservation->created_at->diffForHumans() }}
                                </p>
                            </td>
                            <td class="px-5 py-4">
                                {{ $reservation->guest?->first_name }} {{ $reservation->guest?->last_name }}
                            </td>
                            <td class="px-5 py-4 text-ink-muted">
                                {{ $reservation->creator?->name ?? '—' }}
                            </td>
                            <td class="px-5 py-4 text-ink-muted">
                                @if ($reservation->room)
                                    Room {{ $reservation->room->room_number }}
                                @else
                                    {{ $reservation->roomType?->name }}
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <p class="text-ink-muted">{{ $reservation->check_in_date->format('d M Y') }}</p>
                                @if ($reservation->checked_in_at)
                                    <p class="mt-0.5 text-[11px] text-emerald-600" title="Checked in at {{ $reservation->checked_in_at->format('d M Y H:i') }}">
                                        ↳ {{ $reservation->checked_in_at->format('d M Y H:i') }}
                                    </p>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <p class="text-ink-muted">{{ $reservation->check_out_date->format('d M Y') }}</p>
                                @if ($reservation->checked_out_at)
                                    <p class="mt-0.5 text-[11px] text-sky-600" title="Checked out at {{ $reservation->checked_out_at->format('d M Y H:i') }}">
                                        ↳ {{ $reservation->checked_out_at->format('d M Y H:i') }}
                                    </p>
                                @endif
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                <x-ui.status-badge :status="$reservation->status" class="whitespace-nowrap px-3 py-1" />
                            </td>
                            <td class="px-5 py-4 text-right">
                                <p class="font-medium">@money($reservation->total_amount)</p>
                                <p class="text-xs text-ink-muted">
                                    {{ $reservation->total_nights }} night(s) · @money($reservation->daily_rate) / night
                                </p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a
                                    href="{{ route('tenant.reservations.show', $reservation) }}"
                                    class="inline-flex items-center justify-center rounded-md p-1.5 text-primary hover:bg-slate-100"
                                    title="View reservation"
                                    aria-label="View reservation"
                                >
                                    <x-icon name="eye" class="size-4" />
                                    <span class="sr-only">View</span>
                                </a>
                                <a
                                    href="{{ route('tenant.reservations.ticket', $reservation) }}"
                                    class="ml-1 inline-flex items-center justify-center rounded-md p-1.5 text-primary hover:bg-slate-100"
                                    title="Download reservation ticket"
                                    aria-label="Download reservation ticket"
                                >
                                    <x-icon name="document" class="size-4" />
                                    <span class="sr-only">Download ticket</span>
                                </a>
                                @can('check-in-guests')
                                    @if ($reservation->status === 'confirmed')
                                        <form method="POST" action="{{ route('tenant.reservations.check-in', $reservation) }}" class="ml-1 inline"
                                              onsubmit="return confirm('Check in {{ addslashes($reservation->booking_ref) }}?')">
                                            @csrf
                                            <button type="submit"
                                                class="inline-flex items-center rounded-md bg-emerald-600 px-2 py-1 text-xs font-semibold text-white hover:bg-emerald-700"
                                                title="Check in guest"
                                                @if (! $reservation->room_id) disabled title="No room assigned" @endif
                                            >Check in</button>
                                        </form>
                                    @endif
                                @endcan
                                @can('check-out-guests')
                                    @if ($reservation->status === 'checked_in')
                                        <form method="POST" action="{{ route('tenant.reservations.check-out', $reservation) }}" class="ml-1 inline"
                                              onsubmit="return confirm('Check out {{ addslashes($reservation->booking_ref) }}?\n\nEnsure folio balance is zero.')">
                                            @csrf
                                            <button type="submit"
                                                class="inline-flex items-center rounded-md bg-sky-600 px-2 py-1 text-xs font-semibold text-white hover:bg-sky-700"
                                                title="Check out guest"
                                            >Check out</button>
                                        </form>
                                    @endif
                                @endcan
                                @can('manage-reservations')
                                    @if (in_array($reservation->status, ['inquiry', 'confirmed'], true))
                                        <a
                                            href="{{ route('tenant.reservations.edit', $reservation) }}"
                                            class="ml-1 inline-flex items-center justify-center rounded-md p-1.5 text-primary hover:bg-slate-100"
                                            title="Edit reservation"
                                            aria-label="Edit reservation"
                                        >
                                            <x-icon name="pencil" class="size-4" />
                                            <span class="sr-only">Edit</span>
                                        </a>
                                        <form method="POST" action="{{ route('tenant.reservations.no-show', $reservation) }}" class="ml-1 inline"
                                              onsubmit="return confirm('Mark {{ addslashes($reservation->booking_ref) }} as no-show?')">
                                            @csrf
                                            <button type="submit"
                                                class="inline-flex items-center justify-center rounded-md p-1.5 text-amber-500 hover:bg-amber-50"
                                                title="Mark as no-show">
                                                <x-icon name="x-circle" class="size-4" />
                                                <span class="sr-only">No show</span>
                                            </button>
                                        </form>
                                    @endif
                                    @if (
                                        auth()->user()?->can('manage-reservations')
                                        && (
                                            in_array($reservation->status, ['inquiry', 'confirmed', 'cancelled', 'no_show'], true)
                                            || auth()->user()?->can('force-delete-reservations')
                                        )
                                    )
                                    @php
                                        $deleteConfirmMessage = in_array($reservation->status, ['checked_in', 'checked_out'], true)
                                            ? sprintf(
                                                'Permanently delete %s? This reservation is %s and may include folio history. This cannot be undone.',
                                                $reservation->booking_ref,
                                                str_replace('_', ' ', $reservation->status)
                                            )
                                            : 'Permanently delete this reservation? This cannot be undone.';
                                    @endphp
                                    <form method="POST" action="{{ route('tenant.reservations.destroy', $reservation) }}" class="ml-1 inline"
                                          onsubmit="return confirm(@js($deleteConfirmMessage))">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="inline-flex items-center justify-center rounded-md p-1.5 text-red-600 hover:bg-red-50"
                                            title="Delete reservation permanently"
                                            aria-label="Delete reservation permanently"
                                        >
                                            <x-icon name="trash" class="size-4" />
                                            <span class="sr-only">Delete</span>
                                        </button>
                                    </form>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()?->can('manage-reservations') ? 10 : 9 }}" class="px-5 py-12 text-center text-ink-muted">No reservations found.</td>
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

    @can('manage-reservations')
        <script>
            (() => {
                const selectAll = document.getElementById('select-all-reservations');
                const checkboxes = Array.from(document.querySelectorAll('.reservation-row-checkbox'));

                if (!selectAll || checkboxes.length === 0) {
                    return;
                }

                const syncState = () => {
                    const checkedCount = checkboxes.filter((checkbox) => checkbox.checked).length;
                    selectAll.checked = checkedCount > 0 && checkedCount === checkboxes.length;
                    selectAll.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;
                };

                selectAll.addEventListener('change', () => {
                    checkboxes.forEach((checkbox) => {
                        checkbox.checked = selectAll.checked;
                    });
                    syncState();
                });

                checkboxes.forEach((checkbox) => checkbox.addEventListener('change', syncState));
            })();
        </script>
    @endcan
</x-layouts.app>
