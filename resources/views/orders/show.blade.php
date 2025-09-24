<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Order Details') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="mb-4">
                    <h3 class="font-semibold">Remarks</h3>
                    @php
                        $labels = $order->remarkPresets->pluck('label')->all();
                    @endphp
                    @if(!empty($labels))
                        <div class="mb-1 text-sm text-gray-700">Common: {{ implode(', ', $labels) }}</div>
                    @endif
                    <p>{{ $order->remarks }}</p>
                </div>


                <div class="mb-6" x-data="{ q: '' }">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="font-semibold">Items</h4>
                        <div class="flex items-center gap-2">
                            <input type="search" x-model="q" class="border rounded p-1.5 text-sm" placeholder="Quick find service..." title="Type to filter services by name" />
                        </div>
                    </div>
                    @can('edit_orders')
            <form action="{{ route('order-services.assign-orders') }}" method="POST" class="mb-3">
                        @csrf
                        <div class="flex items-center gap-2">
                            <input type="hidden" name="order_ids[]" value="{{ $order->id }}">
                            <select name="employee_id" class="border rounded p-1 text-sm">
                                <option value="">Assign employee to entire order...</option>
                                @foreach(($operators ?? collect()) as $emp)
                                    <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                                @endforeach
                            </select>
                <input type="number" name="quantity" step="0.01" min="0.01" placeholder="Qty (optional)" class="border rounded p-1 w-36 text-sm" />
                            <button class="text-xs bg-slate-700 text-white px-2 py-1 rounded">Assign whole order</button>
                        </div>
                    </form>
                    @endcan
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-collapse">
                            <thead class="sticky top-0 z-10">
                                <tr class="bg-gray-100">
                                    <th class="p-2 text-left">Cloth Item</th>
                                    <th class="p-2 text-left">Unit</th>
                                    <th class="p-2 text-left">Qty</th>
                                    <th class="p-2 text-left">Services</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($order->orderItems as $item)
                                    <tr class="border-b align-top">
                                        <td class="p-2">({{ $item->clothItem->item_code }}) {{ $item->clothItem->name }}</td>
                                        <td class="p-2">{{ $item->unit->name }}</td>
                                        <td class="p-2">{{ $item->quantity }}
                                            @php $itemLabels = $item->remarkPresets->pluck('label')->all(); @endphp
                                            @if(!empty($itemLabels))
                                                <div class="text-xs text-gray-600 mt-1">Notes: {{ implode(', ', $itemLabels) }}</div>
                                            @endif
                                            @if($item->remarks)
                                                <div class="text-xs text-gray-600 mt-1">{{ $item->remarks }}</div>
                                            @endif
                                        </td>
                                        <td class="p-2" x-data="{ open: true }">
                                            <div class="flex items-center justify-between mb-2">
                                                <div></div>
                                                <button type="button" class="text-xs text-blue-600 underline" @click="open=!open" x-text="open ? 'Hide services' : 'Show services'"></button>
                                            </div>
                                            <div x-show="open">
                                            @can('edit_orders')
                        <form action="{{ route('order-services.assign-items') }}" method="POST" class="mb-3">
                                                @csrf
                                                <div class="flex items-center gap-2">
                                                    <input type="hidden" name="order_item_ids[]" value="{{ $item->id }}">
                                                    <select name="employee_id" class="border rounded p-1 text-sm">
                                                        <option value="">Assign employee to this item...</option>
                                                        @foreach(\App\Models\User::role('Operator')->orderBy('name')->get() as $emp)
                                                            <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                                                        @endforeach
                                                    </select>
                            <input type="number" name="quantity" step="0.01" min="0.01" placeholder="Qty (optional)" class="border rounded p-1 w-36 text-sm" />
                                                    <button class="text-xs bg-slate-600 text-white px-2 py-1 rounded">Assign item</button>
                                                </div>
                                            </form>

                        <form action="{{ route('order-services.assign') }}" method="POST" class="mb-2">
                                                @csrf
                                                <div class="flex items-center gap-2">
                                                    <select name="employee_id" class="border rounded p-1 text-sm">
                                                        <option value="">Assign employee...</option>
                                                        @foreach(($operators ?? collect()) as $emp)
                                                            <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                                                        @endforeach
                                                    </select>
                            <input type="number" name="quantity" step="0.01" min="0.01" placeholder="Qty (optional)" class="border rounded p-1 w-36 text-sm" />
                                                    <button class="text-xs bg-slate-600 text-white px-2 py-1 rounded">Assign selected</button>
                                                </div>
                                                <div class="mt-2 grid grid-cols-1 gap-1">
                                                @foreach ($item->orderItemServices as $svc)
                                                    <label class="flex items-center gap-2 border rounded p-2">
                                                        <input type="checkbox" name="service_ids[]" value="{{ $svc->id }}" class="rounded">
                                                        @php
                                                            $assQty = $svc->quantity;
                                                            if(auth()->user() && auth()->user()->hasRole('Operator') && !auth()->user()->can('view_all_orders')){
                                                                $assQty = number_format($svc->assignedQuantityForEmployee(auth()->id()), 2);
                                                            }
                                                            $totalQty = (float) $svc->quantity;
                                                            $assignedTotal = (float) $svc->assignedQuantity();
                                                            $isPartial = $assignedTotal > 0 && $assignedTotal < $totalQty;
                                                            $statusLabel = $svc->status;
                                                            if ($svc->status === 'pending' && $assignedTotal >= $totalQty) { $statusLabel = 'assigned'; }
                                                            elseif ($svc->status === 'pending' && $isPartial) { $statusLabel = 'partial'; }
                                                        @endphp
                                                        <span class="text-sm">{{ $svc->service->name }} (qty {{ $assQty }})</span>
                                                        @if ($svc->urgencyTier)
                                                            <span class="text-xs text-gray-500">{{ $svc->urgencyTier->label }}</span>
                                                        @endif
                                                        <span class="ml-auto text-xs px-2 py-0.5 rounded bg-gray-100">{{ $statusLabel }}</span>
                                                        @if($isPartial)
                                                            <span class="text-xs text-gray-500 ml-2">{{ number_format($assignedTotal,2) }} / {{ number_format($totalQty,2) }}</span>
                                                        @endif
                                                        @if ($svc->employee)
                                                            <span class="text-xs text-gray-600">→ {{ $svc->employee->name }}</span>
                                                        @endif
                                                    </label>
                                                @endforeach
                                                </div>
                                            </form>
                                            @endcan

                                            @php
                                                $user = auth()->user();
                                                $visibleServices = collect($item->orderItemServices);
                                                if ($user && $user->hasRole('Operator') && !$user->can('view_all_orders')) {
                                                    $visibleServices = $visibleServices->filter(function($s) use ($user) {
                                                        // Visible if directly assigned employee or has an assignment row for this user
                                                        $hasOwnAssign = $s->assignments->contains('employee_id', $user->id) || $s->assignments()->where('employee_id', $user->id)->exists();
                                                        return ((int)($s->employee_id) === (int)$user->id) || $hasOwnAssign;
                                                    })->values();
                                                }
                                            @endphp
                                            @if(auth()->user() && (auth()->user()->hasRole('Admin')||auth()->user()->can('assign_service')))
                                                <div class="mt-2 grid grid-cols-1 gap-1">
                                                    @foreach ($item->orderItemServices as $svc)
                                                        <div class="text-xs text-gray-600">
                                                            <span class="font-medium">{{ $svc->service->name }}</span> — Assigned breakdown:
                                                            @php $parts = $svc->assignments()->with('employee')->get(); @endphp
                                                            @if($parts->isEmpty())
                                                                <span>none</span>
                                                            @else
                                                                <span>
                                                                    {{ $parts->map(fn($a)=> ($a->employee?->name ?? '—') . ' × ' . number_format((float)$a->quantity,2))->join(', ') }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                            @can('update_order_status')
                                            <form action="{{ route('order-services.status') }}" method="POST" class="mt-2">
                                                @csrf
                                                <div class="flex items-center gap-2">
                                                    <select name="status" class="border rounded p-1 text-sm">
                                                        <option value="assigned">assigned</option>
                                                        <option value="in_progress">in_progress</option>
                                                        <option value="completed">completed</option>
                                                        <option value="on_hold">on_hold</option>
                                                        <option value="cancelled">cancelled</option>
                                                    </select>
                                                    <button class="text-xs bg-blue-600 text-white px-2 py-1 rounded">Update status for selected</button>
                                                </div>
                                                <div class="mt-2 grid grid-cols-1 gap-1">
                                                @foreach ($visibleServices as $svc)
                                                    <label class="flex items-center gap-2 border rounded p-2" x-show="!q || (($el.dataset.svcName || '').includes(q.toLowerCase()))" data-svc-name="{{ \Illuminate\Support\Str::lower($svc->service->name) }}">
                                                        <input type="checkbox" name="service_ids[]" value="{{ $svc->id }}" class="rounded">
                                                        @php
                                                            $showMine = auth()->user() && auth()->user()->hasRole('Operator') && !auth()->user()->can('view_all_orders');
                                                            $totalQty = (float) $svc->quantity;
                                                            $assignedTotal = (float) $svc->assignedQuantity();
                                                            $isPartial = $assignedTotal > 0 && $assignedTotal < $totalQty;
                                                            $statusLabel = $svc->status;
                                                            if ($svc->status === 'pending' && $assignedTotal >= $totalQty) { $statusLabel = 'assigned'; }
                                                            elseif ($svc->status === 'pending' && $isPartial) { $statusLabel = 'partial'; }
                                                        @endphp
                                                        <span class="text-sm">
                                                            {{ $svc->service->name }}
                                                            @if($showMine)
                                                                @php $myQty = number_format($svc->assignedQuantityForEmployee(auth()->id()), 2); @endphp
                                                                <span class="text-xs text-gray-600">(my {{ $myQty }} / total {{ number_format($totalQty,2) }})</span>
                                                            @endif
                                                        </span>
                                                        <span class="ml-auto text-xs px-2 py-0.5 rounded bg-gray-100">{{ $statusLabel }}</span>
                                                        @if($isPartial)
                                                            <span class="text-xs text-gray-500 ml-2">{{ number_format($assignedTotal,2) }} / {{ number_format($totalQty,2) }}</span>
                                                        @endif
                                                    </label>
                                                @endforeach
                                                </div>
                                            </form>
                                            @else
                                                <div class="mt-2 grid grid-cols-1 gap-1">
                                                    @forelse ($visibleServices as $svc)
                                                        <div class="flex items-center gap-2 border rounded p-2" x-show="!q || (($el.dataset.svcName || '').includes(q.toLowerCase()))" data-svc-name="{{ \Illuminate\Support\Str::lower($svc->service->name) }}">
                                                            @php
                                                                $assQty = number_format($svc->assignedQuantityForEmployee(auth()->id()), 2);
                                                                $totalQty = (float) $svc->quantity;
                                                                $assignedTotal = (float) $svc->assignedQuantity();
                                                                $isPartial = $assignedTotal > 0 && $assignedTotal < $totalQty;
                                                                $statusLabel = $svc->status;
                                                                if ($svc->status === 'pending' && $assignedTotal >= $totalQty) { $statusLabel = 'assigned'; }
                                                                elseif ($svc->status === 'pending' && $isPartial) { $statusLabel = 'partial'; }
                                                            @endphp
                                                            <span class="text-sm">{{ $svc->service->name }} (my {{ $assQty }} / total {{ number_format($totalQty,2) }})</span>
                                                            <span class="ml-auto text-xs px-2 py-0.5 rounded bg-gray-100">{{ $statusLabel }}</span>
                                                            @if($isPartial)
                                                                <span class="text-xs text-gray-500 ml-2">{{ number_format($assignedTotal,2) }} / {{ number_format($totalQty,2) }}</span>
                                                            @endif
                                                            @php $myAssign = $svc->assignments->firstWhere('employee_id', auth()->id()); @endphp
                                                            @if($myAssign)
                                                                <form method="POST" action="{{ route('order-services.assignment-status') }}" class="ml-2 inline-flex items-center gap-1">
                                                                    @csrf
                                                                    <input type="hidden" name="assignment_ids[]" value="{{ $myAssign->id }}" />
                                                                    <select name="status" class="border rounded p-1 text-xs">
                                                                        @foreach(['assigned','in_progress','completed','on_hold','cancelled'] as $s)
                                                                            <option value="{{ $s }}" @selected($myAssign->status===$s)>{{ $s }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                    <button class="text-xs bg-blue-600 text-white px-2 py-1 rounded">Update</button>
                                                                </form>
                                                            @endif
                                                        </div>
                                                    @empty
                                                        <div class="text-sm text-gray-500">No tasks assigned to you for this item.</div>
                                                    @endforelse
                                                </div>
                                            @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    @can('update_order_status')
                    <form action="{{ route('orders.update-status', $order) }}" method="POST" class="flex items-center gap-2">
                        @csrf
                        @method('PATCH')
                        <select name="status" class="border rounded p-2">
                            <option value="received" @selected($order->status==='received')>received</option>
                            <option value="processing" @selected($order->status==='processing')>processing</option>
                            <option value="washing" @selected($order->status==='washing')>washing</option>
                            <option value="drying_steaming" @selected($order->status==='drying_steaming')>drying_steaming</option>
                            <option value="ironing" @selected($order->status==='ironing')>ironing</option>
                            <option value="packaging" @selected($order->status==='packaging')>packaging</option>
                            <option value="ready_for_pickup" @selected($order->status==='ready_for_pickup')>ready_for_pickup</option>
                            <option value="delivered" @selected($order->status==='delivered')>delivered</option>
                            <option value="cancelled" @selected($order->status==='cancelled')>cancelled</option>
                        </select>
                        <button class="bg-blue-600 text-white px-4 py-2 rounded" type="submit">Update Status</button>
                    </form>
                    @endcan
                    @can('edit_orders')
                        <a href="{{ route('orders.edit', $order) }}" class="text-blue-600">Edit</a>
                    @endcan
                    @can('print_orders')
                        <a href="{{ route('orders.invoice', $order) }}" class="ml-4 text-blue-600" target="_blank">Print Invoice</a>
                    @endcan
                    @can('create_payments')
                        <button type="button" class="ml-4 text-blue-600" @click="window.dispatchEvent(new CustomEvent('open-record-payment', { detail: { orderId: {{ $order->id }}, orderLabel: '{{ $order->order_id }} — {{ addslashes(optional($order->customer)->name) }}' } }))">Record Payment</button>
                    @endcan
                </div>

                @can('edit_orders')
                <div class="mt-6 border-t pt-4">
                    <h4 class="font-semibold mb-2">Penalties</h4>
                    @php
                        $itemizedPenalties = $order->itemPenalties()->latest()->get();
                        $hasPendingApprovals = $itemizedPenalties->where('requires_approval', true)->count() > 0;
                    @endphp
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <div class="border rounded">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="p-2 text-left">Linked Service</th>
                                            <th class="p-2 text-right">Amount</th>
                                            <th class="p-2 text-left">Status</th>
                                            <th class="p-2 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($itemizedPenalties as $pen)
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
                                                <td class="p-2 text-right">
                                                    @can('edit_orders')
                                                        @if(!$pen->waived)
                                                            <form action="{{ route('penalties.waive', $pen) }}" method="POST" class="inline-flex items-center gap-1">
                                                                @csrf
                                                                <input type="text" name="reason" class="border rounded p-1 text-xs" placeholder="Reason (optional)" />
                                                                <button class="text-xs text-blue-600">Waive</button>
                                                            </form>
                                                        @endif
                                                        @if($pen->requires_approval && auth()->user() && auth()->user()->hasRole('Admin'))
                                                            <form action="{{ route('penalties.approve', $pen) }}" method="POST" class="inline ml-2">
                                                                @csrf
                                                                <button class="text-xs text-green-700">Approve</button>
                                                            </form>
                                                        @endif
                                                        <form action="{{ route('penalties.destroy', $pen) }}" method="POST" class="inline ml-2" data-confirm="Remove this penalty?" data-confirm-title="Please Confirm" data-confirm-ok="Remove" data-confirm-cancel="Cancel">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button class="text-xs text-red-600">Delete</button>
                                                        </form>
                                                    @endcan
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td class="p-2" colspan="4">No penalties added.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div>
                            @can('edit_orders')
                                <div class="border rounded p-3">
                                    <div class="font-medium mb-2">Add Penalty</div>
                                    <form action="{{ route('orders.penalties.store', $order) }}" method="POST" class="space-y-2">
                                        @csrf
                                        <div>
                                            <label class="block text-sm text-gray-700">Service (optional)</label>
                                            <select name="order_item_service_id" class="border rounded w-full p-2 text-sm">
                                                <option value="">— None —</option>
                                                @foreach($order->orderItems as $it)
                                                    @foreach($it->orderItemServices as $svc)
                                                        <option value="{{ $svc->id }}">{{ $it->clothItem->name }} — {{ $svc->service->name }}</option>
                                                    @endforeach
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm text-gray-700">Amount (ETB)</label>
                                            <input type="number" step="0.01" min="0.01" name="amount" class="border rounded w-full p-2" required>
                                        </div>
                                        <div>
                                            <label class="block text-sm text-gray-700">Reason (optional)</label>
                                            <input type="text" name="reason" class="border rounded w-full p-2" placeholder="e.g., Lost item, Damage compensation">
                                        </div>
                                        <div class="text-right">
                                            <button class="bg-slate-700 text-white px-3 py-1.5 rounded text-sm">Add Penalty</button>
                                        </div>
                                    </form>
                                    @php
                                        $suggest = app(\App\Services\PaymentService::class)->suggestedAmountForOrder($order->id);
                                        $hasItemized = $itemizedPenalties->where('waived', false)->count() > 0;
                                        $autoPenalty = (float)($suggest['penalty'] ?? 0);
                                    @endphp
                                    @if(!$hasItemized && $autoPenalty > 0)
                                        <div class="mt-3 bg-amber-50 border border-amber-200 text-amber-800 rounded p-2 text-sm">
                                            Automatic penalty currently applies: <strong>{{ number_format($autoPenalty, 2) }} ETB</strong>.
                                            <form action="{{ route('orders.penalties.store', $order) }}" method="POST" class="inline">
                                                @csrf
                                                <input type="hidden" name="amount" value="{{ $autoPenalty }}" />
                                                <input type="hidden" name="reason" value="Converted from automatic penalty" />
                                                <button class="underline ml-1">Convert to fixed penalty line</button>
                                            </form>
                                        </div>
                                    @endif
                                </div>
                            @endcan
                        </div>
                    </div>
                </div>
                @endcan

                @canany(['view_payments','create_payments'])
                <div class="mt-6 border-t pt-4">
                    <h4 class="font-semibold mb-2">Payments <span class="text-xs text-gray-500">({{ strtoupper($order->paymentStatus()) }})</span></h4>
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-collapse text-sm">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="p-2 text-left">Paid At</th>
                                    <th class="p-2 text-left">Method</th>
                                    <th class="p-2 text-left">Status</th>
                                    <th class="p-2 text-left">Notes</th>
                                    <th class="p-2 text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($order->payments()->latest('paid_at')->get() as $p)
                                    <tr class="border-b">
                                        <td class="p-2">{{ optional($p->paid_at)->format('Y-m-d H:i') }}</td>
                                        <td class="p-2">{{ $p->method }}</td>
                                        <td class="p-2">{{ ucfirst($p->status) }}</td>
                                        <td class="p-2">{{ $p->notes }}</td>
                                        <td class="p-2 text-right">{{ number_format((float)$p->amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td class="p-2" colspan="5">No payments recorded.</td></tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                @php
                                    $suggest = app(\App\Services\PaymentService::class)->suggestedAmountForOrder($order->id);
                                    $base = (float)($suggest['base'] ?? 0);
                                    $penalty = (float)($suggest['penalty'] ?? 0);
                                    $total = (float)($suggest['total'] ?? (float)($order->total_cost ?? 0));
                                    $completed = (float)$order->payments()->where('status','completed')->sum('amount');
                                    $refunded = (float)$order->payments()->where('status','refunded')->sum('amount');
                                    $paid = max(0.0, $completed - $refunded);
                                    $due = max(0, $total - $paid);
                                @endphp
                                <tr>
                                    <th class="p-2 text-right" colspan="4">Subtotal</th>
                                    <th class="p-2 text-right">{{ number_format($base, 2) }}</th>
                                </tr>
                                <tr>
                                    <th class="p-2 text-right" colspan="4">Penalty</th>
                                    <th class="p-2 text-right">{{ number_format($penalty, 2) }}</th>
                                </tr>
                                <tr>
                                    <th class="p-2 text-right" colspan="4">Total</th>
                                    <th class="p-2 text-right">{{ number_format($total, 2) }}</th>
                                </tr>
                                <tr>
                                    <th class="p-2 text-right" colspan="4">Paid</th>
                                    <th class="p-2 text-right">{{ number_format($paid, 2) }}</th>
                                </tr>
                                <tr>
                                    <th class="p-2 text-right" colspan="4">Due</th>
                                    <th class="p-2 text-right">{{ number_format($due, 2) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                @endcanany
            </div>
        </div>
    </div>
    @can('create_payments')
        @include('payments._record_modal', ['orderId' => $order->id, 'orderLabel' => $order->order_id . ' — ' . optional($order->customer)->name, 'open' => false])
    @endcan
</x-app-layout>
