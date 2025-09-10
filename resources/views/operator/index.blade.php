<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Operator Dashboard</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" class="mb-4 grid md:grid-cols-5 gap-3">
                    <select name="status" class="border rounded p-2">
                        <option value="">All statuses</option>
                        @foreach(['pending','assigned','in_progress','completed','on_hold','cancelled'] as $s)
                            <option value="{{ $s }}" @selected(request('status')===$s)>{{ $s }}</option>
                        @endforeach
                    </select>
                    @can('view_all_orders')
                        <select name="employee_id" class="border rounded p-2">
                            <option value="">All employees</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" @selected(request('employee_id')==$emp->id)>{{ $emp->name }}</option>
                            @endforeach
                        </select>
                    @endcan
                    <a href="{{ route('operator.index', ['employee_id'=>auth()->id()]) }}" class="text-sm self-center text-blue-600">My tasks</a>
                    <button class="bg-gray-700 text-white px-4 py-2 rounded">Filter</button>
                    <a href="{{ route('operator.index') }}" class="text-sm text-blue-600 self-center">Reset</a>
                </form>

                @can('assign_service')
                <form action="{{ route('order-services.assign') }}" method="POST" class="mb-3">
                    @csrf
                    <div class="flex items-center gap-2">
                        <select name="employee_id" class="border rounded p-2">
                            <option value="">Assign employee...</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                            @endforeach
                        </select>
                        <button class="bg-slate-600 text-white px-3 py-2 rounded text-sm">Assign to selected</button>
                    </div>
                    <div id="selected-services-holder"></div>
                </form>
                @endcan

                <div class="mt-3">
                    <table class="min-w-full border-collapse">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="p-2"><input type="checkbox" id="check-all"></th>
                                    <th class="p-2 text-left">Order</th>
                                    <th class="p-2 text-left">Customer</th>
                                    <th class="p-2 text-left">Service</th>
                                    <th class="p-2 text-left">My Qty</th>
                                    <th class="p-2 text-left">Status</th>
                                    <th class="p-2 text-left">Employee</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($services as $svc)
                                    @php
                                        $currentOp = auth()->id();
                                        if (auth()->user() && \Illuminate\Support\Facades\Gate::allows('view_all_orders') && request()->filled('employee_id')) {
                                            $currentOp = (int) request('employee_id');
                                        }
                                        $assignRow = null;
                                        if ($currentOp) {
                                            $assignRow = $svc->relationLoaded('assignments')
                                                ? $svc->assignments->firstWhere('employee_id', $currentOp)
                                                : $svc->assignments()->where('employee_id', $currentOp)->first();
                                        }
                                    @endphp
                                    <tr class="border-b">
                                        <td class="p-2"><input type="checkbox" name="service_ids[]" value="{{ $svc->id }}" class="row-check" @if($assignRow) data-assign-id="{{ $assignRow->id }}" @endif></td>
                                        <td class="p-2">{{ $svc->orderItem->order->order_id }}</td>
                                        <td class="p-2">{{ $svc->orderItem->order->customer->name }}</td>
                                        <td class="p-2">{{ $svc->service->name }}</td>
                                        @php
                                            $mineQty = $assignRow ? (float)$assignRow->quantity : ($currentOp ? $svc->assignedQuantityForEmployee((int)$currentOp) : 0);
                                            $totalQty = (float) $svc->quantity;
                                        @endphp
                                        <td class="p-2">
                                            <span class="text-sm">{{ number_format($mineQty,2) }} / {{ number_format($totalQty,2) }}</span>
                                        </td>
                                        @php
                                            $totalQty = (float) $svc->quantity;
                                            $assignedTotal = (float) $svc->assignedQuantity();
                                            $isPartial = $assignedTotal > 0 && $assignedTotal < $totalQty;
                                            $statusLabel = $svc->status;
                                            if ($svc->status === 'pending' && $assignedTotal >= $totalQty) { $statusLabel = 'assigned'; }
                                            elseif ($svc->status === 'pending' && $isPartial) { $statusLabel = 'partial'; }
                                        @endphp
                                        <td class="p-2">
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs px-2 py-0.5 rounded bg-gray-100">{{ $statusLabel }}</span>
                                                @if($isPartial)
                                                    <span class="text-xs text-gray-500">{{ number_format($assignedTotal,2) }} / {{ number_format($totalQty,2) }}</span>
                                                @endif
                                            </div>
                                            <div class="mt-1">
                                                @if($assignRow)
                                                    @php
                                                        $workflow = config('shebar.service_status_workflow');
                                                        $allowed = $workflow[$assignRow->status] ?? [];
                                                        $options = array_values(array_unique(array_merge([$assignRow->status], $allowed)));
                                                    @endphp
                                                    <form action="{{ route('order-services.assignment-status') }}" method="POST" class="inline-flex items-center gap-1">
                                                        @csrf
                                                        <input type="hidden" name="assignment_ids[]" value="{{ $assignRow->id }}" />
                                                        <select name="status" class="border rounded p-1 text-xs">
                                                            @foreach($options as $s)
                                                                <option value="{{ $s }}" @selected($assignRow->status===$s)>{{ $s }}</option>
                                                            @endforeach
                                                        </select>
                                                        <button class="bg-blue-600 text-white px-2 py-1 rounded text-xs">Update</button>
                                                    </form>
                                                @else
                                                    <span class="text-xs text-gray-400">No personal assignment</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="p-2">
                                            @php
                                                $direct = optional($svc->employee)->name;
                                                $assignees = $svc->relationLoaded('assignments') ? $svc->assignments->pluck('employee.name')->filter()->unique()->values() : collect();
                                                $label = $direct ?: ($assignees->isNotEmpty() ? $assignees->join(', ') : '-');
                                            @endphp
                                            {{ $label }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="mt-2">{{ $services->links() }}</div>
                    </div>

                @can('assign_service')
                <form action="{{ route('order-services.assign-customers') }}" method="POST" class="mt-4">
                    @csrf
                    <div class="flex flex-wrap items-center gap-2">
                        <select name="employee_id" class="border rounded p-2">
                            <option value="">Assign to employee...</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                            @endforeach
                        </select>
                        <select name="customer_ids[]" multiple class="border rounded p-2">
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                        <button class="bg-slate-500 text-white px-3 py-2 rounded text-sm">Assign by customers</button>
                        <a href="{{ route('operator.my') }}" class="text-blue-600 text-sm">My timeline</a>
                    </div>
                </form>
                @endcan

    <form action="{{ route('order-services.assignment-status') }}" method="POST" class="mt-4">
                    @csrf
                    <div class="flex items-center gap-2">
            <select name="status" class="border rounded p-2">
                            @foreach(['assigned','in_progress','completed','on_hold','cancelled'] as $s)
                                <option value="{{ $s }}">{{ $s }}</option>
                            @endforeach
                        </select>
            <button class="bg-blue-600 text-white px-3 py-2 rounded text-sm">Update status for selected</button>
                    </div>
            <div id="selected-holder"></div>
                </form>

            </div>
        </div>
    </div>

    <script>
        const checkAll = document.getElementById('check-all');
        checkAll && checkAll.addEventListener('change', () => {
            document.querySelectorAll('.row-check').forEach(cb => cb.checked = checkAll.checked);
            syncSelected();
        });
        document.addEventListener('change', (e)=>{
            if (e.target.classList && e.target.classList.contains('row-check')) { syncSelected(); }
        });
        function syncSelected(){
            const holder = document.getElementById('selected-holder');
            if (!holder) return;
            holder.innerHTML = '';
            const holderServices = document.getElementById('selected-services-holder');
            if (holderServices) holderServices.innerHTML = '';
            document.querySelectorAll('.row-check:checked').forEach(cb => {
                const assignId = cb.getAttribute('data-assign-id');
                if (!assignId) return; // only post assignment IDs we can identify
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'assignment_ids[]';
                input.value = assignId;
                holder.appendChild(input);
                if (holderServices) {
                    const svcInput = document.createElement('input');
                    svcInput.type = 'hidden';
                    svcInput.name = 'service_ids[]';
                    svcInput.value = cb.value;
                    holderServices.appendChild(svcInput);
                }
            });
        }
    </script>
</x-app-layout>
