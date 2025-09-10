@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto p-4">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-semibold">Inventory Stock</h1>
        @include('partials.export-toolbar', ['route' => 'inventory.stock'])
    </div>

    <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-3 mb-4">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search item/store..." class="border rounded px-3 py-2" />
        <select name="inventory_item_id" class="border rounded px-3 py-2">
            <option value="">All Items</option>
            @foreach ($items as $it)
                <option value="{{ $it->id }}" @selected(request('inventory_item_id')==$it->id)>{{ $it->name }}</option>
            @endforeach
        </select>
        <select name="store_id" class="border rounded px-3 py-2">
            <option value="">All Stores</option>
            @foreach ($stores as $st)
                <option value="{{ $st->id }}" @selected(request('store_id')==$st->id)>{{ $st->name }}</option>
            @endforeach
        </select>
        <select name="per_page" class="border rounded px-3 py-2">
            @php($pp = (int)request('per_page', session('inventory.stock.per_page', 10)))
            @foreach ([10,25,50,100] as $n)
                <option value="{{ $n }}" @selected($pp==$n)>{{ $n }}/page</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <button class="px-3 py-2 rounded bg-gray-800 text-white">Filter</button>
            <a href="{{ route('inventory.stock') }}" class="px-3 py-2 rounded border">Clear</a>
        </div>
    </form>

    @php($dir = request('direction')==='asc' ? 'desc' : 'asc')
    <div class="overflow-x-auto border rounded">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="p-2 text-left"><a href="{{ route('inventory.stock', array_merge(request()->query(), ['sort' => 'item', 'direction' => request('sort')==='item' ? $dir : 'asc'])) }}" class="underline">Item</a></th>
                    <th class="p-2 text-left"><a href="{{ route('inventory.stock', array_merge(request()->query(), ['sort' => 'store', 'direction' => request('sort')==='store' ? $dir : 'asc'])) }}" class="underline">Store</a></th>
                    <th class="p-2 text-right"><a href="{{ route('inventory.stock', array_merge(request()->query(), ['sort' => 'quantity', 'direction' => request('sort')==='quantity' ? $dir : 'desc'])) }}" class="underline">Quantity</a></th>
                    <th class="p-2 text-left">Unit</th>
                    <th class="p-2 text-right"><a href="{{ route('inventory.stock', array_merge(request()->query(), ['sort' => 'minimum_stock', 'direction' => request('sort')==='minimum_stock' ? $dir : 'asc'])) }}" class="underline">Min Stock</a></th>
                    <th class="p-2">Low?</th>
                    <th class="p-2 text-left"><a href="{{ route('inventory.stock', array_merge(request()->query(), ['sort' => 'created_at', 'direction' => request('sort')==='created_at' ? $dir : 'desc'])) }}" class="underline">Updated</a></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($stocks as $row)
                    @php($low = $row->quantity <= $row->item_minimum_stock)
                    <tr class="border-t {{ $low ? 'bg-red-50' : '' }}">
                        <td class="p-2">{{ $row->item_name }}</td>
                        <td class="p-2">{{ $row->store_name }}</td>
                        <td class="p-2 text-right">{{ number_format((float)$row->quantity, 2, '.', '') }}</td>
                        <td class="p-2 text-left">{{ $row->item_unit_name ?? '' }}</td>
                        <td class="p-2 text-right">{{ number_format((float)$row->item_minimum_stock, 2, '.', '') }}</td>
                        <td class="p-2">
                            @if ($low)
                                <span class="inline-flex items-center px-2 py-0.5 text-xs rounded bg-red-100 text-red-800">LOW</span>
                            @endif
                        </td>
                        <td class="p-2">{{ optional($row->updated_at)->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-4 text-center text-gray-500">No stock records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $stocks->links() }}
    </div>
</div>
@endsection
