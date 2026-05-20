<x-layouts.app active-nav="time-left" title="Checkout countdown" subtitle="Checked-in guests — time until departure">
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($stays as $stay)
            @php
                $checkout = $stay->check_out_date->copy()->endOfDay();
                $hoursLeft = max(0, now()->diffInHours($checkout, false));
                $urgent = $hoursLeft <= 6;
            @endphp
            <article @class(['card p-4 border-l-4', 'border-l-amber-500' => $urgent, 'border-l-primary' => ! $urgent])>
                <p class="text-2xl font-bold text-ink">Room {{ $stay->room?->room_number ?? '—' }}</p>
                <p class="text-sm text-ink-muted">{{ $stay->guest?->first_name }} {{ $stay->guest?->last_name }}</p>
                <p class="mt-2 font-mono text-xs text-primary">{{ $stay->booking_ref }}</p>
                <p class="mt-4 text-lg font-semibold @if($urgent) text-amber-700 @else text-ink @endif">
                    @if ($hoursLeft < 24)
                        {{ $hoursLeft }}h left
                    @else
                        {{ (int) ceil($hoursLeft / 24) }} day(s) left
                    @endif
                </p>
                <p class="text-xs text-ink-muted">Checkout {{ $stay->check_out_date->format('d M Y') }}</p>
                <a href="{{ route('tenant.reservations.show', $stay) }}" class="mt-3 inline-block text-sm text-primary hover:underline">View stay</a>
            </article>
        @empty
            <p class="col-span-full text-center text-ink-muted">No guests currently checked in.</p>
        @endforelse
    </div>
</x-layouts.app>
