<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $order->order_id }}</title>
    <style>
        :root { --fg: #111827; --muted: #6b7280; --line: #e5e7eb; --bgth: #f3f4f6; --accent: #22c55e; }
        html, body { height: 100%; }
        body { font-family: -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, Arial, sans-serif; color: var(--fg); margin: 0; }
    .page { padding: 0; }
    .print-header { display: grid; grid-template-columns: 180px 1fr; align-items: center; gap: 16px; padding: 8px 10mm 6px; background: #fff; border-bottom: 3px solid var(--accent); }
        .print-header .logo img { max-height: 56px; width: auto; object-fit: contain; display: block; }
        .company { text-align: right; font-size: 11px; line-height: 1.3; }
        .company .name { font-size: 16px; font-weight: 700; }
    .accent-bar { height: 3px; background: var(--accent); margin: 4px 10mm 0; border-radius: 2px; }
    .content { position: relative; padding: 6mm 10mm; }
        .title { text-align: center; font-weight: 700; margin: 4px 0 4px; }
        .meta { font-size: 11px; color: var(--muted); text-align: center; }
        .section-bar { background: #e5e7eb; border: 1px solid #d1d5db; color: #111827; font-weight: 600; padding: 4px 8px; border-radius: 4px; margin: 8px 0 4px; }
        .panel { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin: 6px 0 6px; }
        .card { border: 1px solid var(--line); border-radius: 8px; padding: 8px; font-size: 11px; }
        .card .label { color: var(--muted); font-size: 10px; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th, td { padding: 6px; border-bottom: 1px solid var(--line); }
        th { background: var(--bgth); text-align: left; font-weight: 600; }
        .text-end { text-align: right; }
        .grid-2 { display: grid; grid-template-columns: 2fr 1fr; gap: 10px; align-items: start; }
        .totals { width: 100%; }
        .totals th { background: transparent; font-weight: 600; border: 0; }
        .totals td { border: 0; }
    .print-footer { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 6px 10mm 8px; border-top: 2px solid var(--line); background: #fff; }
        .footer-left { font-size: 11px; max-width: 70%; color: #374151; }
        .footer-right { text-align: right; }
        .stamp-img { height: 90px; opacity: 0.9; display: inline-block; }
    .stamp-overlay { position: absolute; right: 10mm; bottom: 18mm; }
        .id { font-size: 10px; color: var(--muted); text-align: center; }

    @media screen { .page { max-width: 800px; margin: 0 auto; } }

        @page { size: A4; margin: 12mm 10mm; }
        @media print {
            .no-print, .no-print * { display: none !important; visibility: hidden !important; }
            body { margin: 0; }
            .page { padding: 0; font-size: 10.5px; }
            .print-header { position: fixed; top: 12mm; left: 10mm; right: 10mm; }
            /* Accent bar is part of header via border-bottom; hide separate bar if present */
            .accent-bar { display: none !important; }
            .print-footer { position: fixed; bottom: 12mm; left: 10mm; right: 10mm; }
            /* Leave comfortable space for header/footer */
            .content { padding: 0 0; margin: 42mm 10mm 34mm 10mm; position: relative; }
            /* Repeat inner table headers on new pages */
            .content table thead { display: table-header-group; }
            /* Allow tables and grid to break naturally to prevent huge whitespace */
            .panel .card, .totals { page-break-inside: avoid; }
        }
    </style>
    </head>
<body>
    <div class="no-print" style="display:flex; gap:8px; justify-content:flex-end; padding:12px 10px 0;">
        <button onclick="window.print()" style="background:#374151; color:#fff; padding:8px 12px; border-radius:6px; border:0; cursor:pointer;">Print</button>
    </div>

    <div class="page">
        <header class="print-header">
            <div class="logo">
                @if(!empty($company['logo_url']))
                    <img src="{{ $company['logo_url'] }}" alt="Logo">
                @endif
            </div>
            <div class="company">
                <div class="name">{{ $company['name'] }}</div>
                <div>{{ $company['address'] }}</div>
                @if(!empty($company['phone']))<div>Tel: {{ $company['phone'] }}</div>@endif
                @if(!empty($company['email']))<div>Email: {{ $company['email'] }}</div>@endif
                @if(!empty($company['tin']))<div>TIN: {{ $company['tin'] }}</div>@endif
                @if(!empty($company['company_vat_no']))<div>VAT Reg. No: {{ $company['company_vat_no'] }}</div>@endif
            </div>
        </header>
        <div class="accent-bar"></div>

        <main class="content">
            <div class="title">Invoice</div>
            <div class="id">Invoice #{{ $order->order_id }} • Date: {{ now()->format('Y-m-d H:i') }}</div>

            <div class="section-bar">Transaction information</div>
            <div class="panel">
                <div class="card">
                    <div class="label">Billed To</div>
                    <div>{{ optional($order->customer)->name ?? '-' }}</div>
                    <div class="label" style="margin-top:4px;">Phone</div>
                    <div>{{ optional($order->customer)->phone ?? '-' }}</div>
                    <div class="label" style="margin-top:4px;">Date</div>
                    <div>{{ optional($order->created_at)->format('Y-m-d') }}</div>
                </div>
                <div class="card">
                    <div class="label">From</div>
                    <div>{{ $company['name'] }}</div>
                    <div class="label" style="margin-top:4px;">TIN</div>
                    <div>{{ $company['tin'] ?? '-' }}</div>
                    <div class="label" style="margin-top:4px;">Address</div>
                    <div>{{ $company['address'] ?? '-' }}</div>
                </div>
            </div>

            <div class="section-bar">Invoice details</div>
            <div class="grid-2">
                <div>
                    <table>
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Service</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Unit</th>
                                <th class="text-end">Price (ETB)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->orderItems as $it)
                                @foreach($it->orderItemServices as $svc)
                                    <tr>
                                        <td>{{ $it->clothItem->name }}</td>
                                        <td>{{ $svc->service->name }}</td>
                                        <td class="text-end">{{ number_format($svc->quantity, 2) }}</td>
                                        <td class="text-end">{{ optional($it->clothItem->unit)->name ?? optional($it->unit)->name }}</td>
                                        <td class="text-end">{{ number_format($svc->price_applied, 2) }}</td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>

                    <div style="height:8px"></div>
                    <div class="card">
                        <div class="label">Remarks</div>
                        @php
                            $labels = $order->remarkPresets->pluck('label')->all();
                        @endphp
                        @if(!empty($labels))
                            <div style="margin:2px 0 4px;">Common: {{ implode(', ', $labels) }}</div>
                        @endif
                        <div>{{ $order->remarks }}</div>
                    </div>
                </div>
                @php
                    $suggest = app(\App\Services\PaymentService::class)->suggestedAmountForOrder($order->id);
                    $subtotal = (float)($suggest['base'] ?? 0);
                    $penalty = (float)($suggest['penalty'] ?? 0);
                    $total = (float)($suggest['total'] ?? (float)($order->total_cost ?? 0));
                    $completed = (float)$order->payments()->where('status','completed')->sum('amount');
                    $refunded = (float)$order->payments()->where('status','refunded')->sum('amount');
                    $paid = max(0.0, $completed - $refunded);
                    $due = max(0, $total - $paid);
                    $itemizedPenalties = $order->itemPenalties()->where('waived', false)->get();
                @endphp
                <div>
                    <table class="totals">
                        <tbody>
                            <tr><th class="text-end" style="width:60%">Subtotal</th><td class="text-end">{{ number_format($subtotal, 2) }} ETB</td></tr>
                            <tr><th class="text-end">Penalty</th><td class="text-end">{{ number_format($penalty, 2) }} ETB</td></tr>
                            <tr><th class="text-end">Total</th><td class="text-end"><strong>{{ number_format($total, 2) }} ETB</strong></td></tr>
                            <tr><th class="text-end">Paid</th><td class="text-end">{{ number_format($paid, 2) }} ETB</td></tr>
                            <tr><th class="text-end">Due</th><td class="text-end">{{ number_format($due, 2) }} ETB</td></tr>
                        </tbody>
                    </table>

                    @if($itemizedPenalties->count() > 0)
                        <div style="height:8px"></div>
                        <div class="label">Penalty details</div>
                        <table>
                            <thead>
                                <tr>
                                    <th>Linked Service</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($itemizedPenalties as $pen)
                                    <tr>
                                        <td>
                                            @php $svc = optional($pen->orderItemService); @endphp
                                            @if($svc)
                                                {{ optional($svc->service)->name }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="text-end">{{ number_format((float)$pen->amount, 2) }} ETB</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif

                    <div style="height:8px"></div>
                    <table>
                        <thead>
                            <tr>
                                <th>Payment Method</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($order->payments as $p)
                                <tr>
                                    <td>{{ $p->method ?? '-' }}</td>
                                    <td class="text-end">{{ number_format((float)$p->amount, 2) }} ETB</td>
                                </tr>
                            @empty
                                <tr><td colspan="2">No payments recorded.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

                        @if(!empty($company['stamp_url']))
                            <div class="stamp-overlay">
                                <img src="{{ $company['stamp_url'] }}" alt="Stamp" class="stamp-img" />
                            </div>
                        @endif
        </main>

        <footer class="print-footer">
            <div class="footer-left">{{ $company['footer'] ?? 'Thank you for your business.' }}</div>
            <div class="footer-right"></div>
        </footer>
    </div>
</body>
</html>
