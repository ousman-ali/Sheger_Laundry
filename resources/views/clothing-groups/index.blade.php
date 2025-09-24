<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Clothing Groups') }}
            </h2>
            @can('create_clothing_groups')
                <x-create-button :href="route('clothing-groups.create')" label="Add Clothing Group" />
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                
                {{-- Flash messages --}}
                @if (session('success'))
                    <div class="mb-4 text-green-700 bg-green-100 border border-green-200 rounded p-3">
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div class="mb-4 text-red-700 bg-red-100 border border-red-200 rounded p-3">
                        {{ session('error') }}
                    </div>
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

                {{-- Filter + Search --}}
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                    <form method="GET" class="flex flex-wrap items-end gap-2" onsubmit="this.page && (this.page.value=1);">
                        <div class="flex flex-col">
                            <label class="text-sm text-gray-700">Search</label>
                            <input type="text" name="q" value="{{ request('q') }}" placeholder="Name or description" class="border rounded p-2 text-sm" />
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
                            <a href="{{ route('clothing-groups.index') }}" class="text-sm text-gray-600 underline">Clear</a>
                        @endif
                        @include('partials.export-toolbar', ['route' => 'clothing-groups.index'])
                    </form>
                </div>

                {{-- Table --}}
                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-100">
                                @php $dir = request('direction','asc')==='asc'?'desc':'asc'; @endphp
                                <th class="p-2 text-left">
                                    <a href="{{ route('clothing-groups.index', array_merge(request()->query(), ['sort' => 'name', 'direction' => request('sort')==='name' ? $dir : 'asc'])) }}" class="underline">Group Name</a>
                                </th>
                                <th class="p-2 text-left">Assigned User</th>
                                <th class="p-2 text-left">Cloth Items</th>
                                <th class="p-2 text-left">Description</th>
                                <th class="p-2 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($clothingGroups as $group)
                                <tr class="border-b">
                                    <td class="p-2">{{ $group->name }}</td>
                                    <td class="p-2">{{ optional($group->user)->name }}</td>
                                    <td class="p-2">
                                        {{ $group->clothItems->pluck('name')->implode(', ') }}
                                    </td>
                                    <td class="p-2">{{ \Illuminate\Support\Str::limit($group->description, 80) }}</td>
                                    <td class="p-2 flex gap-3 items-center">
                                        @can('edit_clothing_groups')
                                            <a href="{{ route('clothing-groups.edit', $group) }}" class="text-blue-600">Edit</a>
                                        @endcan
                                        @can('delete_clothing_groups')
                                            <form action="{{ route('clothing-groups.destroy', $group) }}" method="POST" data-confirm="Delete this clothing group?" data-confirm-title="Please Confirm" data-confirm-ok="Delete" data-confirm-cancel="Cancel">
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

                {{-- Pagination --}}
                <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div class="text-sm text-gray-600">
                        Showing {{ $clothingGroups->firstItem() ?? 0 }} to {{ $clothingGroups->lastItem() ?? 0 }} of {{ $clothingGroups->total() }} results
                    </div>
                    <div>{{ $clothingGroups->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
