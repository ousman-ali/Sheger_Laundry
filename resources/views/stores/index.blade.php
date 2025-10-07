<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Stores') }}</h2>
            @can('create_stores')
                <x-create-button :href="route('stores.create')" label="Add Store" />
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                @if (session('success'))
                    <div class="mb-4 text-green-700 bg-green-100 border border-green-200 rounded p-3">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="mb-4 text-red-700 bg-red-100 border border-red-200 rounded p-3">{{ session('error') }}</div>
                @endif
                @if ($errors->any())
                    <div class="mb-4 text-red-700 bg-red-100 border border-red-200 rounded p-3">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                    <form method="GET" class="flex flex-wrap items-end gap-2" onsubmit="this.page && (this.page.value=1);">
                        <div class="flex flex-col">
                            <label class="text-sm text-gray-700">Search</label>
                            <input type="text" name="q" value="{{ request('q') }}" placeholder="Name/Description" class="border rounded p-2 text-sm" />
                        </div>
                        <div class="flex flex-col">
                            <label class="text-sm text-gray-700">Type</label>
                            <select name="type" class="border rounded p-2 text-sm">
                                <option value="">All</option>
                                <option value="main" @selected(request('type')==='main')>Main</option>
                                <option value="sub" @selected(request('type')==='sub')>Sub</option>
                            </select>
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
                        @if(request()->hasAny(['per_page','q','type','sort','direction']))
                            <a href="{{ route('stores.index') }}" class="text-sm text-gray-600 underline">Clear</a>
                        @endif
                        @include('partials.export-toolbar', ['route' => 'stores.index'])
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-100">
                                @php $dir = request('direction','asc')==='asc'?'desc':'asc'; @endphp
                                <th class="p-2 text-left"><a href="{{ route('stores.index', array_merge(request()->query(), ['sort' => 'name', 'direction' => request('sort')==='name' ? $dir : 'asc'])) }}" class="underline">Name</a></th>
                                <th class="p-2 text-left"><a href="{{ route('stores.index', array_merge(request()->query(), ['sort' => 'type', 'direction' => request('sort')==='type' ? $dir : 'asc'])) }}" class="underline">Type</a></th>
                                <th class="p-2 text-left">Description</th>
                                <th class="p-2 text-left"><a href="{{ route('stores.index', array_merge(request()->query(), ['sort' => 'created_at', 'direction' => request('sort')==='created_at' ? $dir : 'asc'])) }}" class="underline">Created</a></th>
                                <th class="p-2 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($stores as $store)
                                <tr class="border-b">
                                    <td class="p-2">{{ $store->name }}</td>
                                    <td class="p-2">{{ ucfirst($store->type) }}</td>
                                    <td class="p-2">{{ $store->description }}</td>
                                    <td class="p-2">{{ optional($store->created_at)->toDateTimeString() }}</td>
                                    <td class="p-2 flex gap-3 items-center">
                                        @can('edit_stores')
                                            <a href="{{ route('stores.edit', $store) }}" 
                                                class="inline-flex items-center justify-center w-8 h-8 bg-green-100 hover:bg-green-200 text-green-600 rounded-md transition"
                                            >
                                                <x-heroicon-o-pencil class="w-5 h-5 text-green-600" />
                                            </a>
                                        @endcan
                                        @can('delete_stores')
                                            <form action="{{ route('stores.destroy', $store) }}" method="POST" data-confirm="Delete this store?" data-confirm-title="Please Confirm" data-confirm-ok="Delete" data-confirm-cancel="Cancel">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                    class="inline-flex items-center justify-center w-8 h-8 bg-red-100 hover:bg-red-200 text-red-600 rounded-md transition"
                                                >
                                                     <x-heroicon-o-trash class="w-5 h-5 text-red-600" />
                                                </button>
                                            </form>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div class="text-sm text-gray-600">Showing {{ $stores->firstItem() ?? 0 }} to {{ $stores->lastItem() ?? 0 }} of {{ $stores->total() }} results</div>
                    <div>{{ $stores->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
