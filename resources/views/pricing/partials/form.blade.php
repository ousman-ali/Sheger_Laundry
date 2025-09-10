@props(['pricingTier' => null, 'clothItems', 'services', 'action', 'method'])
<form action="{{ $action }}" method="POST" class="space-y-4">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <div>
        <label for="cloth_item_id" class="block text-sm font-medium">Cloth Item</label>
        <select name="cloth_item_id" id="cloth_item_id" class="w-full border rounded p-2" required>
            <option value="">Select cloth item</option>
            @foreach ($clothItems as $item)
                <option value="{{ $item->id }}" @selected((int) old('cloth_item_id', $pricingTier->cloth_item_id ?? '') === $item->id)>
                    {{ $item->name }} ({{ $item->unit->name }})
                </option>
            @endforeach
        </select>
        @error('cloth_item_id')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="service_id" class="block text-sm font-medium">Service</label>
        <select name="service_id" id="service_id" class="w-full border rounded p-2" required>
            <option value="">Select service</option>
            @foreach ($services as $svc)
                <option value="{{ $svc->id }}" @selected((int) old('service_id', $pricingTier->service_id ?? '') === $svc->id)>
                    {{ $svc->name }}
                </option>
            @endforeach
        </select>
        @error('service_id')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="price" class="block text-sm font-medium">Price</label>
        <input type="number" step="0.01" name="price" id="price" class="w-full border rounded p-2" 
               value="{{ old('price', $pricingTier->price ?? '') }}" required>
        @error('price')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
    </div>

    <div class="flex gap-2">
        <a href="{{ route('pricing.index') }}" class="px-4 py-2 rounded border">Cancel</a>
        <button type="submit" class="{{ $method === 'POST' ? 'bg-green-600' : 'bg-blue-600' }} text-white px-4 py-2 rounded">
            @if($method === 'POST') Create @else Update @endif
        </button>
    </div>
</form>
