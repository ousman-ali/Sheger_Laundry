<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Payments') }}
            </h2>
            @can('create_payments')
                <x-create-button :href="route('payments.create')" label="Record Payment" />
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                    <form method="GET" class="flex flex-wrap items-end gap-2" onsubmit="this.page && (this.page.value=1);">
                        <div class="flex flex-col">
                            <label class="text-sm text-gray-700">Search</label>
                            <input type="text" name="q" value="{{ request('q') }}" placeholder="Order ID or user" class="border rounded p-2 text-sm" />
                        </div>
                        <div class="flex flex-col">
                            <label class="text-sm text-gray-700">Status</label>
                            <select name="status" class="border rounded p-2 text-sm">
                                <option value="">All</option>
                                @foreach(['pending','completed','refunded'] as $s)
                                    <option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>
                                @endforeach
                            </select>
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
                        @include('partials.export-toolbar', ['route' => 'payments.index'])
                        <button class="bg-gray-800 text-white px-3 py-2 rounded text-sm">Apply</button>
                        @if(request()->hasAny(['per_page','q','status','from_date','to_date','sort','direction']))
                            <a href="{{ route('payments.index') }}" class="text-sm text-gray-600 underline">Clear</a>
                        @endif
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="p-2 text-left">Order</th>
                                <th class="p-2 text-left">Amount</th>
                                <th class="p-2 text-left">Method</th>
                                <th class="p-2 text-left">Bank</th>
                                <th class="p-2 text-left">Status</th>
                                <th class="p-2 text-left">Waiver</th>
                                <th class="p-2 text-left">Paid At</th>
                                <th class="p-2 text-left">By</th>
                                <th class="p-2 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($payments as $p)
                                <tr class="border-b">
                                    <td class="p-2">
                                        <a class="text-blue-700 hover:underline" href="{{ route('payments.show', $p) }}">{{ $p->order->order_id }}</a>
                                    </td>
                                    <td class="p-2">{{ number_format($p->amount, 2) }}</td>
                                    <td class="p-2">{{ ucfirst($p->method ?? '-') }}</td>
                                    <td class="p-2">{{ optional($p->bank)->name }}</td>
                                    <td class="p-2">{{ ucfirst($p->status) }}</td>
                                    <td class="p-2">
                                        @if($p->requires_approval)
                                            <span class="inline-block px-2 py-0.5 text-xs rounded bg-amber-100 text-amber-800">Awaiting approval</span>
                                        @elseif($p->waived_penalty)
                                            <span class="inline-block px-2 py-0.5 text-xs rounded bg-blue-100 text-blue-800">Waived</span>
                                        @else
                                            <span class="inline-block px-2 py-0.5 text-xs rounded bg-gray-100 text-gray-700">None</span>
                                        @endif
                                    </td>
                                    <td class="p-2">{{ $p->paid_at }}</td>
                                    <td class="p-2">{{ optional($p->createdBy)->name }}</td>
                                    <td class="p-2">
                                        @can('edit_payments')
                                            <a href="{{ route('payments.edit', $p) }}" class="text-blue-600 hover:underline">Edit</a>
                                        @endcan
                                        @role('Admin')
                                            @if($p->requires_approval)
                                                <form method="POST" action="{{ route('payments.approve', $p) }}" class="inline">
                                                    @csrf
                                                    <button class="ml-2 text-emerald-700 hover:underline">Approve</button>
                                                </form>
                                            @endif
                                        @endrole
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div class="text-sm text-gray-600">Showing {{ $payments->firstItem() ?? 0 }} to {{ $payments->lastItem() ?? 0 }} of {{ $payments->total() }} results</div>
                    <div>{{ $payments->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
