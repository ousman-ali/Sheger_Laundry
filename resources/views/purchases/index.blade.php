<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Purchases') }}
            </h2>
            @can('create_purchases')
                <x-create-button :href="route('purchases.create')" label="Add Purchase" />
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
                            <input type="text" name="q" value="{{ request('q') }}" placeholder="Supplier, phone, address" class="border rounded p-2 text-sm" />
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
                            <a href="{{ route('purchases.index') }}" class="text-sm text-gray-600 underline">Clear</a>
                        @endif
                        @include('partials.export-toolbar', ['route' => 'purchases.index'])
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-100">
                                @php $dir = request('direction','desc')==='asc'?'desc':'asc'; @endphp
                                <th class="p-2 text-left">Supplier</th>
                                <th class="p-2 text-left"><a href="{{ route('purchases.index', array_merge(request()->query(), ['sort' => 'purchase_date', 'direction' => request('sort')==='purchase_date' ? $dir : 'desc'])) }}" class="underline">Date</a></th>
                                <th class="p-2 text-left"><a href="{{ route('purchases.index', array_merge(request()->query(), ['sort' => 'total_price', 'direction' => request('sort')==='total_price' ? $dir : 'desc'])) }}" class="underline">Total</a></th>
                                <th class="p-2 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($purchases as $purchase)
                                <tr class="border-b">
                                    <td class="p-2">{{ $purchase->supplier_name }}</td>
                                    <td class="p-2">{{ $purchase->purchase_date }}</td>
                                    <td class="p-2">{{ number_format($purchase->total_price, 2) }}</td>
                                    <td class="p-2 flex gap-3">
                                        <a href="{{ route('purchases.show', $purchase) }}" class="text-blue-600">View</a>
                                        @can('edit_purchases')
                                            <a href="{{ route('purchases.edit', $purchase) }}" class="text-blue-600">Edit</a>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div class="text-sm text-gray-600">Showing {{ $purchases->firstItem() ?? 0 }} to {{ $purchases->lastItem() ?? 0 }} of {{ $purchases->total() }} results</div>
                    <div>{{ $purchases->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
