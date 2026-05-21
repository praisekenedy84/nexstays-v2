@php
    $paymentMode = app(\App\Domain\HBMS\Services\ReservationSettingsService::class)->all()['payment_mode'] ?? 'prepaid';
    $today = now()->startOfDay();
    $checkInDate = $reservation->check_in_date->copy()->startOfDay();
    $checkOutDate = $reservation->check_out_date->copy()->startOfDay();
    $stayedDays = $reservation->status === 'checked_out'
        ? $checkInDate->diffInDays($checkOutDate)
        : ($reservation->status === 'checked_in' ? $checkInDate->diffInDays($today) : 0);
    $stayedDays = max(0, $stayedDays);
    $stayedAmount = $stayedDays * (float) $reservation->daily_rate;
@endphp
<x-layouts.app active-nav="reservations" :title="$reservation->booking_ref" subtitle="Reservation detail">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('tenant.reservations.index') }}" class="text-sm text-primary hover:underline">← Reservations</a>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('tenant.reservations.ticket', $reservation) }}" class="btn-outline">Download ticket</a>
            @can('manage-reservations')
                @if (in_array($reservation->status, ['inquiry', 'confirmed'], true))
                    <a href="{{ route('tenant.reservations.edit', $reservation) }}" class="btn-outline">Edit</a>
                @endif
                @if (in_array($reservation->status, ['inquiry', 'confirmed'], true))
                    <form method="POST" action="{{ route('tenant.reservations.destroy', $reservation) }}" onsubmit="return confirm('Delete this reservation?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-outline text-red-600 hover:border-red-200 hover:bg-red-50">Delete reservation</button>
                    </form>
                @endif
            @endcan
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="card space-y-4 p-6 lg:col-span-2">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="font-mono text-lg font-bold text-primary">{{ $reservation->booking_ref }}</p>
                    <p class="text-ink-muted">
                        {{ $reservation->guest?->first_name }} {{ $reservation->guest?->last_name }}
                    </p>
                </div>
                <x-ui.status-badge :status="$reservation->status" />
            </div>

            <dl class="grid gap-4 sm:grid-cols-2 text-sm">
                <div>
                    <dt class="text-xs font-medium uppercase text-ink-muted">Stay</dt>
                    <dd class="mt-1 font-medium text-ink">
                        {{ $reservation->check_in_date->format('d M Y') }} – {{ $reservation->check_out_date->format('d M Y') }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-ink-muted">Room / type</dt>
                    <dd class="mt-1 font-medium text-ink">
                        @if ($reservation->room)
                            Room {{ $reservation->room->room_number }}
                        @else
                            {{ $reservation->roomType?->name }} (unassigned)
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-ink-muted">Guests</dt>
                    <dd class="mt-1 text-ink">{{ $reservation->adults }} adults, {{ $reservation->children }} children</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-ink-muted">Payment mode</dt>
                    <dd class="mt-1 font-medium text-ink">{{ strtoupper($paymentMode) }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-ink-muted">Daily rate</dt>
                    <dd class="mt-1 font-medium text-ink">@money($reservation->daily_rate)</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-ink-muted">Total stay price</dt>
                    <dd class="mt-1 font-medium text-ink">
                        {{ $reservation->total_nights }} night(s) · @money($reservation->total_amount)
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-ink-muted">Spent so far</dt>
                    <dd class="mt-1 font-medium text-ink">
                        {{ $stayedDays }} day(s) · @money($stayedAmount)
                    </dd>
                </div>
                @if ($reservation->special_requests)
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-medium uppercase text-ink-muted">Special requests</dt>
                        <dd class="mt-1 text-ink">{{ $reservation->special_requests }}</dd>
                    </div>
                @endif
                @if ($reservation->status === 'cancelled')
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-medium uppercase text-ink-muted">Cancellation settlement</dt>
                        <dd class="mt-1 text-ink">
                            Policy: {{ str_replace('_', ' ', (string) $reservation->cancellation_policy) }}
                            · Charged: @money($reservation->cancellation_charge_amount ?? 0)
                            · Refund: @money($reservation->cancellation_refund_amount ?? 0)
                        </dd>
                    </div>
                @endif
            </dl>
        </section>

        @if ($reservation->folio)
            <section class="card p-6">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-ink-muted">Folio</h2>
                <p class="text-2xl font-bold text-ink">
                    @if ($folioBalance)
                        @money($folioBalance->getAmount()->__toString())
                    @else
                        —
                    @endif
                </p>
                <p class="text-xs text-ink-subtle">Status: {{ $reservation->folio->status }}</p>
                @if ($reservation->folio->transactions->isNotEmpty())
                    <ul class="mt-4 max-h-48 space-y-2 overflow-y-auto text-xs">
                        @foreach ($reservation->folio->transactions->take(10) as $tx)
                            <li class="flex justify-between gap-2 border-b border-slate-100 pb-2">
                                <span class="text-ink-muted">{{ $tx->description }}</span>
                                <span class="font-medium">@money($tx->amount)</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endif
    </div>
</x-layouts.app>
