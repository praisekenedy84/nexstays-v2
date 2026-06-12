<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt — {{ $attendance->typeLabel() }}</title>
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

        .hd { text-align: center; margin-bottom: 10px; }
        .hd-name { font-size: 14px; font-weight: 800; letter-spacing: 0.06em; text-transform: uppercase; color: #0f172a; }
        .hd-sub { font-size: 10px; color: #94a3b8; margin-top: 1px; }

        .dashed { border: none; border-top: 1px dashed #cbd5e1; margin: 8px 0; }
        .solid  { border: none; border-top: 1px solid #e2e8f0; margin: 8px 0; }

        .meta-row { display: flex; justify-content: space-between; font-size: 10px; color: #64748b; margin-bottom: 2px; }
        .meta-val { color: #0f172a; font-weight: 600; }

        .total-row { display: flex; justify-content: space-between; font-size: 11px; margin-bottom: 3px; }
        .total-row.grand { font-size: 13px; font-weight: 700; border-top: 1px solid #0f172a; padding-top: 5px; margin-top: 3px; }
        .total-label { color: #64748b; }
        .total-row.grand .total-label { color: #0f172a; }

        .payment-block { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; padding: 6px 8px; margin-top: 8px; }
        .payment-method { font-weight: 700; text-transform: uppercase; font-size: 10px; letter-spacing: 0.06em; color: #0f172a; }
        .payment-row { display: flex; justify-content: space-between; font-size: 10px; color: #64748b; margin-top: 2px; }

        .status {
            display: inline-block; font-size: 9px; font-weight: 700;
            letter-spacing: 0.07em; text-transform: uppercase;
            padding: 2px 7px; border-radius: 3px;
        }
        .status-active { background: #dcfce7; color: #15803d; }
        .status-voided { background: #fee2e2; color: #b91c1c; }

        .footer { text-align: center; font-size: 9px; color: #94a3b8; margin-top: 10px; line-height: 1.6; }

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

    <div class="hd">
        <div class="hd-name">{{ config('app.name') }}</div>
        <div class="hd-sub">{{ $attendance->typeLabel() }} Receipt</div>
    </div>

    <hr class="dashed">

    <div class="meta-row">
        <span>Status</span>
        <span>
            @if ($attendance->isVoided())
                <span class="status status-voided">Voided</span>
            @else
                <span class="status status-active">Active</span>
            @endif
        </span>
    </div>
    <div class="meta-row">
        <span>Visitor</span>
        <span class="meta-val">{{ $attendance->visitor_name }}</span>
    </div>
    <div class="meta-row">
        <span>Type</span>
        <span class="meta-val">{{ $attendance->reservation_id ? 'Hotel guest' : 'Walk-in' }}</span>
    </div>
    <div class="meta-row">
        <span>People</span>
        <span class="meta-val">{{ $attendance->party_size }}</span>
    </div>
    <div class="meta-row">
        <span>Date & time</span>
        <span class="meta-val">{{ $attendance->attended_at->format('d M Y, H:i') }}</span>
    </div>
    <div class="meta-row">
        <span>Recorded by</span>
        <span class="meta-val">{{ $attendance->recorder?->name ?? '—' }}</span>
    </div>

    <hr class="solid">

    @php $currency = $attendance->currency ?? config('nexstay.currency.default', 'TZS'); @endphp

    <div class="total-row grand">
        <span class="total-label">TOTAL</span>
        <span>{{ $currency }} {{ number_format((float) $attendance->amount) }}</span>
    </div>

    @php
        $methodLabel = match ($attendance->settlement) {
            'cash' => 'Cash',
            'mobile_money' => 'Mobile Money',
            'card' => 'Card',
            'folio' => 'Charged to room folio',
            'complimentary' => 'Complimentary',
            default => ucfirst($attendance->settlement),
        };
    @endphp
    <div class="payment-block">
        <div class="payment-method">{{ $methodLabel }}</div>
        @if ($attendance->payment?->cash_tendered)
            <div class="payment-row"><span>Tendered</span><span>{{ $currency }} {{ number_format((float) $attendance->payment->cash_tendered) }}</span></div>
            <div class="payment-row"><span>Change</span><span>{{ $currency }} {{ number_format((float) $attendance->payment->cash_change) }}</span></div>
        @endif
        @if ($attendance->reservation)
            <div class="payment-row"><span>Booking ref</span><span>{{ $attendance->reservation->booking_ref }}</span></div>
        @endif
    </div>

    @if ($attendance->isVoided())
        <div class="payment-block" style="margin-top: 6px;">
            <div class="payment-method" style="color:#b91c1c;">Void reason</div>
            <div class="payment-row"><span>{{ $attendance->void_reason }}</span></div>
        </div>
    @endif

    @if ($attendance->notes)
        <hr class="solid">
        <div class="meta-row"><span>Notes</span><span class="meta-val">{{ $attendance->notes }}</span></div>
    @endif

    <div class="footer">
        Thank you!<br>
        {{ config('app.name') }} &middot; {{ now()->format('d M Y') }}
    </div>

    <button class="print-btn" onclick="window.print()">Print receipt</button>

</article>
</body>
</html>
