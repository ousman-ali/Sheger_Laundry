<x-app-layout>
    <x-slot name="header">
        {{ __('Create Inventory Item') }}
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form action="{{ route('inventory.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium">Name</label>
                        <input type="text" name="name" class="w-full border rounded p-2" value="{{ old('name') }}" required>
                        @error('name')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Unit</label>
                        <select name="unit_id" class="w-full border rounded p-2" required>
                            <option value="">Select unit</option>
                            @foreach($units as $u)
                                <option value="{{ $u->id }}" @selected(old('unit_id')==$u->id)>{{ $u->name }}</option>
                            @endforeach
                        </select>
                        @error('unit_id')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Minimum Stock (optional)</label>
                        <input type="number" step="0.01" name="minimum_stock" class="w-full border rounded p-2" value="{{ old('minimum_stock') }}">
                        @error('minimum_stock')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex gap-2">
                        <button class="bg-blue-600 text-white px-4 py-2 rounded">Save</button>
                        <a href="{{ route('inventory.index') }}" class="px-4 py-2 rounded border">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
