<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Units') }}
            </h2>
            @can('create_units')
                <x-create-button :href="route('units.create')" label="Add Unit" />
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
                            <input type="text" name="q" value="{{ request('q') }}" placeholder="Name" class="border rounded p-2 text-sm" />
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
                        @if(request()->hasAny(['per_page','q','sort','direction']))
                            <a href="{{ route('units.index') }}" class="text-sm text-gray-600 underline">Clear</a>
                        @endif
                        @include('partials.export-toolbar', ['route' => 'units.index'])
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-100">
                                @php $dir = request('direction','asc')==='asc'?'desc':'asc'; @endphp
                                <th class="p-2 text-left"><a href="{{ route('units.index', array_merge(request()->query(), ['sort' => 'name', 'direction' => request('sort')==='name' ? $dir : 'asc'])) }}" class="underline">Name</a></th>
                                <th class="p-2 text-left">Parent Unit</th>
                                <th class="p-2 text-left">Conversion Factor</th>
                                <th class="p-2 text-left"><a href="{{ route('units.index', array_merge(request()->query(), ['sort' => 'created_at', 'direction' => request('sort')==='created_at' ? $dir : 'asc'])) }}" class="underline">Created</a></th>
                                <th class="p-2 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($units as $unit)
                                <tr class="border-b">
                                    <td class="p-2">{{ $unit->name }}</td>
                                    <td class="p-2">{{ optional($unit->parentUnit)->name }}</td>
                                    <td class="p-2">{{ $unit->conversion_factor }}</td>
                                    <td class="p-2">{{ optional($unit->created_at)->toDateTimeString() }}</td>
                                    <td class="p-2 flex gap-3 items-center">
                                        @can('edit_units')
                                            <a href="{{ route('units.edit', $unit) }}" class="text-blue-600">Edit</a>
                                        @endcan
                                        @can('delete_units')
                                            <form action="{{ route('units.destroy', $unit) }}" method="POST" data-confirm="Delete this unit?" data-confirm-title="Please Confirm" data-confirm-ok="Delete" data-confirm-cancel="Cancel">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600">Delete</button>
                                            </form>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div class="text-sm text-gray-600">Showing {{ $units->firstItem() ?? 0 }} to {{ $units->lastItem() ?? 0 }} of {{ $units->total() }} results</div>
                    <div>{{ $units->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
