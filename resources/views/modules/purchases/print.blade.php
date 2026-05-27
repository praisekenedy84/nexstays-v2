<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $order->po_number }} — Purchase Order</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif;
            font-size: 11px;
            color: #0f172a;
            background: #fff;
            padding: 24px 28px;
        }

        .page { max-width: 760px; margin: 0 auto; }

        /* ── Header ── */
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 18px; }
        .company-name { font-size: 18px; font-weight: 800; color: #0f172a; letter-spacing: -0.01em; }
        .company-sub { font-size: 10px; color: #64748b; margin-top: 3px; }
        .po-badge { text-align: right; }
        .po-title { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #4f46e5; }
        .po-number { font-size: 20px; font-weight: 800; color: #4f46e5; letter-spacing: 0.01em; margin-top: 1px; }
        .po-number-mono { font-variant-numeric: tabular-nums; }

        /* ── Status badge ── */
        .status {
            display: inline-block; padding: 2px 9px; border-radius: 9999px;
            font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em;
        }
        .status-draft    { background: #f1f5f9; color: #64748b; }
        .status-ordered  { background: #dbeafe; color: #1d4ed8; }
        .status-received { background: #dcfce7; color: #15803d; }
        .status-cancelled{ background: #fee2e2; color: #dc2626; }

        /* ── Meta grid ── */
        .meta {
            display: grid; grid-template-columns: 1fr 1fr 1fr;
            gap: 0; margin-bottom: 16px;
            border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden;
        }
        .meta-block {
            padding: 9px 12px; border-right: 1px solid #e2e8f0;
            background: #f8fafc;
        }
        .meta-block:last-child { border-right: none; }
        .meta-label { font-size: 8px; text-transform: uppercase; letter-spacing: 0.06em; color: #94a3b8; font-weight: 600; }
        .meta-value { font-size: 12px; font-weight: 600; color: #0f172a; margin-top: 2px; }

        /* ── Notes ── */
        .notes-box {
            padding: 8px 12px; background: #fefce8; border: 1px solid #fde68a;
            border-radius: 5px; margin-bottom: 14px; font-size: 10px; color: #92400e;
        }

        /* ── Table ── */
        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }

        thead { background: #0f172a; color: #fff; }
        thead th {
            padding: 7px 10px; text-align: left;
            font-size: 9px; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600;
        }
        thead th.right { text-align: right; }

        tbody tr { border-bottom: 1px solid #e2e8f0; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        tbody td { padding: 6px 10px; font-size: 10px; }
        tbody td.right { text-align: right; }

        tfoot tr { border-top: 2px solid #0f172a; }
        tfoot td { padding: 7px 10px; font-weight: 700; font-size: 11px; }
        tfoot td.right { text-align: right; }

        /* ── Variance ── */
        .var-positive { color: #dc2626; }
        .var-negative { color: #15803d; }

        /* ── Cost variance summary ── */
        .variance-summary {
            display: inline-block; padding: 8px 14px; margin-bottom: 16px;
            border-radius: 6px; font-size: 10px;
        }
        .variance-summary .vc-label { font-size: 9px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.06em; }
        .variance-summary .vc-value { font-size: 16px; font-weight: 800; margin-top: 1px; }

        /* ── Signatures ── */
        .sigs { display: flex; justify-content: space-between; gap: 24px; margin-top: 28px; }
        .sig-block { flex: 1; }
        .sig-line { border-top: 1px solid #cbd5e1; margin-top: 32px; padding-top: 5px; font-size: 9px; color: #94a3b8; }

        /* ── Page footer ── */
        .page-footer { margin-top: 16px; font-size: 9px; color: #94a3b8; text-align: center; }

        /* ── Print ── */
        @media print {
            @page { size: A4 portrait; margin: 12mm; }
            body { padding: 0; }
            .page { max-width: 100%; }
            .no-print { display: none; }
            table { page-break-inside: avoid; }
            .sigs { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
<div class="page">

    {{-- Header --}}
    <div class="header">
        <div>
            <div class="company-name">{{ config('app.name', 'NexStay') }}</div>
            <div class="company-sub">
                {{ ucfirst($order->department) }} Department
                @if ($order->outlet) &middot; {{ $order->outlet->name }} @endif
            </div>
        </div>
        <div class="po-badge">
            <div class="po-title">Purchase Order</div>
            <div class="po-number po-number-mono">{{ $order->po_number }}</div>
            <div style="margin-top:5px;">
                <span class="status status-{{ $order->status }}">{{ $order->status }}</span>
            </div>
        </div>
    </div>

    {{-- Meta grid ── up to 6 cells in a 3-col grid ── --}}
    <div class="meta">
        <div class="meta-block">
            <p class="meta-label">Supplier</p>
            <p class="meta-value">{{ $order->supplier_name }}</p>
            @if ($order->supplier_reference)
                <p style="font-size:10px;color:#64748b;margin-top:1px;">Ref: {{ $order->supplier_reference }}</p>
            @endif
        </div>
        <div class="meta-block">
            <p class="meta-label">Order date</p>
            <p class="meta-value">{{ ($order->ordered_at ?? $order->created_at)->format('d M Y') }}</p>
        </div>
        <div class="meta-block">
            <p class="meta-label">Payment terms</p>
            <p class="meta-value">{{ $order->payment_terms ?: '—' }}</p>
        </div>
        <div class="meta-block" style="border-top:1px solid #e2e8f0;">
            <p class="meta-label">Expected delivery</p>
            <p class="meta-value">{{ $order->delivery_date_expected?->format('d M Y') ?? '—' }}</p>
        </div>
        @if ($order->received_at)
        <div class="meta-block" style="border-top:1px solid #e2e8f0;">
            <p class="meta-label">Received on</p>
            <p class="meta-value">{{ $order->received_at->format('d M Y') }}</p>
        </div>
        @endif
        <div class="meta-block" style="border-top:1px solid #e2e8f0; border-right:none;">
            <p class="meta-label">Prepared by</p>
            <p class="meta-value">{{ $order->creator?->name ?? '—' }}</p>
        </div>
    </div>

    {{-- Notes --}}
    @if ($order->notes)
        <div class="notes-box"><strong>Notes:</strong> {{ $order->notes }}</div>
    @endif

    {{-- Line items --}}
    <table>
        <thead>
            <tr>
                <th style="width:24px;">#</th>
                <th>Item / Description</th>
                <th class="right">Qty ordered</th>
                <th class="right">Unit cost</th>
                <th class="right">Line total</th>
                @if ($order->status === 'received')
                    <th class="right">Qty received</th>
                    <th class="right">Actual cost</th>
                    <th class="right">Variance</th>
                @endif
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->lines as $i => $line)
                @php $vp = $order->status === 'received' ? $line->variancePct() : null; @endphp
                <tr>
                    <td style="color:#94a3b8;">{{ $i + 1 }}</td>
                    <td><strong>{{ $line->stockItem?->name ?? '—' }}</strong></td>
                    <td class="right">{{ rtrim(rtrim((string) $line->quantity, '0'), '.') }}</td>
                    <td class="right">{{ number_format((float) $line->unit_cost, 2) }}</td>
                    <td class="right"><strong>{{ number_format((float) $line->line_total, 2) }}</strong></td>
                    @if ($order->status === 'received')
                        <td class="right">{{ $line->qty_received !== null ? rtrim(rtrim((string) $line->qty_received, '0'), '.') : '—' }}</td>
                        <td class="right">{{ $line->actual_unit_cost !== null ? number_format((float) $line->actual_unit_cost, 2) : '—' }}</td>
                        <td class="right">
                            @if ($vp !== null)
                                <span class="{{ $vp > 0 ? 'var-positive' : ($vp < 0 ? 'var-negative' : '') }}">
                                    {{ $vp > 0 ? '+' : '' }}{{ number_format($vp, 1) }}%
                                </span>
                            @else —
                            @endif
                        </td>
                    @endif
                    <td style="color:#64748b;">{{ $line->line_notes ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="{{ $order->status === 'received' ? 4 : 3 }}" style="text-align:right; color:#64748b; font-weight:500;">
                    Ordered total
                </td>
                <td class="right">{{ number_format((float) $order->total_amount, 2) }}</td>
                @if ($order->status === 'received')
                    <td colspan="2" style="text-align:right; color:#64748b; font-weight:500;">Received total</td>
                    <td class="right {{ $order->costVariance() > 0 ? 'var-positive' : ($order->costVariance() < 0 ? 'var-negative' : '') }}">
                        {{ number_format((float) $order->received_total, 2) }}
                    </td>
                @endif
                <td></td>
            </tr>
        </tfoot>
    </table>

    {{-- Cost variance summary ── only for received orders ── --}}
    @if ($order->status === 'received' && $order->costVariancePct() !== null)
        @php $vc = $order->costVariance(); $vcp = $order->costVariancePct(); @endphp
        <div class="variance-summary"
             style="background:{{ $vc > 0 ? '#fef2f2' : '#f0fdf4' }}; border:1px solid {{ $vc > 0 ? '#fca5a5' : '#86efac' }};">
            <p class="vc-label" style="color:{{ $vc > 0 ? '#dc2626' : '#15803d' }};">Cost variance</p>
            <p class="vc-value" style="color:{{ $vc > 0 ? '#dc2626' : '#15803d' }};">
                {{ $vc > 0 ? '+' : '' }}{{ number_format($vc, 2) }}
                &nbsp;({{ $vc > 0 ? '+' : '' }}{{ number_format($vcp, 1) }}%)
            </p>
        </div>
    @endif

    {{-- Signatures --}}
    <div class="sigs">
        <div class="sig-block"><div class="sig-line">Prepared by / Requested by</div></div>
        <div class="sig-block"><div class="sig-line">Approved by</div></div>
        <div class="sig-block"><div class="sig-line">Received by / Store</div></div>
    </div>

    <div class="page-footer">
        Generated {{ now()->format('d M Y H:i') }} &middot; {{ $order->po_number }} &middot; {{ config('app.name', 'NexStay') }}
    </div>

</div>

<script>
    window.addEventListener('load', () => window.print());
</script>
</body>
</html>
