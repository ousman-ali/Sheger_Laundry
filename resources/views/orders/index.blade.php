<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Orders') }}
            </h2>
            @can('create_orders')
                <x-create-button :href="route('orders.create')" label="Create Order" />
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-col gap-3 mb-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <form method="GET" class="grid grid-cols-1 md:grid-cols-7 gap-2 items-end" onsubmit="this.page && (this.page.value=1);">
                            <div class="flex flex-col">
                                <label class="text-sm text-gray-700">Search</label>
                                <input type="text" name="q" value="{{ request('q') }}" placeholder="Order ID, customer, operator" class="border rounded p-2 text-sm" />
                            </div>
                            <div class="flex flex-col">
                                <label class="text-sm text-gray-700">Status</label>
                                <select name="status" class="border rounded p-2 text-sm">
                                    <option value="">All</option>
                                    @foreach(['received','processing','washing','drying_steaming','ironing','packaging','ready_for_pickup','delivered','cancelled'] as $s)
                                        <option value="{{ $s }}" @selected(request('status')===$s)>{{ $s }}</option>
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
                                <label class="text-sm text-gray-700">Customer</label>
                                <select name="customer_id" class="border rounded p-2 text-sm">
                                    <option value="">All</option>
                                    @foreach(($customers ?? collect()) as $c)
                                        <option value="{{ $c->id }}" @selected((string)request('customer_id')===(string)$c->id)>{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex flex-col">
                                <label class="text-sm text-gray-700">Operator</label>
                                <select name="operator_id" class="border rounded p-2 text-sm">
                                    <option value="">All</option>
                                    @foreach(($operators ?? collect()) as $op)
                                        <option value="{{ $op->id }}" @selected((string)request('operator_id')===(string)$op->id)>{{ $op->name }}</option>
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
                            <div class="flex items-center gap-3 md:col-span-7">
                                <button class="bg-gray-800 text-white px-3 py-2 rounded text-sm">Apply</button>
                                @if(request()->filled('q') || request()->filled('status') || request()->filled('from_date') || request()->filled('to_date') || request()->filled('customer_id') || request()->filled('operator_id'))
                                    <a href="{{ route('orders.index') }}" class="text-sm text-gray-600 underline">Clear</a>
                                @endif
                                @include('partials.export-toolbar', ['route' => 'orders.index'])
                            </div>
                        </form>
                    </div>
                </div>
                @if($orders->isEmpty())
                    <div class="text-center py-16">
                        <div class="mx-auto w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18" /></svg>
                        </div>
                        @if(request()->filled('status') || request()->filled('from_date') || request()->filled('to_date') || request()->filled('customer_id') || request()->filled('operator_id'))
                            <p class="text-gray-700 text-lg mb-2">No orders match your filters.</p>
                            <p class="text-gray-500 mb-6">Try clearing the filters.</p>
                            <a href="{{ route('orders.index') }}" class="inline-block bg-gray-800 text-white px-4 py-2 rounded">Clear filters</a>
                        @else
                            <p class="text-gray-700 text-lg mb-2">No orders yet.</p>
                            <p class="text-gray-500 mb-6">Create your first order to get started.</p>
                            <a href="{{ route('orders.create') }}" class="inline-block border border-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-50">Create Order</a>
                        @endif
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-collapse">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="p-2 text-left">Order ID</th>
                                    <th class="p-2 text-left">Customer</th>
                                    <th class="p-2 text-left">Status</th>
                                    <th class="p-2 text-left">Total Cost</th>
                                    <th class="p-2 text-left">Assigned To</th>
                                    <th class="p-2 text-left">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($orders as $order)
                                    <tr class="border-b">
                                        <td class="p-2">{{ $order->order_id }}</td>
                                        <td class="p-2">
                                            <div class="flex items-center gap-2">
                                                <span>{{ optional($order->customer)->name ?? '-' }}</span>
                                                @if(optional($order->customer)->is_vip)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-yellow-100 text-yellow-800">VIP</span>
                                                @endif
                                            </div>
                                            @if(!empty(optional($order->customer)->code))
                                                <div class="text-xs text-gray-500">Code: {{ optional($order->customer)->code }}</div>
                                            @endif
                                        </td>
                                        <td class="p-2">
                                            <div>{{ $order->status }}</div>
                                            @php
                                                $labels = $order->remarkPresets->pluck('label')->all();
                                            @endphp
                                            @if(!empty($labels))
                                                <div class="text-xs text-gray-600">Notes: {{ implode(', ', $labels) }}</div>
                                            @endif
                                        </td>
                                        <td class="p-2">{{ number_format($order->total_cost, 2) }}</td>
                                        <td class="p-2">
                                            @php
                                                // Build a per-operator quantity breakdown across all services in the order (PHP 7.3-compatible).
                                                $breakdown = [];
                                                foreach ($order->orderItems as $it) {
                                                    foreach ($it->orderItemServices as $svc) {
                                                        $assigns = $svc->relationLoaded('assignments') ? $svc->assignments : $svc->assignments()->with('employee')->get();
                                                        $hasAssigns = $assigns instanceof \Illuminate\Support\Collection ? $assigns->isNotEmpty() : (bool)count($assigns);
                                                        if ($hasAssigns) {
                                                            foreach ($assigns as $a) {
                                                                $name = optional($a->employee)->name;
                                                                if (!$name) { continue; }
                                                                $q = isset($breakdown[$name]) ? $breakdown[$name] : 0;
                                                                $breakdown[$name] = $q + (float)$a->quantity;
                                                            }
                                                        } elseif ($svc->employee) {
                                                            // Legacy fallback: no assignment rows — use direct employee
                                                            $name = $svc->employee->name;
                                                            $q = isset($breakdown[$name]) ? $breakdown[$name] : 0;
                                                            $breakdown[$name] = $q + (float)$svc->quantity;
                                                        }
                                                    }
                                                }
                                                $parts = [];
                                                foreach ($breakdown as $n => $q) {
                                                    $parts[] = [$n, (float)$q];
                                                }
                                                $totalOperators = count($parts);
                                                $topParts = array_slice($parts, 0, 3);
                                                $top = array_map(function($pair){ return $pair[0] . ' × ' . number_format($pair[1], 2); }, $topParts);
                                                // Compute partial allocation badge: if any service has assigned < total but > 0
                                                $hasPartial = $order->orderItems->flatMap->orderItemServices->contains(function($svc){
                                                    $total = (float) $svc->quantity;
                                                    $assigned = (float) (method_exists($svc,'assignedQuantity') ? $svc->assignedQuantity() : 0);
                                                    return $assigned > 0 && $assigned < $total;
                                                });
                                                $allocatedText = '';
                                                if ($hasPartial) {
                                                    $sumTotal = (float) $order->orderItems->flatMap->orderItemServices->sum('quantity');
                                                    $sumAssigned = (float) $order->orderItems->flatMap->orderItemServices->sum(function($svc){ return method_exists($svc,'assignedQuantity') ? $svc->assignedQuantity() : 0; });
                                                    $allocatedText = number_format($sumAssigned,2) . ' / ' . number_format($sumTotal,2);
                                                }
                                            @endphp
                                            @if(empty($top))
                                                <span class="text-xs text-gray-500">Unassigned</span>
                                            @else
                                                <span class="text-xs">{{ implode(', ', $top) }}@if($totalOperators > 3) +{{ $totalOperators - 3 }} more @endif</span>
                                                @if($totalOperators > 3)
                                                    <span x-data="{open:false}" class="relative ml-1 align-middle">
                                                        <button type="button" class="text-[10px] underline text-gray-600" @click="open=!open">details</button>
                                                        <div x-show="open" @click.outside="open=false" x-cloak class="absolute z-20 mt-1 bg-white border border-gray-200 rounded shadow p-2 text-xs w-56 right-0">
                                                            <div class="font-medium mb-1">Assignees</div>
                                                            <ul class="space-y-0.5 max-h-60 overflow-auto">
                                                                @foreach($parts as $pair)
                                                                    <li>{{ $pair[0] }} × {{ number_format($pair[1],2) }}</li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    </span>
                                                @endif
                                                @if($hasPartial)
                                                    <span class="ml-2 text-[10px] px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 border border-amber-200 align-middle">Partial {{ $allocatedText }}</span>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="p-2">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <a href="{{ route('orders.show', $order) }}" class="text-blue-600">View</a>
                                                @can('edit_orders')
                                                    <a href="{{ route('orders.edit', $order) }}" class="text-blue-600">Edit</a>
                                                @endcan
                                                @can('edit_orders')
                                                <form action="{{ route('order-services.assign-orders') }}" method="POST" class="flex items-center gap-1">
                                                    @csrf
                                                    <input type="hidden" name="order_ids[]" value="{{ $order->id }}">
                                                    <select name="employee_id" class="border rounded p-1 text-sm">
                                                        <option value="">Assign to…</option>
                                                        @foreach(($operators ?? collect()) as $op)
                                                            <option value="{{ $op->id }}">{{ $op->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <input type="number" name="quantity" step="0.01" min="0.01" placeholder="Qty (optional)" class="border rounded p-1 w-28 text-sm" />
                                                    <button class="text-slate-700 underline text-sm" title="Assign entire order">Assign</button>
                                                </form>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div class="text-sm text-gray-600">
                            Showing
                            <span class="font-medium">{{ $orders->firstItem() ?? 0 }}</span>
                            to
                            <span class="font-medium">{{ $orders->lastItem() ?? 0 }}</span>
                            of
                            <span class="font-medium">{{ $orders->total() }}</span>
                            results
                        </div>
                        <div>
                            {{ $orders->links() }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>