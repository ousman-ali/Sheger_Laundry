<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Stock Transfers') }}
            </h2>
            @can('create_stock_transfers')
                <x-create-button :href="route('stock-transfers.create')" label="New Transfer" />
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
                            <input type="text" name="q" value="{{ request('q') }}" placeholder="From/To store" class="border rounded p-2 text-sm" />
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
                        <button class="bg-gray-800 text-white px-3 py-2 rounded text-sm">Apply</button>
                        @if(request()->hasAny(['per_page','q','from_date','to_date','sort','direction']))
                            <a href="{{ route('stock-transfers.index') }}" class="text-sm text-gray-600 underline">Clear</a>
                        @endif
                        @include('partials.export-toolbar', ['route' => 'stock-transfers.index'])
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-100">
                                @php $dir = request('direction','desc')==='asc'?'desc':'asc'; @endphp
                                <th class="p-2 text-left">From</th>
                                <th class="p-2 text-left">To</th>
                                <th class="p-2 text-left"><a href="{{ route('stock-transfers.index', array_merge(request()->query(), ['sort' => 'transferred_at', 'direction' => request('sort')==='transferred_at' ? $dir : 'desc'])) }}" class="underline">Date</a></th>
                                <th class="p-2 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($stockTransfers as $st)
                                <tr class="border-b">
                                    <td class="p-2">{{ $st->fromStore->name }}</td>
                                    <td class="p-2">{{ $st->toStore->name }}</td>
                                    <td class="p-2">{{ $st->transferred_at }}</td>
                                    <td class="p-2 flex gap-3">
                                        <a href="{{ route('stock-transfers.show', $st) }}" 
                                            class="inline-flex items-center justify-center w-8 h-8 bg-blue-100 hover:bg-blue-200 text-blue-600 rounded-md transition"
                                        >
                                            <x-heroicon-o-eye class="w-5 h-5 text-blue-600" />
                                        </a>
                                        @can('edit_stock_transfers')
                                            <a href="{{ route('stock-transfers.edit', $st) }}" 
                                                class="inline-flex items-center justify-center w-8 h-8 bg-green-100 hover:bg-green-200 text-green-600 rounded-md transition"
                                            >
                                                <x-heroicon-o-pencil class="w-5 h-5 text-green-600" />
                                            </a>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div class="text-sm text-gray-600">Showing {{ $stockTransfers->firstItem() ?? 0 }} to {{ $stockTransfers->lastItem() ?? 0 }} of {{ $stockTransfers->total() }} results</div>
                    <div>{{ $stockTransfers->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
