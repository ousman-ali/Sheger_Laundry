<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Invoices</h2>
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
                            <label class="text-sm text-gray-700">Status</label>
                            <select name="status" class="border rounded p-2 text-sm">
                                <option value="">Any</option>
                                @foreach(['paid','partial','unpaid'] as $s)
                                    <option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-col">
                            <label class="text-sm text-gray-700">Per page</label>
                            <select name="per_page" class="border rounded p-2 text-sm">
                                @foreach([10,25,50,100] as $n)
                                    <option value="{{ $n }}" @selected((int)request('per_page',10)===$n)>Show {{ $n }}</option>
                                @endforeach
                            </select>
                        </div>
                        @include('partials.export-toolbar', ['route' => 'invoices.index'])
                        <button class="bg-gray-800 text-white px-3 py-2 rounded text-sm">Apply</button>
                        @if(request()->hasAny(['per_page','q','customer_id','from_date','to_date','status']))
                            <a href="{{ route('invoices.index') }}" class="text-sm text-gray-600 underline">Clear</a>
                        @endif
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="p-2 text-left">
                                    <a href="{{ route('invoices.index', array_merge(request()->query(), ['sort'=>'order_id','direction'=> request('direction')==='asc' && request('sort')==='order_id' ? 'desc' : 'asc'])) }}">Order</a>
                                </th>
                                <th class="p-2 text-left">Customer</th>
                                <th class="p-2 text-right">Total</th>
                                <th class="p-2 text-right">Paid</th>
                                <th class="p-2 text-right">
                                    <a href="{{ route('invoices.index', array_merge(request()->query(), ['sort'=>'due','direction'=> request('direction')==='asc' && request('sort')==='due' ? 'desc' : 'asc'])) }}">Due</a>
                                </th>
                                <th class="p-2 text-left">Created</th>
                                <th class="p-2 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($orders as $o)
                                @php
                                    $completed = (float)($o->payments()->where('status','completed')->sum('amount'));
                                    $refunded = (float)($o->payments()->where('status','refunded')->sum('amount'));
                                    $paid = max(0.0, $completed - $refunded);
                                    $due = max(0, (float)($o->total_cost ?? 0) - $paid);
                                @endphp
                                <tr class="border-b">
                                    <td class="p-2"><a class="text-blue-700 hover:underline" href="{{ route('orders.show', $o) }}">{{ $o->order_id }}</a></td>
                                    <td class="p-2">
                                        <div class="flex items-center gap-2">
                                            <span>{{ optional($o->customer)->name ?? '-' }}</span>
                                            @if(optional($o->customer)->is_vip)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-yellow-100 text-yellow-800">VIP</span>
                                            @endif
                                        </div>
                                        @if(!empty(optional($o->customer)->code))
                                            <div class="text-xs text-gray-500">Code: {{ optional($o->customer)->code }}</div>
                                        @endif
                                    </td>
                                    <td class="p-2 text-right">{{ number_format((float)($o->total_cost ?? 0), 2) }}</td>
                                    <td class="p-2 text-right">{{ number_format($paid, 2) }}</td>
                                    <td class="p-2 text-right">{{ number_format($due, 2) }}</td>
                                    <td class="p-2">{{ optional($o->created_at)->format('Y-m-d H:i') }}</td>
                                    <td class="p-2">
                                        @can('print_invoices')
                                            <a href="{{ route('orders.invoice', $o) }}" target="_blank" class="text-blue-600 hover:underline">View/Print</a>
                                        @endcan
                                        @can('export_invoices')
                                            <a href="{{ route('orders.invoice.pdf', $o) }}" target="_blank" class="ml-2 text-blue-600 hover:underline">PDF</a>
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
</x-app-layout>
