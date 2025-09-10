<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Stock-out Requests</h2>
            @can('create_stock_out_requests')
                <x-create-button :href="route('stock-out-requests.create')" label="New Request" />
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
                            <input type="text" name="q" value="{{ request('q') }}" placeholder="Req#, requester" class="border rounded p-2 text-sm" />
                        </div>
                        <div class="flex flex-col">
                            <label class="text-sm text-gray-700">Status</label>
                            <select name="status" class="border rounded p-2 text-sm">
                                <option value="">All</option>
                                @foreach(['draft','submitted','approved','rejected','cancelled'] as $s)
                                    <option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-col">
                            <label class="text-sm text-gray-700">Store</label>
                            <select name="store_id" class="border rounded p-2 text-sm">
                                <option value="">All</option>
                                @foreach(\App\Models\Store::orderBy('name')->get() as $st)
                                    <option value="{{ $st->id }}" @selected((int)request('store_id')===$st->id)>{{ $st->name }}</option>
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
                        @include('partials.export-toolbar', ['route' => 'stock-out-requests.index', 'base' => 'stock_out_requests'])
                        <button class="bg-gray-800 text-white px-3 py-2 rounded text-sm">Apply</button>
                        @if(request()->hasAny(['q','status','store_id','from_date','to_date']))
                            <a href="{{ route('stock-out-requests.index') }}" class="text-sm text-gray-600 underline">Clear</a>
                        @endif
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="p-2 text-left">Request#</th>
                                <th class="p-2 text-left">Store</th>
                                <th class="p-2 text-left">Status</th>
                                <th class="p-2 text-left">Requested By</th>
                                <th class="p-2 text-left">Approved By</th>
                                <th class="p-2 text-left">Approved At</th>
                                <th class="p-2 text-left">Created</th>
                                <th class="p-2 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($requests as $r)
                                <tr class="border-b">
                                    <td class="p-2">{{ $r->request_no }}</td>
                                    <td class="p-2">{{ optional($r->store)->name }}</td>
                                    <td class="p-2">{{ ucfirst($r->status) }}</td>
                                    <td class="p-2">{{ optional($r->requestedBy)->name }}</td>
                                    <td class="p-2">{{ optional($r->approvedBy)->name }}</td>
                                    <td class="p-2">{{ optional($r->approved_at)->toDateTimeString() }}</td>
                                    <td class="p-2">{{ optional($r->created_at)->toDateTimeString() }}</td>
                                    <td class="p-2 flex flex-wrap gap-2 items-center">
                                        <a href="{{ route('stock-out-requests.show', $r) }}" class="text-blue-600">View</a>
                                        @can('edit_stock_out_requests')
                                            @if(in_array($r->status, ['draft','rejected']))
                                                <form action="{{ route('stock-out-requests.submit', $r) }}" method="POST">
                                                    @csrf
                                                    <button class="text-slate-700 underline text-sm">Submit</button>
                                                </form>
                                            @endif
                                            @if(in_array($r->status, ['draft','submitted']))
                                                <form action="{{ route('stock-out-requests.cancel', $r) }}" method="POST" data-confirm="Cancel this request? This cannot be undone." data-confirm-title="Please Confirm" data-confirm-ok="Yes, cancel" data-confirm-cancel="No">
                                                    @csrf
                                                    <button class="text-slate-700 underline text-sm">Cancel</button>
                                                </form>
                                            @endif
                                        @endcan
                                        @role('Admin')
                                            @if($r->status==='submitted')
                                                <form action="{{ route('stock-out-requests.approve', $r) }}" method="POST">
                                                    @csrf
                                                    <button class="text-emerald-700 underline text-sm">Approve</button>
                                                </form>
                                                <form action="{{ route('stock-out-requests.reject', $r) }}" method="POST">
                                                    @csrf
                                                    <button class="text-red-700 underline text-sm">Reject</button>
                                                </form>
                                            @endif
                                        @endrole
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">{{ $requests->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
