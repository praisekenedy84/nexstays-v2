<x-layouts.app active-nav="debts" title="Outstanding debts" subtitle="Open folios with unpaid balance">
    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-ink-muted">
                <tr>
                    <th class="px-5 py-3">Folio</th>
                    <th class="px-5 py-3">Guest / booking</th>
                    <th class="px-5 py-3">Room</th>
                    <th class="px-5 py-3 text-right">Balance due</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($debts as $row)
                    <tr>
                        <td class="px-5 py-4 font-mono text-xs">{{ $row['folio']->folio_number }}</td>
                        <td class="px-5 py-4">
                            @if ($row['reservation'])
                                <a href="{{ route('tenant.reservations.show', $row['reservation']) }}" class="text-primary hover:underline">{{ $row['reservation']->booking_ref }}</a>
                                — {{ $row['reservation']->guest?->first_name }} {{ $row['reservation']->guest?->last_name }}
                            @else — @endif
                        </td>
                        <td class="px-5 py-4 text-ink-muted">{{ $row['reservation']?->room?->room_number ?? '—' }}</td>
                        <td class="px-5 py-4 text-right font-bold text-red-600">@money($row['balance']->getAmount()->__toString())</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-12 text-center text-ink-muted">No outstanding balances.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.app>
