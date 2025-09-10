<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Stock Transfer') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                @if ($errors->any())
                    <div class="mb-4 p-3 border border-red-300 bg-red-50 text-red-700 rounded">
                        <ul class="list-disc list-inside text-sm">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('stock-transfers.update', $stockTransfer) }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium">From Store</label>
                            <select name="from_store_id" class="w-full border rounded p-2" required>
                                <option value="">Select</option>
                                @foreach ($stores as $s)
                                    <option value="{{ $s->id }}" {{ old('from_store_id', $stockTransfer->from_store_id) == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium">To Store</label>
                            <select name="to_store_id" class="w-full border rounded p-2" required>
                                <option value="">Select</option>
                                @foreach ($stores as $s)
                                    <option value="{{ $s->id }}" {{ old('to_store_id', $stockTransfer->to_store_id) == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Transfer Date</label>
                            <input type="date" name="transferred_at" value="{{ old('transferred_at', \Illuminate\Support\Carbon::parse($stockTransfer->transferred_at)->format('Y-m-d')) }}" class="w-full border rounded p-2" required />
                        </div>
                    </div>

                    <div id="items" class="space-y-4">
                        <h3 class="font-semibold">Items</h3>
                        <p class="text-xs text-gray-500">Unit must be compatible with the item's default unit. E.g., kg and grams are convertible.</p>

                        @php $oldItems = old('items'); @endphp

                        @if ($oldItems)
                            @foreach ($oldItems as $i => $it)
                                <div class="item grid grid-cols-1 md:grid-cols-12 gap-2 items-center">
                                    <div class="md:col-span-5">
                                        <select name="items[{{ $i }}][inventory_item_id]" class="border rounded p-2 w-full" required>
                                            <option value="">Select Item</option>
                                            @foreach ($inventoryItems as $inv)
                                                <option value="{{ $inv->id }}" {{ (int)($it['inventory_item_id'] ?? 0) === $inv->id ? 'selected' : '' }}>
                                                    {{ $inv->name }}
                                                    @php $avail = $availableByItem[$inv->id] ?? 0; @endphp
                                                </option>
                                            @endforeach
                                        </select>
                                        <p class="text-xs text-gray-500">Available: <span class="available" data-item="{{ (int)($it['inventory_item_id'] ?? 0) }}">{{ $availableByItem[(int)($it['inventory_item_id'] ?? 0)] ?? 0 }}</span></p>
                                    </div>
                                    <div class="md:col-span-3">
                                        <select name="items[{{ $i }}][unit_id]" class="border rounded p-2 w-full" required>
                                            <option value="">Select Unit</option>
                                            @foreach ($units as $u)
                                                <option value="{{ $u->id }}" {{ (int)($it['unit_id'] ?? 0) === $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="md:col-span-3">
                                        <input type="number" step="0.01" name="items[{{ $i }}][quantity]" value="{{ $it['quantity'] ?? '' }}" placeholder="Qty" class="border rounded p-2 w-full" required />
                                    </div>
                                    <div class="md:col-span-1 flex justify-end">
                                        <button type="button" class="remove-item text-red-600 hover:underline">Remove</button>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            @foreach ($stockTransfer->stockTransferItems as $i => $it)
                                <div class="item grid grid-cols-1 md:grid-cols-12 gap-2 items-center">
                                    <div class="md:col-span-5">
                                        <select name="items[{{ $i }}][inventory_item_id]" class="border rounded p-2 w-full" required>
                                            <option value="">Select Item</option>
                                            @foreach ($inventoryItems as $inv)
                                                <option value="{{ $inv->id }}" {{ $inv->id === $it->inventory_item_id ? 'selected' : '' }}>{{ $inv->name }}</option>
                                            @endforeach
                                        </select>
                                        <p class="text-xs text-gray-500">Available: <span class="available" data-item="{{ $it->inventory_item_id }}">{{ $availableByItem[$it->inventory_item_id] ?? 0 }}</span></p>
                                    </div>
                                    <div class="md:col-span-3">
                                        <select name="items[{{ $i }}][unit_id]" class="border rounded p-2 w-full" required>
                                            <option value="">Select Unit</option>
                                            @foreach ($units as $u)
                                                <option value="{{ $u->id }}" {{ $u->id === $it->unit_id ? 'selected' : '' }}>{{ $u->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="md:col-span-3">
                                        <input type="number" step="0.01" name="items[{{ $i }}][quantity]" value="{{ $it->quantity }}" placeholder="Qty" class="border rounded p-2 w-full" required />
                                    </div>
                                    <div class="md:col-span-1 flex justify-end">
                                        <button type="button" class="remove-item text-red-600 hover:underline">Remove</button>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>

                    <button id="add-item" type="button" class="bg-gray-100 border rounded px-3 py-2">Add another</button>

                    <div class="flex gap-2">
                        <a href="{{ route('stock-transfers.show', $stockTransfer) }}" class="px-4 py-2 rounded border">Cancel</a>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let i = (function() {
            const itemsWrap = document.getElementById('items');
            const rows = itemsWrap.querySelectorAll('.item').length;
            return rows > 0 ? rows - 1 : 0;
        })();

        document.getElementById('add-item').addEventListener('click', () => {
            i++;
            const wrap = document.getElementById('items');
            wrap.insertAdjacentHTML('beforeend', `
                <div class="item grid grid-cols-1 md:grid-cols-12 gap-2 items-center">
                    <div class="md:col-span-5">
                        <select name="items[${i}][inventory_item_id]" class="border rounded p-2 w-full" required>
                            <option value="">Select Item</option>
                            @foreach ($inventoryItems as $inv)
                                <option value="{{ $inv->id }}">{{ $inv->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500">Available: <span class="available" data-item="{{ $inv->id }}">{{ $availableByItem[$inv->id] ?? 0 }}</span></p>
                    </div>
                    <div class="md:col-span-3">
                        <select name="items[${i}][unit_id]" class="border rounded p-2 w-full" required>
                            <option value="">Select Unit</option>
                            @foreach ($units as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-3">
                        <input type="number" step="0.01" name="items[${i}][quantity]" placeholder="Qty" class="border rounded p-2 w-full" required />
                    </div>
                    <div class="md:col-span-1 flex justify-end">
                        <button type="button" class="remove-item text-red-600 hover:underline">Remove</button>
                    </div>
                </div>
            `);
        });

        document.addEventListener('click', (e) => {
            if (e.target && e.target.classList.contains('remove-item')) {
                const row = e.target.closest('.item');
                if (row) row.remove();
            }
        });
    </script>
</x-app-layout>
