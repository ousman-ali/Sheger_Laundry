<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Pricing Tiers') }}
            </h2>
            @can('create_pricing')
                <x-create-button :href="route('pricing.create')" label="Add Pricing" />
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                @if (session('success'))
                    <div class="mb-4 text-green-700 bg-green-100 border border-green-200 rounded p-3">
                        {{ session('success') }}
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

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                    <form method="GET" class="flex flex-wrap items-end gap-2" onsubmit="this.page && (this.page.value=1);">
                        <div class="flex flex-col">
                            <label class="text-sm text-gray-700">Search</label>
                            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cloth item or service" class="border rounded p-2 text-sm" />
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
                            <a href="{{ route('pricing.index') }}" class="text-sm text-gray-600 underline">Clear</a>
                        @endif
                        @include('partials.export-toolbar', ['route' => 'pricing.index'])
                    </form>
                </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full border-collapse">
                            <thead>
                                <tr class="bg-gray-100">
                                    @php $dir = request('direction','asc')==='asc'?'desc':'asc'; @endphp
                                    <th class="p-2 text-left"><a href="{{ route('pricing.index', array_merge(request()->query(), ['sort' => 'cloth_item_id', 'direction' => request('sort')==='cloth_item_id' ? $dir : 'asc'])) }}" class="underline">Cloth Item</a></th>
                                    <th class="p-2 text-left"><a href="{{ route('pricing.index', array_merge(request()->query(), ['sort' => 'service_id', 'direction' => request('sort')==='service_id' ? $dir : 'asc'])) }}" class="underline">Service</a></th>
                                    <th class="p-2 text-left"><a href="{{ route('pricing.index', array_merge(request()->query(), ['sort' => 'price', 'direction' => request('sort')==='price' ? $dir : 'asc'])) }}" class="underline">Price</a></th>
                                    <th class="p-2 text-left">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pricingTiers as $tier)
                    <tr class="border-b">
                        <td class="p-2">{{ $tier->clothItem->name }} ({{ $tier->clothItem->unit->name }})</td>
                        <td class="p-2">{{ $tier->service->name }}</td>
                        <td class="p-2">{{ number_format($tier->price, 2) }}</td>
                                        <td class="p-2 flex gap-3 items-center">
                                            @can('edit_pricing')
                                                <a href="{{ route('pricing.edit', $tier) }}" 
                                                    class="inline-flex items-center justify-center w-8 h-8 bg-green-100 hover:bg-green-200 text-green-600 rounded-md transition"
                                                >
                                                    <x-heroicon-o-pencil class="w-5 h-5 text-green-600" />
                                                </a>
                                            @endcan
                                            @can('delete_pricing')
                                                <form action="{{ route('pricing.destroy', $tier) }}" method="POST" data-confirm="Delete this pricing?" data-confirm-title="Please Confirm" data-confirm-ok="Delete" data-confirm-cancel="Cancel">
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
                    </div>
                <!-- Removed bulk update to prevent nested form issues -->

                <!-- Delete via JS fetch to avoid nested forms -->

                <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div class="text-sm text-gray-600">Showing {{ $pricingTiers->firstItem() ?? 0 }} to {{ $pricingTiers->lastItem() ?? 0 }} of {{ $pricingTiers->total() }} results</div>
                    <div>{{ $pricingTiers->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
