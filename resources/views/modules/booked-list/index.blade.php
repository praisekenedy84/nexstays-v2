<x-layouts.app active-nav="booked-list" title="Booked list" subtitle="Upcoming confirmed & inquiry reservations">
    <form method="GET" class="mb-4 flex flex-wrap gap-3">
        <input type="date" name="from" value="{{ $from->format('Y-m-d') }}" class="input-field w-auto">
        <input type="date" name="to" value="{{ $to->format('Y-m-d') }}" class="input-field w-auto">
        <button type="submit" class="btn-primary">Filter</button>
    </form>
    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-ink-muted">
                <tr><th class="px-5 py-3">Check-in</th><th class="px-5 py-3">Guest</th><th class="px-5 py-3">Room / type</th><th class="px-5 py-3">Status</th><th></th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($bookings as $b)
                    <tr>
                        <td class="px-5 py-4 font-medium">{{ $b->check_in_date->format('d M Y') }}</td>
                        <td class="px-5 py-4">{{ $b->guest?->first_name }} {{ $b->guest?->last_name }}</td>
                        <td class="px-5 py-4 text-ink-muted">{{ $b->room?->room_number ?? $b->roomType?->name }}</td>
                        <td class="px-5 py-4"><x-ui.status-badge :status="$b->status" /></td>
                        <td class="px-5 py-4 text-right"><a href="{{ route('tenant.reservations.show', $b) }}" class="text-primary hover:underline">View</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-layouts.app>
