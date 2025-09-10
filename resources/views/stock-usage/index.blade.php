<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Stock Usage') }}
            </h2>
            @can('create_stock_usage')
                <x-create-button :href="route('stock-usage.create')" label="Record Usage" />
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
                            <input type="text" name="q" value="{{ request('q') }}" placeholder="Item, store, operation" class="border rounded p-2 text-sm" />
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
                        <div class="flex flex-col">
                            <label class="text-sm text-gray-700">Per page</label>
                            <select name="per_page" class="border rounded p-2 text-sm">
                                @foreach([10,25,50,100] as $n)
                                    <option value="{{ $n }}" @selected((int)request('per_page',10)===$n)>Show {{ $n }}</option>
                                @endforeach
                            </select>
                        </div>
                        @include('partials.export-toolbar', ['route' => 'stock-usage.index'])
                        <button class="bg-gray-800 text-white px-3 py-2 rounded text-sm">Apply</button>
                        @if(request()->hasAny(['per_page','q','store_id','inventory_item_id','from_date','to_date','sort','direction']))
                            <a href="{{ route('stock-usage.index') }}" class="text-sm text-gray-600 underline">Clear</a>
                        @endif
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="p-2 text-left">Item</th>
                                <th class="p-2 text-left">Store</th>
                                <th class="p-2 text-left">Unit</th>
                                <th class="p-2 text-left">Qty Used</th>
                                <th class="p-2 text-left">Operation</th>
                                <th class="p-2 text-left">Date</th>
                                <th class="p-2 text-left">By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($stockUsage as $u)
                                <tr class="border-b">
                                    <td class="p-2">{{ $u->inventoryItem->name }}</td>
                                    <td class="p-2">{{ $u->store->name }}</td>
                                    <td class="p-2">{{ $u->unit->name }}</td>
                                    <td class="p-2">{{ number_format($u->quantity_used, 2) }}</td>
                                    <td class="p-2">{{ ucfirst($u->operation_type) }}</td>
                                    <td class="p-2">{{ $u->usage_date }}</td>
                                    <td class="p-2">{{ optional($u->createdBy)->name }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div class="text-sm text-gray-600">Showing {{ $stockUsage->firstItem() ?? 0 }} to {{ $stockUsage->lastItem() ?? 0 }} of {{ $stockUsage->total() }} results</div>
                    <div>{{ $stockUsage->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
