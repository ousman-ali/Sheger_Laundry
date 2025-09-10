@props(['clothItem' => null, 'units', 'action', 'method'])
<form action="{{ $action }}" method="POST" class="space-y-4">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <div>
        <label for="name" class="block text-sm font-medium">Name</label>
        <input type="text" name="name" id="name" class="w-full border rounded p-2" value="{{ old('name', $clothItem->name ?? '') }}" required>
        @error('name')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="unit_id" class="block text-sm font-medium">Unit</label>
        <select name="unit_id" id="unit_id" class="w-full border rounded p-2" required>
            <option value="">Select unit</option>
            @foreach($units as $unit)
                <option value="{{ $unit->id }}" @selected((int) old('unit_id', $clothItem->unit_id ?? '') === $unit->id)>
                    {{ $unit->name }}
                </option>
            @endforeach
        </select>
        @error('unit_id')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="description" class="block text-sm font-medium">Description</label>
        <textarea name="description" id="description" class="w-full border rounded p-2">{{ old('description', $clothItem->description ?? '') }}</textarea>
        @error('description')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
    </div>

    <div class="flex gap-2">
        <a href="{{ route('cloth-items.index') }}" class="px-4 py-2 rounded border">Cancel</a>
        <button type="submit" class="{{ $method === 'POST' ? 'bg-green-600' : 'bg-blue-600' }} text-white px-4 py-2 rounded">
            @if($method === 'POST') Create @else Update @endif
        </button>
    </div>
</form>
