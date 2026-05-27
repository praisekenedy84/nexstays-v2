<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt — {{ $order->order_number }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif;
            font-size: 11px;
            color: #0f172a;
            background: #f1f5f9;
            padding: 16px;
            display: flex;
            justify-content: center;
        }

        .receipt {
            background: #fff;
            width: 320px;
            padding: 16px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
        }

        /* ── Header ── */
        .hd { text-align: center; margin-bottom: 10px; }
        .hd-name { font-size: 14px; font-weight: 800; letter-spacing: 0.06em; text-transform: uppercase; color: #0f172a; }
        .hd-outlet { font-size: 10px; color: #64748b; margin-top: 2px; text-transform: uppercase; letter-spacing: 0.05em; }
        .hd-sub { font-size: 10px; color: #94a3b8; margin-top: 1px; }

        /* ── Dividers ── */
        .dashed { border: none; border-top: 1px dashed #cbd5e1; margin: 8px 0; }
        .solid  { border: none; border-top: 1px solid #e2e8f0; margin: 8px 0; }

        /* ── Meta rows ── */
        .meta-row { display: flex; justify-content: space-between; font-size: 10px; color: #64748b; margin-bottom: 2px; }
        .meta-val { color: #0f172a; font-weight: 600; }

        /* ── Items ── */
        .item-row { margin-bottom: 4px; }
        .item-name { font-weight: 600; font-size: 11px; color: #0f172a; }
        .item-detail { display: flex; justify-content: space-between; font-size: 10px; color: #64748b; margin-top: 1px; }

        /* ── Totals ── */
        .total-row { display: flex; justify-content: space-between; font-size: 11px; margin-bottom: 3px; }
        .total-row.grand { font-size: 13px; font-weight: 700; border-top: 1px solid #0f172a; padding-top: 5px; margin-top: 3px; }
        .total-label { color: #64748b; }
        .total-row.grand .total-label { color: #0f172a; }

        /* ── Payment ── */
        .payment-block { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; padding: 6px 8px; margin-top: 8px; }
        .payment-method { font-weight: 700; text-transform: uppercase; font-size: 10px; letter-spacing: 0.06em; color: #0f172a; }
        .payment-row { display: flex; justify-content: space-between; font-size: 10px; color: #64748b; margin-top: 2px; }

        /* ── QR ── */
        .qr-block { text-align: center; margin-top: 10px; }
        .qr-block img { width: 72px; height: 72px; }
        .qr-hint { font-size: 9px; color: #94a3b8; margin-top: 3px; letter-spacing: 0.04em; font-weight: 500; }

        /* ── Status badge ── */
        .status {
            display: inline-block; font-size: 9px; font-weight: 700;
            letter-spacing: 0.07em; text-transform: uppercase;
            padding: 2px 7px; border-radius: 3px;
        }
        .status-open             { background: #f1f5f9; color: #475569; }
        .status-sent_to_kitchen  { background: #fef9c3; color: #854d0e; }
        .status-preparing        { background: #fff0d8; color: #9a3412; }
        .status-partially_served { background: #dbeafe; color: #1d4ed8; }
        .status-served           { background: #dcfce7; color: #15803d; }
        .status-closed           { background: #dcfce7; color: #15803d; }
        .status-voided           { background: #fee2e2; color: #b91c1c; }

        /* ── Footer ── */
        .footer { text-align: center; font-size: 9px; color: #94a3b8; margin-top: 10px; line-height: 1.6; }

        /* ── Print btn ── */
        .print-btn {
            display: block; width: 100%; margin-top: 14px; padding: 9px;
            background: #0f172a; color: #fff; font-family: inherit;
            font-size: 11px; font-weight: 700; text-align: center;
            border: none; border-radius: 4px; cursor: pointer; letter-spacing: 0.04em;
        }

        @media print {
            @page { size: 80mm auto; margin: 3mm 2mm; }
            body { background: #fff; padding: 0; display: block; }
            .receipt { border: 0; border-radius: 0; width: 100%; padding: 0; page-break-inside: avoid; }
            .print-btn { display: none; }
        }
    </style>
</head>
<body>
<article class="receipt">

    {{-- Header --}}
    <div class="hd">
        <div class="hd-name">{{ config('app.name') }}</div>
        <div class="hd-outlet">{{ $order->outlet->name }}</div>
        <div class="hd-sub">Order Receipt</div>
    </div>

    <hr class="dashed">

    {{-- Meta --}}
    <div class="meta-row">
        <span>Order</span>
        <span class="meta-val">{{ $order->order_number }}</span>
    </div>
    <div class="meta-row">
        <span>Status</span>
        <span><span class="status status-{{ $order->status }}">{{ str_replace('_', ' ', $order->status) }}</span></span>
    </div>
    @if ($order->table)
    <div class="meta-row">
        <span>Table</span>
        <span class="meta-val">{{ $order->table->table_number }}</span>
    </div>
    @endif
    @if ($order->guest_label)
    <div class="meta-row">
        <span>Guest</span>
        <span class="meta-val">{{ $order->guest_label }}</span>
    </div>
    @endif
    <div class="meta-row">
        <span>Waiter</span>
        <span class="meta-val">{{ $order->waiter?->name ?? '—' }}</span>
    </div>
    <div class="meta-row">
        <span>Covers</span>
        <span class="meta-val">{{ $order->covers }} pax</span>
    </div>
    <div class="meta-row">
        <span>Opened</span>
        <span class="meta-val">{{ $order->opened_at?->format('d M Y, H:i') }}</span>
    </div>
    @if ($order->closed_at)
    <div class="meta-row">
        <span>Closed</span>
        <span class="meta-val">{{ $order->closed_at->format('d M Y, H:i') }}</span>
    </div>
    @endif

    <hr class="solid">

    {{-- Items --}}
    @php
        $currency = config('nexstay.currency.default', 'TZS');
        $activeItems = $order->items;
    @endphp

    @if ($activeItems->isEmpty())
        <p style="text-align:center; font-size:11px; color:#94a3b8;">No items.</p>
    @else
        @foreach ($activeItems as $item)
            <div class="item-row">
                <div class="item-name">{{ $item->menuItem?->name ?? 'Item' }}</div>
                <div class="item-detail">
                    <span>{{ (int) $item->quantity }} × {{ number_format((float) $item->unit_price) }}</span>
                    <span style="font-weight:600; color:#0f172a;">{{ number_format((float) $item->unit_price * $item->quantity) }}</span>
                </div>
            </div>
        @endforeach
    @endif

    <hr class="solid">

    {{-- Totals --}}
    <div class="total-row">
        <span class="total-label">Subtotal</span>
        <span>{{ $currency }} {{ number_format((float) $order->subtotal) }}</span>
    </div>
    <div class="total-row">
        <span class="total-label">Tax</span>
        <span>{{ $currency }} {{ number_format((float) $order->tax_amount) }}</span>
    </div>
    <div class="total-row grand">
        <span class="total-label">TOTAL</span>
        <span>{{ $currency }} {{ number_format((float) $order->total) }}</span>
    </div>

    {{-- Payment --}}
    @if ($payment)
        @php
            $methodLabel = match($payment->method) {
                'cash'         => 'Cash',
                'mobile_money' => 'Mobile Money',
                'card'         => 'Card',
                default        => ucfirst($payment->method),
            };
        @endphp
        <div class="payment-block">
            <div class="payment-method">{{ $methodLabel }}</div>
            @if ($payment->gateway_ref)
                <div class="payment-row"><span>Ref</span><span>{{ $payment->gateway_ref }}</span></div>
            @endif
            @if ($payment->cash_tendered)
                <div class="payment-row"><span>Tendered</span><span>{{ $currency }} {{ number_format((float) $payment->cash_tendered) }}</span></div>
                <div class="payment-row"><span>Change</span><span>{{ $currency }} {{ number_format((float) $payment->cash_change) }}</span></div>
            @endif
        </div>
    @elseif ($order->folio_id)
        <div class="payment-block">
            <div class="payment-method">Posted to room folio</div>
        </div>
    @endif

    {{-- QR --}}
    @php
        $qrOptions = new \chillerlan\QRCode\QROptions(['outputType' => 'svg', 'imageBase64' => false]);
        $qrSvgB64  = base64_encode((new \chillerlan\QRCode\QRCode($qrOptions))->render($order->order_number));
    @endphp
    <div class="qr-block">
        <img src="data:image/svg+xml;base64,{{ $qrSvgB64 }}" width="72" height="72" alt="QR">
        <div class="qr-hint">{{ $order->order_number }}</div>
    </div>

    {{-- Footer --}}
    <div class="footer">
        Thank you for your order!<br>
        {{ config('app.name') }} &middot; {{ now()->format('d M Y') }}
    </div>

    <button class="print-btn" onclick="window.print()">Print receipt</button>

</article>
</body>
</html>
