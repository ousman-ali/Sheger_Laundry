<div class="p-4">
    <div class="mb-2">
        <div class="text-lg font-semibold">Order {{ $order->order_id }}</div>
        <div class="text-sm text-gray-600">Customer: {{ optional($order->customer)->name }} | Created: {{ optional($order->created_at)->format('Y-m-d H:i') }}</div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <div class="font-medium mb-2">Payments</div>
            <div class="max-h-56 overflow-auto border rounded">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="p-2 text-left">Paid At</th>
                            <th class="p-2 text-left">Method</th>
                            <th class="p-2 text-left">Bank</th>
                            <th class="p-2 text-left">Status</th>
                            <th class="p-2 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ledger->payments as $p)
                            <tr class="border-b">
                                <td class="p-2">{{ optional($p->paid_at)->format('Y-m-d H:i') }}</td>
                                <td class="p-2">{{ ucfirst($p->method ?? '-') }}</td>
                                <td class="p-2">{{ optional($p->bank)->name }}</td>
                                <td class="p-2">{{ ucfirst($p->status) }}</td>
                                <td class="p-2 text-right">{{ number_format((float)$p->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td class="p-2" colspan="4">No payments.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div>
            <div class="font-medium mb-2">Total Cost Breakdown</div>
            <div class="border rounded p-2">
                <table class="min-w-full text-sm">
                    <tbody>
                        <tr><th class="p-2 text-right w-1/2">Subtotal</th><td class="p-2 text-right">{{ number_format($subtotal,2) }} ETB</td></tr>
                        <tr><th class="p-2 text-right">Penalty</th><td class="p-2 text-right">{{ number_format($penalty,2) }} ETB</td></tr>
                        <tr><th class="p-2 text-right">Total</th><td class="p-2 text-right font-semibold">{{ number_format($total,2) }} ETB</td></tr>
                        <tr><th class="p-2 text-right">Paid</th><td class="p-2 text-right">{{ number_format($paid,2) }} ETB</td></tr>
                        <tr><th class="p-2 text-right">Due</th><td class="p-2 text-right">{{ number_format($due,2) }} ETB</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="mt-3 text-right">
                @can('print_orders')
                    <a href="{{ route('orders.invoice', $order) }}" target="_blank" class="text-blue-600 hover:underline">Print Invoice</a>
                @endcan
            </div>
        </div>
    </div>
    <div class="mt-4">
        <div class="font-medium mb-1">Services priced</div>
        <div class="max-h-40 overflow-auto border rounded">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="p-2 text-left">Item</th>
                        <th class="p-2 text-left">Service</th>
                        <th class="p-2 text-right">Qty</th>
                        <th class="p-2 text-right">Unit</th>
                        <th class="p-2 text-right">Unit Price</th>
                        <th class="p-2 text-right">Line Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->orderItems as $it)
                        @foreach($it->orderItemServices as $svc)
                            <tr class="border-b">
                                <td class="p-2">{{ $it->clothItem->name }}</td>
                                <td class="p-2">{{ $svc->service->name }}</td>
                                <td class="p-2 text-right">{{ number_format((float)$svc->quantity,2) }}</td>
                                <td class="p-2 text-right">{{ optional($it->clothItem->unit)->name ?? optional($it->unit)->name }}</td>
                                @php
                                    $qty = (float)($svc->quantity ?? 0);
                                    $line = (float)($svc->price_applied ?? 0);
                                    $unit = $qty > 0 ? $line / $qty : 0;
                                @endphp
                                <td class="p-2 text-right">{{ number_format($unit,2) }}</td>
                                <td class="p-2 text-right">{{ number_format($line,2) }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">
        <div class="font-medium mb-1">Penalties</div>
        <div class="max-h-40 overflow-auto border rounded">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="p-2 text-left">Linked Service</th>
                        <th class="p-2 text-right">Amount</th>
                        <th class="p-2 text-left">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @php $pens = $order->itemPenalties()->latest()->get(); @endphp
                    @forelse($pens as $pen)
                        <tr class="border-b">
                            <td class="p-2">
                                @php $svc = optional($pen->orderItemService); @endphp
                                @if($svc)
                                    {{ optional($svc->service)->name }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="p-2 text-right">{{ number_format((float)$pen->amount, 2) }}</td>
                            <td class="p-2">
                                @if($pen->waived)
                                    <span class="text-green-700 text-xs bg-green-50 border border-green-200 rounded px-2 py-0.5">Waived</span>
                                @elseif($pen->requires_approval)
                                    <span class="text-amber-700 text-xs bg-amber-50 border border-amber-200 rounded px-2 py-0.5">Pending approval</span>
                                @else
                                    <span class="text-gray-700 text-xs bg-gray-50 border border-gray-200 rounded px-2 py-0.5">Active</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td class="p-2" colspan="3">No penalties.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
