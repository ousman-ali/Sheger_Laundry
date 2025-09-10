<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Request {{ $req->request_no }}</h2>
            <a href="{{ route('stock-out-requests.index') }}" class="px-3 py-2 rounded border">Back</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div><div class="text-xs text-gray-600">Store</div><div class="font-medium">{{ optional($req->store)->name }}</div></div>
                    <div><div class="text-xs text-gray-600">Status</div><div class="font-medium">{{ ucfirst($req->status) }}</div></div>
                    <div><div class="text-xs text-gray-600">Requested By</div><div class="font-medium">{{ optional($req->requestedBy)->name }}</div></div>
                    <div><div class="text-xs text-gray-600">Approved By</div><div class="font-medium">{{ optional($req->approvedBy)->name }}</div></div>
                    <div><div class="text-xs text-gray-600">Approved At</div><div class="font-medium">{{ optional($req->approved_at)->toDateTimeString() }}</div></div>
                    <div><div class="text-xs text-gray-600">Created</div><div class="font-medium">{{ optional($req->created_at)->toDateTimeString() }}</div></div>
                </div>

                @if($req->remarks)
                    <div>
                        <div class="text-xs text-gray-600">Remarks</div>
                        <div>{{ $req->remarks }}</div>
                    </div>
                @endif

                <div>
                    <h3 class="font-semibold mb-2">Items</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="text-left px-3 py-2">Item</th>
                                    <th class="text-left px-3 py-2">Unit</th>
                                    <th class="text-left px-3 py-2">Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($req->items as $it)
                                    <tr class="border-t">
                                        <td class="px-3 py-2">{{ optional($it->inventoryItem)->name }}</td>
                                        <td class="px-3 py-2">{{ optional($it->unit)->name }}</td>
                                        <td class="px-3 py-2">{{ number_format($it->quantity, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex gap-2">
                    @can('edit_stock_out_requests')
                        @if(in_array($req->status, ['draft','submitted']))
                            <form action="{{ route('stock-out-requests.cancel', $req) }}" method="POST" data-confirm="Cancel this request? This cannot be undone." data-confirm-title="Please Confirm" data-confirm-ok="Yes, cancel" data-confirm-cancel="No">@csrf<button class="px-3 py-2 rounded border">Cancel</button></form>
                        @endif
                    @endcan
                    @role('Admin')
                        @if($req->status==='submitted')
                            <form action="{{ route('stock-out-requests.approve', $req) }}" method="POST">@csrf<button class="px-3 py-2 rounded bg-emerald-600 text-white">Approve</button></form>
                            <form action="{{ route('stock-out-requests.reject', $req) }}" method="POST">@csrf<button class="px-3 py-2 rounded bg-red-600 text-white">Reject</button></form>
                        @endif
                    @endrole
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
