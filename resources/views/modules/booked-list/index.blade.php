<x-layouts.app active-nav="booked-list" title="Booked list" subtitle="Arrivals, in-house guests, and upcoming reservations">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-4">
        <x-ui.search-bar :value="$search" placeholder="Booking ref or guest name…" />
    </div>

    <form method="GET" class="mb-4 flex flex-wrap gap-3">
        @foreach (request()->except(['from', 'to', 'page']) as $key => $val)
            <input type="hidden" name="{{ $key }}" value="{{ $val }}">
        @endforeach
        <input type="date" name="from" value="{{ $from->format('Y-m-d') }}" class="input-field w-auto" onchange="this.form.submit()">
        <input type="date" name="to" value="{{ $to->format('Y-m-d') }}" class="input-field w-auto" onchange="this.form.submit()">
    </form>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[800px] text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-ink-muted">
                    <tr>
                        <x-ui.sort-th column="check_in_date" label="Check-in" :sort="$sort" :dir="$dir" />
                        <x-ui.sort-th column="booking_ref" label="Booking ref" :sort="$sort" :dir="$dir" />
                        <th class="px-5 py-3 text-left">Guest</th>
                        <th class="px-5 py-3 text-left">Room / type</th>
                        <x-ui.sort-th column="check_out_date" label="Check-out" :sort="$sort" :dir="$dir" />
                        <x-ui.sort-th column="status" label="Status" :sort="$sort" :dir="$dir" />
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($bookings as $b)
                        <tr class="{{ $b->status === 'checked_in' ? 'bg-emerald-50/40' : 'hover:bg-slate-50/50' }}">
                            <td class="px-5 py-4 font-medium">{{ $b->check_in_date->format('d M Y') }}</td>
                            <td class="px-5 py-4 font-mono text-xs font-semibold text-primary">{{ $b->booking_ref }}</td>
                            <td class="px-5 py-4">{{ $b->guest?->first_name }} {{ $b->guest?->last_name }}</td>
                            <td class="px-5 py-4 text-ink-muted">
                                @if ($b->room)
                                    Room {{ $b->room->room_number }}
                                @else
                                    {{ $b->roomType?->name }}
                                    <span class="text-xs text-amber-600">(unassigned)</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-ink-muted">{{ $b->check_out_date->format('d M Y') }}</td>
                            <td class="px-5 py-4"><x-ui.status-badge :status="$b->status" /></td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    {{-- Check-in: confirmed only (room required) --}}
                                    @can('check-in-guests')
                                        @if ($b->status === 'confirmed')
                                            <form method="POST" action="{{ route('tenant.reservations.check-in', $b) }}"
                                                  onsubmit="return confirm('Check in {{ addslashes($b->guest?->first_name . ' ' . $b->guest?->last_name) }} ({{ $b->booking_ref }})?')">
                                                @csrf
                                                <button type="submit"
                                                    class="rounded-md bg-emerald-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-emerald-700 disabled:opacity-50"
                                                    @if (! $b->room_id) title="No room assigned — edit reservation first" disabled @endif
                                                >
                                                    Check in
                                                </button>
                                            </form>
                                        @endif
                                    @endcan

                                    {{-- Check-out: checked_in only --}}
                                    @can('check-out-guests')
                                        @if ($b->status === 'checked_in')
                                            <form method="POST" action="{{ route('tenant.reservations.check-out', $b) }}"
                                                  onsubmit="return confirm('Check out {{ addslashes($b->guest?->first_name . ' ' . $b->guest?->last_name) }} ({{ $b->booking_ref }})?\n\nMake sure folio balance is zero before proceeding.')">
                                                @csrf
                                                <button type="submit"
                                                    class="rounded-md bg-sky-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-sky-700">
                                                    Check out
                                                </button>
                                            </form>
                                        @endif
                                    @endcan

                                    {{-- No-show: confirmed/inquiry --}}
                                    @can('manage-reservations')
                                        @if (in_array($b->status, ['confirmed', 'inquiry']))
                                            <form method="POST" action="{{ route('tenant.reservations.no-show', $b) }}"
                                                  onsubmit="return confirm('Mark {{ addslashes($b->booking_ref) }} as no-show?')">
                                                @csrf
                                                <button type="submit"
                                                    class="rounded-md border border-amber-300 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 hover:bg-amber-100">
                                                    No show
                                                </button>
                                            </form>
                                        @endif
                                    @endcan

                                    {{-- View detail --}}
                                    <a href="{{ route('tenant.reservations.show', $b) }}"
                                       class="inline-flex items-center justify-center rounded-md p-1.5 text-primary hover:bg-slate-100"
                                       title="View reservation">
                                        <x-icon name="eye" class="size-4" />
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-ink-muted">No bookings in this date range.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($bookings->hasPages())
            <div class="border-t border-slate-100 px-5 py-3">{{ $bookings->links() }}</div>
        @endif
    </div>
</x-layouts.app>
