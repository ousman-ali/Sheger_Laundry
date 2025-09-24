@props(['group' => null, 'users', 'clothItems', 'action', 'method'])

<form action="{{ $action }}" method="POST" class="space-y-4">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    {{-- Group Name --}}
    <div>
        <label for="name" class="block text-sm font-medium">Group Name</label>
        <input type="text" name="name" id="name"
               class="w-full border rounded p-2"
               value="{{ old('name', $group->name ?? '') }}" required>
        @error('name')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
    </div>

    {{-- Description --}}
    <div>
        <label for="description" class="block text-sm font-medium">Description</label>
        <textarea name="description" id="description"
                  class="w-full border rounded p-2">{{ old('description', $group->description ?? '') }}</textarea>
        @error('description')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
    </div>

    {{-- Assign User --}}
    <div>
        <label for="user_id" class="block text-sm font-medium">Assign User</label>
        <select name="user_id" id="user_id" class="w-full border rounded p-2" required>
            <option value="">Select a User</option>
            @foreach($users as $user)
                <option value="{{ $user->id }}"
                    @selected((int) old('user_id', $group->user_id ?? '') === $user->id)>
                    {{ $user->name }}
                </option>
            @endforeach
        </select>
        @error('user_id')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
    </div>

    {{-- Assign Cloth Items --}}
    <div>
        <label class="block text-sm font-medium mb-2">Assign Cloth Items</label>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
            @foreach($clothItems as $item)
                <label class="flex items-center space-x-2 border rounded p-2">
                    <input 
                        type="checkbox" 
                        name="cloth_items[]" 
                        value="{{ $item->id }}"
                        @checked(in_array($item->id, old('cloth_items', $group?->clothItems->pluck('id')->toArray() ?? [])))
                    >
                    <span>{{ $item->item_code }} - {{ $item->name }} - {{ $item->unit->name }}</span>
                </label>
            @endforeach
        </div>
        @error('cloth_items')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
    </div>

    {{-- Actions --}}
    <div class="flex gap-2">
        <a href="{{ route('clothing-groups.index') }}" class="px-4 py-2 rounded border">Cancel</a>
        <button type="submit"
                class="{{ $method === 'POST' ? 'bg-green-600' : 'bg-blue-600' }} text-white px-4 py-2 rounded">
            @if($method === 'POST') Create @else Update @endif
        </button>
    </div>
</form>
