<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Timeline</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <p class="text-sm text-gray-600 mb-4">Services assigned to you, latest updates first.</p>
                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="p-2 text-left">When</th>
                                <th class="p-2 text-left">Order</th>
                                <th class="p-2 text-left">Customer</th>
                                <th class="p-2 text-left">Close Item</th>
                                <th class="p-2 text-left">Service</th>
                                <th class="p-2 text-left">My Qty</th>
                                <th class="p-2 text-left">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($services as $svc)
                                <tr class="border-b">
                                    <td class="p-2 text-sm text-gray-600">{{ $svc->updated_at->diffForHumans() }}</td>
                                    <td class="p-2">{{ $svc->orderItem->order->order_id }}</td>
                                    <td class="p-2">{{ $svc->orderItem->order->customer->name }}</td>
                                    <td class="p-2">{{ $svc->orderItem->clothItem->name }}</td>
                                    <td class="p-2">{{ $svc->service->name }}</td>
                                    @php
                                        $mine = number_format($svc->assignedQuantityForEmployee(auth()->id()), 2);
                                        $total = number_format((float)$svc->quantity, 2);
                                        $myAssign = $svc->assignments->firstWhere('employee_id', auth()->id()) ?? $svc->assignments()->where('employee_id', auth()->id())->first();
                                    @endphp
                                    <td class="p-2">{{ $mine }} / {{ $total }}</td>
                                    <td class="p-2">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs px-2 py-0.5 rounded bg-gray-100">{{ $svc->status }}</span>
                                            @php $assignedTotal = (float)$svc->assignedQuantity(); $t = (float)$svc->quantity; @endphp
                                            @if($assignedTotal>0 && $assignedTotal<$t)
                                                <span class="text-xs text-gray-500">{{ number_format($assignedTotal,2) }} / {{ number_format($t,2) }}</span>
                                            @endif
                                        </div>
                                        @if($myAssign)
                                            @php
                                                $workflow = config('shebar.service_status_workflow');
                                                $allowed = $workflow[$myAssign->status] ?? [];
                                                $options = array_values(array_unique(array_merge([$myAssign->status], $allowed)));
                                            @endphp
                                            <form method="POST" action="{{ route('order-services.assignment-status') }}" class="inline-flex items-center gap-1 mt-1">
                                                @csrf
                                                <input type="hidden" name="assignment_ids[]" value="{{ $myAssign->id }}" />
                                                <select name="status" class="border rounded p-1 text-xs">
                                                    @foreach($options as $s)
                                                        <option value="{{ $s }}" @selected($myAssign->status===$s)>{{ $s }}</option>
                                                    @endforeach
                                                </select>
                                                <button class="text-xs bg-blue-600 text-white px-2 py-1 rounded">Update</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="mt-3">{{ $services->links() }}</div>
                </div>
                <div class="mt-4">
                    <a href="{{ route('operator.index', ['employee_id'=>auth()->id()]) }}" class="text-blue-600">Back to My tasks</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
