<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Customers') }}
            </h2>
            @can('create_customers')
                <x-create-button :href="route('customers.create')" label="Add Customer" />
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
                            <input type="text" name="q" value="{{ request('q') }}" placeholder="Name, phone, code, address" class="border rounded p-2 text-sm" />
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
                            <a href="{{ route('customers.index') }}" class="text-sm text-gray-600 underline">Clear</a>
                        @endif
                        @include('partials.export-toolbar', ['route' => 'customers.index'])
                    </form>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-100">
                                @php
                                    $dir = request('direction', 'asc') === 'asc' ? 'desc' : 'asc';
                                @endphp
                                <th class="p-2 text-left">
                                    <a href="{{ route('customers.index', array_merge(request()->query(), ['sort' => 'name', 'direction' => request('sort')==='name' ? $dir : 'asc'])) }}" class="underline">Name</a>
                                </th>
                                    <th class="p-2 text-left">
                                        <a href="{{ route('customers.index', array_merge(request()->query(), ['sort' => 'code', 'direction' => request('sort')==='code' ? $dir : 'asc'])) }}" class="underline">Code</a>
                                    </th>
                                <th class="p-2 text-left">
                                    <a href="{{ route('customers.index', array_merge(request()->query(), ['sort' => 'phone', 'direction' => request('sort')==='phone' ? $dir : 'asc'])) }}" class="underline">Phone</a>
                                </th>
                                <th class="p-2 text-left">
                                    <a href="{{ route('customers.index', array_merge(request()->query(), ['sort' => 'created_at', 'direction' => request('sort')==='created_at' ? $dir : 'asc'])) }}" class="underline">Created</a>
                                </th>
                                <th class="p-2 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($customers as $customer)
                                <tr class="border-b">
                                    <td class="p-2">{{ $customer->name }}</td>
                                        <td class="p-2">{{ $customer->code ?? '-' }}@if($customer->is_vip) <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded text-xs bg-yellow-100 text-yellow-800">VIP</span>@endif</td>
                                    <td class="p-2">{{ $customer->phone }}</td>
                                    <td class="p-2">{{ optional($customer->created_at)->format('Y-m-d') }}</td>
                                    <td class="p-2">
                                        @can('edit_customers')
                                            <a href="{{ route('customers.edit', $customer) }}" 
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
                    <div class="text-sm text-gray-600">
                        Showing
                        <span class="font-medium">{{ $customers->firstItem() ?? 0 }}</span>
                        to
                        <span class="font-medium">{{ $customers->lastItem() ?? 0 }}</span>
                        of
                        <span class="font-medium">{{ $customers->total() }}</span>
                        results
                    </div>
                    <div>
                        {{ $customers->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>