<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Urgency Tiers') }}</h2>
            @can('create_urgency_tiers')
                <x-create-button :href="route('urgency-tiers.create')" label="Add Urgency Tier" />
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
                            <input type="text" name="q" value="{{ request('q') }}" placeholder="Label" class="border rounded p-2 text-sm" />
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
                            <a href="{{ route('urgency-tiers.index') }}" class="text-sm text-gray-600 underline">Clear</a>
                        @endif
                        @include('partials.export-toolbar', ['route' => 'urgency-tiers.index'])
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-100">
                                @php $dir = request('direction','asc')==='asc'?'desc':'asc'; @endphp
                                <th class="p-2 text-left"><a href="{{ route('urgency-tiers.index', array_merge(request()->query(), ['sort' => 'label', 'direction' => request('sort')==='label' ? $dir : 'asc'])) }}" class="underline">Label</a></th>
                                <th class="p-2 text-left"><a href="{{ route('urgency-tiers.index', array_merge(request()->query(), ['sort' => 'duration_days', 'direction' => request('sort')==='duration_days' ? $dir : 'asc'])) }}" class="underline">Duration (days)</a></th>
                                <th class="p-2 text-left"><a href="{{ route('urgency-tiers.index', array_merge(request()->query(), ['sort' => 'multiplier', 'direction' => request('sort')==='multiplier' ? $dir : 'asc'])) }}" class="underline">Multiplier</a></th>
                                <th class="p-2 text-left"><a href="{{ route('urgency-tiers.index', array_merge(request()->query(), ['sort' => 'created_at', 'direction' => request('sort')==='created_at' ? $dir : 'asc'])) }}" class="underline">Created</a></th>
                                <th class="p-2 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($tiers as $t)
                                <tr class="border-b">
                                    <td class="p-2">{{ $t->label }}</td>
                                    <td class="p-2">{{ $t->duration_days }}</td>
                                    <td class="p-2">{{ number_format((float)$t->multiplier, 2) }}</td>
                                    <td class="p-2">{{ optional($t->created_at)->toDateTimeString() }}</td>
                                    <td class="p-2 flex gap-3 items-center">
                                        @can('edit_urgency_tiers')
                                            <a href="{{ route('urgency-tiers.edit', $t) }}" class="text-blue-600">Edit</a>
                                        @endcan
                                        @can('delete_urgency_tiers')
                                            <form action="{{ route('urgency-tiers.destroy', $t) }}" method="POST" data-confirm="Delete this urgency tier?" data-confirm-title="Please Confirm" data-confirm-ok="Delete" data-confirm-cancel="Cancel">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600">Delete</button>
                                            </form>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-4 text-center text-gray-500">No urgency tiers found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div class="text-sm text-gray-600">Showing {{ $tiers->firstItem() ?? 0 }} to {{ $tiers->lastItem() ?? 0 }} of {{ $tiers->total() }} results</div>
                    <div>{{ $tiers->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
