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

            @can('check-in-guests')
                @if ($reservation->status === 'confirmed')
                    <form method="POST" action="{{ route('tenant.reservations.check-in', $reservation) }}"
                          onsubmit="return confirm('Check in guest for {{ $reservation->booking_ref }}?')">
                        @csrf
                        <button type="submit"
                            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
                            @if (! $reservation->room_id) title="No room assigned — edit reservation first" disabled @endif
                        >
                            Check in guest
                        </button>
                    </form>
                @endif
            @endcan

            @can('check-out-guests')
                @if ($reservation->status === 'checked_in')
                    <form method="POST" action="{{ route('tenant.reservations.check-out', $reservation) }}"
                          onsubmit="return confirm('Check out guest?\n\nMake sure all charges are settled (folio balance = 0) before proceeding.')">
                        @csrf
                        <button type="submit"
                            class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">
                            Check out guest
                        </button>
                    </form>
                @endif
            @endcan

            @can('manage-reservations')
                @if (in_array($reservation->status, ['inquiry', 'confirmed'], true))
                    <a href="{{ route('tenant.reservations.edit', $reservation) }}" class="btn-outline">Edit</a>
                    <form method="POST" action="{{ route('tenant.reservations.no-show', $reservation) }}"
                          onsubmit="return confirm('Mark {{ $reservation->booking_ref }} as no-show?')">
                        @csrf
                        <button type="submit" class="btn-outline text-amber-600 hover:border-amber-200 hover:bg-amber-50">No show</button>
                    </form>
                    <form method="POST" action="{{ route('tenant.reservations.destroy', $reservation) }}" onsubmit="return confirm('Delete this reservation?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-outline text-red-600 hover:border-red-200 hover:bg-red-50">Delete</button>
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

            {{-- Timeline --}}
            <div class="border-t border-slate-100 pt-4">
                <p class="mb-3 text-xs font-medium uppercase tracking-wide text-ink-muted">Timeline</p>
                <ol class="space-y-2">
                    <li class="flex items-start gap-3 text-sm">
                        <span class="mt-1 flex size-5 shrink-0 items-center justify-center rounded-full bg-slate-100 text-[10px] font-bold text-slate-500">1</span>
                        <div>
                            <p class="font-medium text-ink">Reservation created</p>
                            <p class="text-xs text-ink-muted">{{ $reservation->created_at->format('d M Y, H:i') }} &middot; {{ $reservation->created_at->diffForHumans() }}</p>
                        </div>
                    </li>
                    @if ($reservation->checked_in_at)
                        <li class="flex items-start gap-3 text-sm">
                            <span class="mt-1 flex size-5 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-[10px] font-bold text-emerald-600">2</span>
                            <div>
                                <p class="font-medium text-ink">Checked in</p>
                                <p class="text-xs text-ink-muted">{{ $reservation->checked_in_at->format('d M Y, H:i') }} &middot; {{ $reservation->checked_in_at->diffForHumans() }}</p>
                            </div>
                        </li>
                    @elseif (in_array($reservation->status, ['confirmed', 'inquiry'], true))
                        <li class="flex items-start gap-3 text-sm opacity-40">
                            <span class="mt-1 flex size-5 shrink-0 items-center justify-center rounded-full bg-slate-100 text-[10px] font-bold text-slate-400">2</span>
                            <div>
                                <p class="font-medium text-ink-muted">Check-in pending</p>
                                <p class="text-xs text-ink-subtle">Expected {{ $reservation->check_in_date->format('d M Y') }}</p>
                            </div>
                        </li>
                    @endif
                    @if ($reservation->checked_out_at)
                        <li class="flex items-start gap-3 text-sm">
                            <span class="mt-1 flex size-5 shrink-0 items-center justify-center rounded-full bg-sky-100 text-[10px] font-bold text-sky-600">3</span>
                            <div>
                                <p class="font-medium text-ink">Checked out</p>
                                <p class="text-xs text-ink-muted">{{ $reservation->checked_out_at->format('d M Y, H:i') }} &middot; {{ $reservation->checked_out_at->diffForHumans() }}</p>
                            </div>
                        </li>
                    @endif
                    @if ($reservation->cancelled_at)
                        <li class="flex items-start gap-3 text-sm">
                            <span class="mt-1 flex size-5 shrink-0 items-center justify-center rounded-full bg-rose-100 text-[10px] font-bold text-rose-500">✕</span>
                            <div>
                                <p class="font-medium text-ink">Cancelled</p>
                                <p class="text-xs text-ink-muted">{{ $reservation->cancelled_at->format('d M Y, H:i') }} &middot; {{ $reservation->cancelled_at->diffForHumans() }}</p>
                            </div>
                        </li>
                    @endif
                </ol>
            </div>
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
