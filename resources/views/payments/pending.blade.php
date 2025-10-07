<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pending Payments</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                    <form method="GET" class="flex flex-wrap items-end gap-2" onsubmit="this.page && (this.page.value=1);">
                        <div class="flex flex-col">
                            <label class="text-sm text-gray-700">Search</label>
                            <input type="text" name="q" value="{{ request('q') }}" placeholder="Order or customer" class="border rounded p-2 text-sm" />
                        </div>
                        <div class="flex flex-col">
                            <label class="text-sm text-gray-700">Customer</label>
                            <input type="number" name="customer_id" value="{{ request('customer_id') }}" class="border rounded p-2 text-sm" />
                        </div>
                        <div class="flex flex-col">
                            <label class="text-sm text-gray-700">From</label>
                            <input type="date" name="from_date" value="{{ request('from_date') }}" class="border rounded p-2 text-sm" />
                        </div>
                        <div class="flex flex-col">
                            <label class="text-sm text-gray-700">To</label>
                            <input type="date" name="to_date" value="{{ request('to_date') }}" class="border rounded p-2 text-sm" />
                        </div>
                        <div class="flex flex-col">
                            <label class="text-sm text-gray-700">Per page</label>
                            <select name="per_page" class="border rounded p-2 text-sm">
                                @foreach([10,25,50,100] as $n)
                                    <option value="{{ $n }}" @selected((int)request('per_page',10)===$n)>Show {{ $n }}</option>
                                @endforeach
                            </select>
                        </div>
                        @include('partials.export-toolbar', ['route' => 'payments.pending'])
                        <button class="bg-gray-800 text-white px-3 py-2 rounded text-sm">Apply</button>
                        @if(request()->hasAny(['per_page','q','customer_id','from_date','to_date']))
                            <a href="{{ route('payments.pending') }}" class="text-sm text-gray-600 underline">Clear</a>
                        @endif
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="p-2 text-left">Order</th>
                                <th class="p-2 text-left">Customer</th>
                                <th class="p-2 text-right">Penalty</th>
                                <th class="p-2 text-right">Total</th>
                                <th class="p-2 text-right">Paid</th>
                                <th class="p-2 text-right">Due</th>
                                <th class="p-2 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($orders as $o)
                                @php
                                    $suggest = app(\App\Services\PaymentService::class)->suggestedAmountForOrder($o->id);
                                    $pen = (float)($suggest['penalty'] ?? 0);
                                    $total = (float)($suggest['total'] ?? (float)($o->total_cost ?? 0));
                                    $completed = (float)$o->payments()->where('status','completed')->sum('amount');
                                    $refunded = (float)$o->payments()->where('status','refunded')->sum('amount');
                                    $paid = max(0.0, $completed - $refunded);
                                    $due = max(0, $total - $paid);
                                @endphp
                                <tr class="border-b">
                                    <td class="p-2"><a class="text-blue-700 hover:underline" href="{{ route('orders.show', $o) }}">{{ $o->order_id }}</a></td>
                                    <td class="p-2">{{ optional($o->customer)->name }}</td>
                                    <td class="p-2 text-right">{{ number_format($pen, 2) }}</td>
                                    <td class="p-2 text-right">{{ number_format($total, 2) }}</td>
                                    <td class="p-2 text-right">{{ number_format($paid, 2) }}</td>
                                    <td class="p-2 text-right">{{ number_format($due, 2) }}</td>
                                    <td class="p-2">
                                        @can('create_payments')
                                            <button type="button"
                                                class="inline-flex items-center justify-center gap-2 px-2 h-8 bg-emerald-600 hover:bg-emerald-700 text-white rounded-md transition"  
                                                @click="window.dispatchEvent(new CustomEvent('open-record-payment', { detail: { orderId: {{ $o->id }}, orderLabel: '{{ $o->order_id }} — {{ addslashes(optional($o->customer)->name) }}' } }))"
                                            >
                                                <span>Pay</span> <x-heroicon-o-credit-card class="w-4 h-4" />
                                            </button>
                                        @endcan
                                        @can('print_orders')
                                            <a href="{{ route('orders.invoice', $o) }}" target="_blank"  
                                                class="inline-flex items-center justify-center w-8 h-8 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md transition"
                                            >
                                                <x-heroicon-o-printer class="w-4 h-4" />
                                            </a>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div class="text-sm text-gray-600">Showing {{ $orders->firstItem() ?? 0 }} to {{ $orders->lastItem() ?? 0 }} of {{ $orders->total() }} results</div>
                    <div>{{ $orders->links() }}</div>
                </div>
            </div>
        </div>
    </div>
    @can('create_payments')
        @include('payments._record_modal', ['open' => false])
    @endcan
</x-app-layout>
