@php
    $activeItems = $order->items->where('status', '!=', 'voided');
    $pendingItems  = $activeItems->where('status', 'pending');
    $kitchenItems  = $activeItems->whereIn('status', ['sent', 'preparing']);
    $readyItems    = $activeItems->whereIn('status', ['ready', 'served']);

    $pendingValue  = $pendingItems->sum(fn ($i) => (float) $i->unit_price * $i->quantity);
    $kitchenValue  = $kitchenItems->sum(fn ($i) => (float) $i->unit_price * $i->quantity);
    $readyValue    = $readyItems->sum(fn ($i) => (float) $i->unit_price * $i->quantity);
@endphp

@if ($activeItems->isNotEmpty())
    <section class="card overflow-hidden">
        <div class="border-b border-slate-100 px-5 py-3">
            <h3 class="text-sm font-semibold text-ink">Kitchen status</h3>
        </div>
        <div class="grid grid-cols-3 divide-x divide-slate-100">
            <div class="px-3 py-3 text-center">
                <p class="text-[10px] font-medium uppercase tracking-wide text-ink-muted">Pending</p>
                <p class="mt-1 text-lg font-bold text-slate-700">{{ $pendingItems->count() }}</p>
                @if ($pendingValue > 0)
                    <p class="text-[10px] text-ink-subtle">{{ number_format($pendingValue) }}</p>
                @endif
            </div>
            <div class="px-3 py-3 text-center">
                <p class="text-[10px] font-medium uppercase tracking-wide text-amber-600">In kitchen</p>
                <p class="mt-1 text-lg font-bold text-amber-700">{{ $kitchenItems->count() }}</p>
                @if ($kitchenValue > 0)
                    <p class="text-[10px] text-amber-500">{{ number_format($kitchenValue) }}</p>
                @endif
            </div>
            <div class="px-3 py-3 text-center">
                <p class="text-[10px] font-medium uppercase tracking-wide text-emerald-600">Ready/Served</p>
                <p class="mt-1 text-lg font-bold text-emerald-700">{{ $readyItems->count() }}</p>
                @if ($readyValue > 0)
                    <p class="text-[10px] text-emerald-500">{{ number_format($readyValue) }}</p>
                @endif
            </div>
        </div>
        @if ($kitchenItems->isNotEmpty())
            <div class="border-t border-slate-100 bg-amber-50/50 px-4 py-2 text-xs text-amber-700">
                {{ $kitchenItems->count() }} item{{ $kitchenItems->count() !== 1 ? 's' : '' }} still being prepared — settle after all are served.
            </div>
        @endif
    </section>
@endif

<section class="card overflow-hidden">
    <div class="border-b border-slate-100 px-5 py-3 flex items-center justify-between">
        <h3 class="font-semibold text-ink">Order items</h3>
        <span class="text-xs text-ink-muted">{{ $currency }} {{ number_format((float) $order->total) }} total</span>
    </div>

    @if ($activeItems->isEmpty())
        <p class="px-5 py-6 text-center text-sm text-ink-muted">No items yet — add from menu.</p>
    @else
        <ul class="divide-y divide-slate-100">
            @foreach ($activeItems as $item)
                <li class="flex items-center justify-between gap-2 px-5 py-2.5">
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-ink">
                            {{ $item->menuItem?->name }}
                            @if ($item->menuItem?->isBeverage())
                                <span class="text-[10px] font-semibold uppercase text-sky-600">bar</span>
                            @endif
                        </p>
                        <p class="text-xs text-ink-muted">
                            × {{ (int) $item->quantity }} ·
                            <span @class([
                                'capitalize',
                                'text-amber-600'   => in_array($item->status, ['sent', 'preparing']),
                                'text-emerald-600' => in_array($item->status, ['ready', 'served']),
                            ])>{{ $item->status }}</span>
                        </p>
                    </div>
                    <p class="shrink-0 text-sm font-semibold">
                        {{ number_format((float) $item->unit_price * $item->quantity) }}
                    </p>
                    @if ($isOpen && $canManage && $item->status === 'pending')
                        <form method="POST"
                              action="{{ route('tenant.pos.orders.void-item', [$order, $item]) }}"
                              onsubmit="return confirm('Void this item?')">
                            @csrf
                            <button type="submit" class="text-xs text-rose-500 hover:text-rose-700">✕</button>
                        </form>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    @if ($activeItems->isNotEmpty())
        <div class="space-y-1 border-t border-slate-200 px-5 py-3">
            <div class="flex justify-between text-sm text-ink-muted">
                <span>Subtotal (net)</span>
                <span>{{ $currency }} {{ number_format((float) $order->subtotal) }}</span>
            </div>
            <div class="flex justify-between text-sm text-ink-muted">
                <span>Tax</span>
                <span>{{ $currency }} {{ number_format((float) $order->tax_amount) }}</span>
            </div>
            <div class="flex justify-between text-base font-bold text-ink">
                <span>Total</span>
                <span>{{ $currency }} {{ number_format((float) $order->total) }}</span>
            </div>
        </div>
    @endif
</section>

@if ($isOpen && $canManage && $activeItems->isNotEmpty())

    @if ($order->status === 'open')
        @if ($order->outlet->isBar())
            <form method="POST" action="{{ route('tenant.pos.orders.serve', $order) }}">
                @csrf
                <button type="submit" class="w-full rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700">
                    Serve order
                </button>
            </form>
        @else
            <form method="POST" action="{{ route('tenant.pos.orders.fire', $order) }}">
                @csrf
                <button type="submit" class="w-full rounded-xl bg-amber-500 px-4 py-3 text-sm font-semibold text-white transition hover:bg-amber-600">
                    Fire to kitchen 🔥
                </button>
            </form>
        @endif
    @endif

    @if ($showCash)
        <section class="card p-5">
            <h4 class="mb-1 font-semibold text-ink">Collect — Cash</h4>
            @if ($activeTill)
                <p class="mb-3 text-xs text-emerald-600">Till open · change will be tracked.</p>
            @else
                <p class="mb-3 text-xs text-ink-muted">No open till — payment recorded without till tracking.</p>
            @endif
            <form method="POST" action="{{ route('tenant.pos.orders.settle-direct', $order) }}" class="space-y-3">
                @csrf
                <input type="hidden" name="method" value="cash">
                <div>
                    <label class="mb-1 block text-xs font-medium text-ink-muted">Amount tendered ({{ $currency }})</label>
                    <input type="number" name="tendered" step="0.01"
                           min="{{ (float) $order->total }}"
                           placeholder="{{ number_format((float) $order->total) }}"
                           class="input-field w-full" required>
                    <p class="mt-1 text-xs text-ink-subtle">Total: {{ $currency }} {{ number_format((float) $order->total) }}</p>
                </div>
                <button type="submit" class="w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                    Collect cash
                </button>
            </form>
        </section>
    @endif

    @if ($showMobileMoney)
        <section class="card p-5">
            <h4 class="mb-1 font-semibold text-ink">Collect — Mobile Money</h4>
            <p class="mb-3 text-xs text-ink-muted">M-Pesa, Airtel Money, etc. Transaction ref is optional.</p>
            <form method="POST" action="{{ route('tenant.pos.orders.settle-direct', $order) }}" class="space-y-3">
                @csrf
                <input type="hidden" name="method" value="mobile_money">
                <div>
                    <label class="mb-1 block text-xs font-medium text-ink-muted">Transaction reference (optional)</label>
                    <input type="text" name="transaction_ref" maxlength="100"
                           placeholder="e.g. MPX123456789"
                           class="input-field w-full">
                </div>
                <p class="text-xs text-ink-subtle">Amount: {{ $currency }} {{ number_format((float) $order->total) }}</p>
                <button type="submit" class="w-full rounded-xl bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-700">
                    Confirm mobile payment
                </button>
            </form>
        </section>
    @endif

    @if ($showCard)
        <section class="card p-5">
            <h4 class="mb-1 font-semibold text-ink">Collect — Card</h4>
            <p class="mb-3 text-xs text-ink-muted">Debit or credit card via POS terminal. Auth code is optional.</p>
            <form method="POST" action="{{ route('tenant.pos.orders.settle-direct', $order) }}" class="space-y-3">
                @csrf
                <input type="hidden" name="method" value="card">
                <div>
                    <label class="mb-1 block text-xs font-medium text-ink-muted">Auth / reference code (optional)</label>
                    <input type="text" name="transaction_ref" maxlength="100"
                           placeholder="e.g. AUTH 845231"
                           class="input-field w-full">
                </div>
                <p class="text-xs text-ink-subtle">Amount: {{ $currency }} {{ number_format((float) $order->total) }}</p>
                <button type="submit" class="w-full rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-violet-700">
                    Confirm card payment
                </button>
            </form>
        </section>
    @endif

    @if ($showFolio)
        <section class="card p-5">
            <h4 class="mb-1 font-semibold text-ink">Post to room folio</h4>
            <p class="mb-3 text-xs text-ink-muted">Charge to a checked-in guest's account (post-paid).</p>
            @if ($openFolios->isNotEmpty())
                <form method="POST" action="{{ route('tenant.pos.orders.settle-folio', $order) }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="mb-1 block text-xs font-medium text-ink-muted">Guest folio</label>
                        <select name="folio_id" required class="input-field w-full">
                            <option value="">— Select guest —</option>
                            @foreach ($openFolios as $folio)
                                @php
                                    $guest     = $folio->reservation?->guest;
                                    $guestName = trim(($guest?->first_name ?? '').' '.($guest?->last_name ?? '')) ?: 'Unknown guest';
                                    $room      = $folio->reservation?->room?->room_number ?? '—';
                                @endphp
                                <option value="{{ $folio->id }}">
                                    {{ $guestName }} · Rm {{ $room }} · {{ $folio->folio_number }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit"
                            class="w-full rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">
                        Post to folio
                    </button>
                </form>
            @else
                <p class="text-xs text-ink-subtle">No open guest folios. Check guests in first.</p>
            @endif
        </section>
    @endif

@elseif ($order->status === 'closed')
    @php
        $order->loadMissing('payments');
        $payment = $order->payments->first();
        $methodLabel = match($payment?->method) {
            'cash'         => 'Cash',
            'mobile_money' => 'Mobile money'.($payment->gateway_ref ? ' (ref: '.$payment->gateway_ref.')' : ''),
            'card'         => 'Card'.($payment->gateway_ref ? ' (ref: '.$payment->gateway_ref.')' : ''),
            default        => $payment?->method ?? ($order->folio_id ? 'Posted to folio' : 'Settled'),
        };
    @endphp
    <div class="card p-5 text-center">
        <p class="text-sm font-semibold text-emerald-700">Order settled ✓</p>
        <p class="mt-1 text-xs text-ink-muted">
            {{ $order->folio_id ? 'Posted to guest folio.' : "Paid by {$methodLabel}." }}
            Closed {{ $order->closed_at?->format('d M H:i') }}
        </p>
        <div class="mt-3 flex justify-center gap-3">
            <a href="{{ route('tenant.pos.orders.receipt', $order) }}"
               target="_blank"
               class="btn-primary text-xs">
                Print receipt ↗
            </a>
            <a href="{{ $backRoute }}" class="btn-outline text-xs">← Back</a>
        </div>
    </div>
@endif
