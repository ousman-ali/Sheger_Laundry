<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Banks</h2>
            @can('create_banks')
                <x-create-button :href="route('banks.create')" label="New Bank" />
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <form method="GET" class="flex items-center gap-2">
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search" class="border rounded p-2 text-sm" />
                        <select name="active" class="border rounded p-2 text-sm">
                            <option value="">All</option>
                            <option value="1" @selected(request('active')==='1')>Active</option>
                            <option value="0" @selected(request('active')==='0')>Inactive</option>
                        </select>
                        <button class="bg-gray-800 text-white px-3 py-2 rounded text-sm">Apply</button>
                    </form>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="p-2 text-left">Name</th>
                                <th class="p-2 text-left">Branch</th>
                                <th class="p-2 text-left">Active</th>
                                <th class="p-2 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($banks as $b)
                                <tr class="border-b">
                                    <td class="p-2">{{ $b->name }}</td>
                                    <td class="p-2">{{ $b->branch }}</td>
                                    <td class="p-2">{{ $b->is_active ? 'Yes' : 'No' }}</td>
                                    <td class="p-2">
                                        @can('edit_banks')
                                            <a href="{{ route('banks.edit', $b) }}" class="text-blue-600 hover:underline">Edit</a>
                                        @endcan
                                        @can('delete_banks')
                                            <form action="{{ route('banks.destroy', $b) }}" method="POST" class="inline" data-confirm="Delete this bank?" data-confirm-title="Please Confirm" data-confirm-ok="Delete" data-confirm-cancel="Cancel">
                                                @csrf
                                                @method('DELETE')
                                                <button class="ml-2 text-red-600 hover:underline">Delete</button>
                                            </form>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $banks->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
