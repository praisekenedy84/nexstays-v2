@php
    $paymentMode = app(\App\Domain\HBMS\Services\ReservationSettingsService::class)->all()['payment_mode'] ?? 'prepaid';
    $today = now()->startOfDay();
    $checkInDate = $reservation->check_in_date->copy()->startOfDay();
    $checkOutDate = $reservation->check_out_date->copy()->startOfDay();
    $bookedNights = $reservation->total_nights;
    $bookedStayAmount = (float) $reservation->total_amount;
    $stayedDays = $reservation->status === 'checked_out'
        ? $checkInDate->diffInDays($checkOutDate)
        : ($reservation->status === 'checked_in' ? $checkInDate->diffInDays($today) : 0);
    $stayedDays = max(0, $stayedDays);
    $stayedAmount = $stayedDays * (float) $reservation->daily_rate;
    $overstayStatusLabels = [
        'detected' => 'Not yet posted',
        'pending'  => 'On folio — awaiting payment or waiver',
        'paid'     => 'Paid',
        'waived'   => 'Waived',
    ];
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
                    <form method="POST" action="{{ route('tenant.reservations.destroy', $reservation) }}" onsubmit="return confirm('Permanently delete this reservation? This cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-outline text-red-600 hover:border-red-200 hover:bg-red-50">Delete</button>
                    </form>
                @elseif (auth()->user()?->can('force-delete-reservations'))
                    <form method="POST" action="{{ route('tenant.reservations.destroy', $reservation) }}"
                          onsubmit="return confirm('Permanently delete {{ $reservation->booking_ref }}?\n\nThis reservation is {{ str_replace('_', ' ', $reservation->status) }} and may include folio history. This cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-outline text-red-600 hover:border-red-200 hover:bg-red-50">Delete permanently</button>
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
                @if ($reservation->source)
                    <div>
                        <dt class="text-xs font-medium uppercase text-ink-muted">Payment source</dt>
                        <dd class="mt-1 font-medium text-ink">
                            {{ app(\App\Domain\Shared\Services\PaymentMethodSettingsService::class)->labelFor($reservation->source) }}
                        </dd>
                    </div>
                @endif
                <div>
                    <dt class="text-xs font-medium uppercase text-ink-muted">Booked by</dt>
                    <dd class="mt-1 font-medium text-ink">{{ $reservation->creator?->name ?? '—' }}</dd>
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
                    <dt class="text-xs font-medium uppercase text-ink-muted">Spent so far (booked stay)</dt>
                    <dd class="mt-1 font-medium text-ink">
                        {{ min($stayedDays, $bookedNights) }} of {{ $bookedNights }} booked night(s) · @money(min($stayedAmount, $bookedStayAmount))
                    </dd>
                </div>
                @if ($overstayIncrease)
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-medium uppercase text-ink-muted">Overstay increase</dt>
                        <dd class="mt-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-lg font-bold text-amber-700">+ @money($overstayIncrease['charge'])</span>
                                <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">
                                    {{ $overstayIncrease['nights'] }} extra night(s) × @money($overstayIncrease['rate'])
                                </span>
                                <span @class([
                                    'rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                    'bg-amber-100 text-amber-800' => in_array($overstayIncrease['status'], ['detected', 'pending'], true),
                                    'bg-emerald-100 text-emerald-800' => $overstayIncrease['status'] === 'paid',
                                    'bg-slate-100 text-slate-700' => $overstayIncrease['status'] === 'waived',
                                ])>
                                    {{ $overstayStatusLabels[$overstayIncrease['status']] ?? $overstayIncrease['status'] }}
                                </span>
                            </div>
                            @if ($reservation->isOverstaySettled())
                                <p class="mt-2 text-sm text-ink">
                                    @if ($overstayIncrease['status'] === 'paid' && $reservation->overstaySettlementPayment)
                                        Payment recorded:
                                        @money($reservation->overstaySettlementPayment->amount)
                                        via {{ $paymentMethods[$reservation->overstaySettlementPayment->method]['label'] ?? ucfirst(str_replace('_', ' ', $reservation->overstaySettlementPayment->method)) }}
                                        @if ($reservation->overstaySettlementPayment->notes)
                                            · {{ $reservation->overstaySettlementPayment->notes }}
                                        @endif
                                    @elseif ($overstayIncrease['status'] === 'waived')
                                        Waiver recorded — reason:
                                        {{ $reservation->overstay_waiver_reason }}
                                    @endif
                                    @if ($reservation->overstay_settled_at)
                                        <span class="block text-xs text-ink-muted mt-1">
                                            {{ $reservation->overstay_settled_at->format('d M Y, H:i') }}
                                            @if ($reservation->overstaySettledBy)
                                                · {{ $reservation->overstaySettledBy->name }}
                                            @endif
                                        </span>
                                    @endif
                                </p>
                            @elseif ($overstayIncrease['status'] === 'pending')
                                <p class="mt-1 text-xs text-amber-800">
                                    This amount has been added to the folio. Record payment or a waiver below to settle it.
                                </p>
                            @else
                                <p class="mt-1 text-xs text-amber-800">
                                    Guest is past check-out ({{ $reservation->check_out_date->format('d M Y') }}). Post this charge to the folio to continue.
                                </p>
                            @endif
                        </dd>
                    </div>
                @endif
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

            @if ($reservation->status === 'checked_in' && $overstayIncrease)
                <div class="border-t border-slate-100 pt-4">
                    <p class="mb-3 text-xs font-medium uppercase tracking-wide text-ink-muted">Overstay settlement</p>

                    @if ($overstayIncrease['status'] === 'detected')
                        <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-4">
                            <div class="mb-3 flex flex-wrap items-end justify-between gap-3">
                                <div>
                                    <p class="text-xs font-medium uppercase text-amber-900/70">Amount to add to folio</p>
                                    <p class="text-2xl font-bold text-amber-900">+ @money($overstayIncrease['charge'])</p>
                                </div>
                                <p class="text-sm text-amber-800">
                                    {{ $overstayIncrease['nights'] }} night(s) × @money($overstayIncrease['rate'])
                                </p>
                            </div>
                            @can('manage-reservations')
                                <form method="POST"
                                      action="{{ route('tenant.reservations.overstay.charge', $reservation) }}"
                                      class="space-y-3 border-t border-amber-200 pt-4">
                                    @csrf
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-amber-900/80">Rate override (optional)</label>
                                        <input type="number" name="rate_override" step="0.01" min="0"
                                               value="{{ old('rate_override') }}"
                                               placeholder="{{ number_format($overstayIncrease['rate'], 2, '.', '') }}"
                                               class="input-field w-full max-w-xs">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-amber-900/80">Notes (optional)</label>
                                        <input type="text" name="notes" value="{{ old('notes') }}" maxlength="500"
                                               class="input-field w-full" placeholder="Internal note">
                                    </div>
                                    <button type="submit"
                                            class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">
                                        Post + @money($overstayIncrease['charge']) to folio
                                    </button>
                                </form>
                            @else
                                <p class="text-xs text-amber-800">Ask a manager to post the overstay charge to the folio.</p>
                            @endcan
                        </div>
                    @elseif ($reservation->hasPendingOverstay())
                        <div class="rounded-xl border border-sky-200 bg-sky-50/60 p-4">
                            <div class="mb-3 flex flex-wrap items-end justify-between gap-3">
                                <div>
                                    <p class="text-xs font-medium uppercase text-sky-900/70">Added to folio</p>
                                    <p class="text-2xl font-bold text-sky-900">+ @money($overstayIncrease['charge'])</p>
                                </div>
                                <p class="text-sm text-sky-800">
                                    {{ $overstayIncrease['nights'] }} night(s) · awaiting settlement
                                </p>
                            </div>
                            @if ($reservation->overstay_notes)
                                <p class="mb-3 text-xs text-sky-700">Charge note: {{ $reservation->overstay_notes }}</p>
                            @endif

                            @can('post-folio-charges')
                                @if ($enabledMethods !== [])
                                    <form method="POST"
                                          action="{{ route('tenant.reservations.overstay.settle', $reservation) }}"
                                          class="space-y-3 border-t border-sky-200 pt-4">
                                        @csrf
                                        <input type="hidden" name="settlement" value="paid">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-sky-900/80">Record payment</p>
                                        <p class="text-xs text-sky-800">Collect @money($overstayIncrease['charge']) for the overstay charge.</p>
                                        <div>
                                            <label class="mb-1 block text-xs font-medium text-sky-900/80">Payment method</label>
                                            <select name="method" required class="input-field w-full max-w-xs">
                                                @foreach ($enabledMethods as $method)
                                                    <option value="{{ $method }}" @selected(old('method') === $method)>
                                                        {{ $paymentMethods[$method]['label'] ?? ucfirst(str_replace('_', ' ', $method)) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs font-medium text-sky-900/80">Notes (optional)</label>
                                            <input type="text" name="notes" value="{{ old('notes') }}" maxlength="500"
                                                   class="input-field w-full" placeholder="e.g. receipt or M-Pesa ref">
                                        </div>
                                        <button type="submit"
                                                class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                                            Record payment · @money($overstayIncrease['charge'])
                                        </button>
                                    </form>
                                @endif

                                <form method="POST"
                                      action="{{ route('tenant.reservations.overstay.settle', $reservation) }}"
                                      class="mt-4 space-y-3 border-t border-sky-200 pt-4"
                                      onsubmit="return confirm('Waive the overstay charge of {{ number_format((float) $overstayIncrease['charge']) }}?')">
                                    @csrf
                                    <input type="hidden" name="settlement" value="waived">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-sky-900/80">Record waiver</p>
                                    <p class="text-xs text-sky-800">Write off @money($overstayIncrease['charge']) with a documented reason.</p>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-sky-900/80">Reason for waiver <span class="text-red-600">*</span></label>
                                        <textarea name="waiver_reason" required maxlength="500" rows="2"
                                                  class="input-field w-full"
                                                  placeholder="e.g. Late checkout approved by GM, system error">{{ old('waiver_reason') }}</textarea>
                                        @error('waiver_reason')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                    </div>
                                    <button type="submit"
                                            class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-900 hover:bg-amber-100">
                                        Record waiver · @money($overstayIncrease['charge'])
                                    </button>
                                </form>
                            @else
                                <p class="mt-2 text-xs text-sky-700">Payment or waiver requires folio charge permission.</p>
                            @endcan
                        </div>
                    @elseif ($reservation->isOverstaySettled())
                        <div class="rounded-xl border border-emerald-200 bg-emerald-50/60 p-4">
                            <div class="mb-3 flex flex-wrap items-end justify-between gap-3">
                                <div>
                                    <p class="text-xs font-medium uppercase text-emerald-900/70">Overstay amount</p>
                                    <p class="text-2xl font-bold text-emerald-900">+ @money($overstayIncrease['charge'])</p>
                                </div>
                                <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800">
                                    {{ $overstayIncrease['status'] === 'paid' ? 'Payment recorded' : 'Waiver recorded' }}
                                </span>
                            </div>
                            <dl class="space-y-2 text-sm text-emerald-900">
                                <div class="flex justify-between gap-3">
                                    <dt class="text-emerald-800">Extra nights</dt>
                                    <dd class="font-medium">{{ $overstayIncrease['nights'] }} × @money($overstayIncrease['rate'])</dd>
                                </div>
                                @if ($overstayIncrease['status'] === 'paid' && $reservation->overstaySettlementPayment)
                                    <div class="flex justify-between gap-3">
                                        <dt class="text-emerald-800">Payment</dt>
                                        <dd class="font-medium">
                                            @money($reservation->overstaySettlementPayment->amount)
                                            · {{ $paymentMethods[$reservation->overstaySettlementPayment->method]['label'] ?? ucfirst(str_replace('_', ' ', $reservation->overstaySettlementPayment->method)) }}
                                        </dd>
                                    </div>
                                    @if ($reservation->overstaySettlementPayment->notes)
                                        <div class="flex justify-between gap-3">
                                            <dt class="text-emerald-800">Reference</dt>
                                            <dd class="font-medium">{{ $reservation->overstaySettlementPayment->notes }}</dd>
                                        </div>
                                    @endif
                                @elseif ($overstayIncrease['status'] === 'waived')
                                    <div>
                                        <dt class="text-emerald-800">Waiver reason</dt>
                                        <dd class="mt-1 font-medium">{{ $reservation->overstay_waiver_reason }}</dd>
                                    </div>
                                @endif
                                @if ($reservation->overstay_settled_at)
                                    <div class="flex justify-between gap-3 border-t border-emerald-200 pt-2 text-xs text-emerald-800">
                                        <span>Recorded {{ $reservation->overstay_settled_at->format('d M Y, H:i') }}</span>
                                        @if ($reservation->overstaySettledBy)
                                            <span>{{ $reservation->overstaySettledBy->name }}</span>
                                        @endif
                                    </div>
                                @endif
                            </dl>
                        </div>
                    @endif
                </div>
            @endif

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
            @php
                $currency = $reservation->folio->currency;
                $balanceDue = $folioBalance && ! $folioBalance->isZero() && ! $folioBalance->isNegative()
                    ? $folioBalance->getAmount()->toFloat()
                    : 0;
                $isFolioOpen = $reservation->folio->status === 'open';
                $canCollectPayment = auth()->user()?->can('post-folio-charges')
                    && $isFolioOpen
                    && $balanceDue > 0
                    && $enabledMethods !== []
                    && ! $reservation->hasPendingOverstay();
            @endphp
            <section class="card p-6">
                <div class="mb-4 flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-ink-muted">Folio</h2>
                        <p class="mt-1 font-mono text-xs text-ink-subtle">{{ $reservation->folio->folio_number }}</p>
                    </div>
                    <x-ui.status-badge :status="$reservation->folio->status" />
                </div>

                @if ($folioBalance && $folioBalance->isZero())
                    <p class="text-2xl font-bold text-emerald-700">Settled</p>
                    <p class="text-xs text-ink-subtle">Balance is zero — guest can be checked out.</p>
                @elseif ($folioBalance)
                    <p class="text-xs font-medium uppercase text-ink-muted">Balance due</p>
                    <p class="text-2xl font-bold text-ink">@money($folioBalance->getAmount()->__toString())</p>
                @else
                    <p class="text-2xl font-bold text-ink">—</p>
                @endif

                @if ($reservation->folio->transactions->isNotEmpty())
                    <div class="mt-4 border-t border-slate-100 pt-4">
                        <p class="mb-2 text-xs font-medium uppercase tracking-wide text-ink-muted">Ledger</p>
                        <ul class="max-h-48 space-y-2 overflow-y-auto text-xs">
                            @foreach ($reservation->folio->transactions as $tx)
                                @php
                                    $isOverstayCharge = $reservation->overstay_charge_transaction_id === $tx->id;
                                    $isOverstaySettlement = $reservation->overstay_settlement_transaction_id === $tx->id;
                                @endphp
                                <li @class([
                                    'flex justify-between gap-2 border-b pb-2',
                                    'border-amber-100 bg-amber-50/50 -mx-2 rounded px-2 py-1' => $isOverstayCharge,
                                    'border-emerald-100 bg-emerald-50/50 -mx-2 rounded px-2 py-1' => $isOverstaySettlement,
                                    'border-slate-100' => ! $isOverstayCharge && ! $isOverstaySettlement,
                                ])>
                                    <span class="min-w-0 text-ink-muted">
                                        <span class="block truncate">{{ $tx->description }}</span>
                                        <span class="text-ink-subtle">{{ $tx->posted_at?->format('d M H:i') }}</span>
                                        @if ($isOverstayCharge)
                                            <span class="mt-0.5 block text-[10px] font-semibold uppercase text-amber-700">Overstay charge</span>
                                        @elseif ($isOverstaySettlement)
                                            <span class="mt-0.5 block text-[10px] font-semibold uppercase text-emerald-700">
                                                Overstay {{ $reservation->overstay_settlement === 'paid' ? 'payment' : 'waiver' }}
                                            </span>
                                        @endif
                                    </span>
                                    <span @class([
                                        'shrink-0 font-medium',
                                        'text-emerald-700' => (float) $tx->amount < 0,
                                        'text-ink' => (float) $tx->amount >= 0,
                                    ])>@money($tx->amount)</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($canCollectPayment)
                    <div class="mt-5 border-t border-slate-100 pt-5">
                        <h3 class="mb-1 text-sm font-semibold text-ink">Collect payment</h3>
                        <p class="mb-3 text-xs text-ink-muted">Record cash, card, or mobile money against this folio.</p>
                        <form method="POST"
                              action="{{ route('tenant.folios.payments.store', $reservation->folio) }}"
                              class="space-y-3">
                            @csrf
                            <div>
                                <label class="mb-1 block text-xs font-medium text-ink-muted">Amount ({{ $currency }})</label>
                                <input type="number"
                                       name="amount"
                                       step="0.01"
                                       min="0.01"
                                       max="{{ $balanceDue }}"
                                       value="{{ old('amount', number_format($balanceDue, 2, '.', '')) }}"
                                       required
                                       class="input-field w-full">
                                <p class="mt-1 text-xs text-ink-subtle">Outstanding: {{ $currency }} {{ number_format($balanceDue) }}</p>
                                @error('amount')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-ink-muted">Payment method</label>
                                <select name="method" required class="input-field w-full">
                                    @foreach ($enabledMethods as $method)
                                        <option value="{{ $method }}" @selected(old('method') === $method)>
                                            {{ $paymentMethods[$method]['label'] ?? ucfirst(str_replace('_', ' ', $method)) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('method')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-ink-muted">Notes (optional)</label>
                                <input type="text"
                                       name="notes"
                                       value="{{ old('notes') }}"
                                       maxlength="500"
                                       placeholder="e.g. M-Pesa ref, receipt no."
                                       class="input-field w-full">
                                @error('notes')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <button type="submit"
                                    class="w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                Record payment
                            </button>
                        </form>
                    </div>
                @elseif ($isFolioOpen && $balanceDue > 0 && auth()->user()?->can('post-folio-charges') && $reservation->hasPendingOverstay())
                    <p class="mt-4 border-t border-slate-100 pt-4 text-xs text-sky-700">
                        Settle the pending overstay charge above before collecting other folio payments.
                    </p>
                @elseif ($isFolioOpen && $balanceDue > 0 && auth()->user()?->can('post-folio-charges') && $enabledMethods === [])
                    <p class="mt-4 border-t border-slate-100 pt-4 text-xs text-amber-700">
                        No payment methods are enabled. Configure them under Finance → Payment methods.
                    </p>
                @endif

                @can('post-folio-charges')
                    @if ($isFolioOpen && $balanceDue > 0 && ! $reservation->hasPendingOverstay())
                        <details class="mt-4 border-t border-slate-100 pt-4">
                            <summary class="cursor-pointer text-xs font-medium text-ink-muted hover:text-ink">
                                Write off balance
                            </summary>
                            <form method="POST"
                                  action="{{ route('tenant.folios.write-off', $reservation->folio) }}"
                                  class="mt-3 space-y-3"
                                  onsubmit="return confirm('Write off the outstanding folio balance?')">
                                @csrf
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-ink-muted">Reason</label>
                                    <input type="text" name="reason" required maxlength="500" class="input-field w-full"
                                           placeholder="e.g. Manager comp, staff error">
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-ink-muted">Amount (leave blank for full balance)</label>
                                    <input type="number" name="amount" step="0.01" min="0.01" max="{{ $balanceDue }}"
                                           class="input-field w-full" placeholder="{{ number_format($balanceDue, 2, '.', '') }}">
                                </div>
                                <button type="submit"
                                        class="w-full rounded-xl border border-amber-200 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-800 hover:bg-amber-100">
                                    Write off
                                </button>
                            </form>
                        </details>
                    @endif
                @endcan
            </section>
        @endif
    </div>
</x-layouts.app>
