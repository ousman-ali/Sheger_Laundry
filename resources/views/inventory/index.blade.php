<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Inventory Items') }}</h2>
            @can('create_inventory')
                <x-create-button :href="route('inventory.create')" label="Add Item" />
            @endcan
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                @if (session('success'))
                    <div class="mb-4 rounded border border-green-300 bg-green-50 text-green-800 px-4 py-2">
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div class="mb-4 rounded border border-red-300 bg-red-50 text-red-800 px-4 py-2">
                        {{ session('error') }}
                    </div>
                @endif
                <div class="flex items-start justify-between gap-4 mb-4">
                    <form method="GET" class="flex flex-wrap items-end gap-2" onsubmit="this.page && (this.page.value=1);">
                        <div>
                            <label class="block text-xs font-medium">Search</label>
                            <input type="text" name="q" value="{{ request('q') }}" placeholder="Name" class="border rounded p-2 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium">Unit</label>
                            <select name="unit_id" class="border rounded p-2 text-sm">
                                <option value="">All</option>
                                @foreach($units as $u)
                                    <option value="{{ $u->id }}" @selected((string)request('unit_id')===(string)$u->id)>{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium">Per Page</label>
                            <select name="per_page" class="border rounded p-2 text-sm">
                                @foreach([10,25,50,100] as $n)
                                    <option value="{{ $n }}" @selected((int)request('per_page',10)===$n)>Show {{ $n }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-end gap-2">
                            <button class="bg-gray-800 text-white px-3 py-2 rounded text-sm">Filter</button>
                            <a href="{{ route('inventory.index') }}" class="text-sm text-gray-600 underline">Clear</a>
                            <input type="hidden" name="sort" value="{{ request('sort','name') }}" />
                            <input type="hidden" name="direction" value="{{ request('direction','asc') }}" />
                            <input type="hidden" name="page" value="{{ request('page',1) }}" />
                        </div>
                        @include('partials.export-toolbar', ['route' => 'inventory.index'])
                    </form>
                    
                </div>
                <div class="overflow-x-auto">
                    @php($dir = request('direction','asc')==='asc'?'desc':'asc')
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b">
                                <th class="p-2 text-left"><a href="{{ route('inventory.index', array_merge(request()->query(), ['sort' => 'name', 'direction' => request('sort')==='name' ? $dir : 'asc'])) }}" class="underline">Name</a></th>
                                <th class="p-2 text-left">Unit</th>
                                <th class="p-2 text-left"><a href="{{ route('inventory.index', array_merge(request()->query(), ['sort' => 'minimum_stock', 'direction' => request('sort')==='minimum_stock' ? $dir : 'asc'])) }}" class="underline">Minimum Stock</a></th>
                                <th class="p-2 text-left"><a href="{{ route('inventory.index', array_merge(request()->query(), ['sort' => 'created_at', 'direction' => request('sort')==='created_at' ? $dir : 'asc'])) }}" class="underline">Created</a></th>
                                <th class="p-2 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $item)
                                <tr class="border-b">
                                    <td class="p-2">{{ $item->name }}</td>
                                    <td class="p-2">{{ optional($item->unit)->name }}</td>
                                    <td class="p-2">{{ $item->minimum_stock }}</td>
                                    <td class="p-2">{{ optional($item->created_at)->toDateTimeString() }}</td>
                                    <td class="p-2 flex items-center gap-3">
                                        @can('edit_inventory')
                                        <a href="{{ route('inventory.edit', $item) }}" class="text-blue-600">Edit</a>
                                        @endcan
                                        @can('delete_inventory')
                                        <form action="{{ route('inventory.destroy', $item) }}" method="POST" data-confirm="Delete this item?" data-confirm-title="Please Confirm" data-confirm-ok="Delete" data-confirm-cancel="Cancel" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600">Delete</button>
                                        </form>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr><td class="p-4 text-center text-gray-500" colspan="5">No items found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 flex items-center justify-between">
                    <div class="text-sm text-gray-600">Showing {{ $items->firstItem() ?? 0 }} to {{ $items->lastItem() ?? 0 }} of {{ $items->total() }} results</div>
                    <div>{{ $items->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
