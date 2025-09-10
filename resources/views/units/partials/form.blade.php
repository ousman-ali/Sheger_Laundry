@props(['unit' => null, 'units', 'action', 'method'])
<form action="{{ $action }}" method="POST" class="space-y-4" x-data="{ parentId: '{{ old('parent_unit_id', $unit->parent_unit_id ?? '') }}', get hasParent(){ return this.parentId && this.parentId !== '' }, clearIfNoParent(){ if(!this.hasParent){ this.$refs.cf.value=''; } } }" x-init="clearIfNoParent()">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <div>
        <label for="name" class="block text-sm font-medium">Name</label>
        <input type="text" name="name" id="name" class="w-full border rounded p-2" value="{{ old('name', $unit->name ?? '') }}" required>
        @error('name')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
    </div>

    <div>
    <label for="parent_unit_id" class="block text-sm font-medium">Parent Unit</label>
    <p class="text-xs text-gray-500 mb-1">Select only when this is a sub-unit. Example: grams (child) to kg (parent) → Conversion Factor = 1000.</p>
    <select name="parent_unit_id" id="parent_unit_id" class="w-full border rounded p-2" x-model="parentId" @change="clearIfNoParent()">
            <option value="">None</option>
            @foreach($units as $u)
                <option value="{{ $u->id }}" @selected((int) old('parent_unit_id', $unit->parent_unit_id ?? '') === $u->id)>{{ $u->name }}</option>
            @endforeach
        </select>
        @error('parent_unit_id')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
    </div>

    <div>
        <div x-show="hasParent" x-cloak>
            <label for="conversion_factor" class="block text-sm font-medium">Conversion Factor</label>
            <input x-ref="cf" type="number" name="conversion_factor" step="0.0001" id="conversion_factor" class="w-full border rounded p-2" :required="hasParent" :disabled="!hasParent" value="{{ old('conversion_factor', $unit->conversion_factor ?? '') }}" placeholder="e.g., 1000 for grams to kg">
            <p class="text-xs text-gray-500 mt-1">How many child units equal 1 parent unit (e.g., 1000 grams = 1 kg → 1000).</p>
        </div>
        @error('conversion_factor')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
    </div>

    <div class="flex gap-2">
        <a href="{{ route('units.index') }}" class="px-4 py-2 rounded border">Cancel</a>
        <button type="submit" class="{{ $method === 'POST' ? 'bg-green-600' : 'bg-blue-600' }} text-white px-4 py-2 rounded">
            @if($method === 'POST') Create @else Update @endif
        </button>
    </div>
</form>
