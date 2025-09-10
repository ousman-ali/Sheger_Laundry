<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create Stock Transfer') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form action="{{ route('stock-transfers.store') }}" method="POST" class="space-y-4">
                    @csrf
                    @php
                        $unitRootIds = [];
                        foreach ($units as $u) {
                            $anc = $u->ancestry();
                            $root = end($anc);
                            $unitRootIds[$u->id] = $root ? $root->id : $u->id;
                        }
                        // Build inventory list with unit root
                        $inventoryList = [];
            foreach ($inventoryItems as $it) {
                            $anc = $it->unit ? $it->unit->ancestry() : [];
                            $root = $anc ? end($anc) : null;
                            $inventoryList[] = [
                                'id' => $it->id,
                                'name' => $it->name,
                                'unit' => $it->unit ? $it->unit->name : '',
                'unit_root' => $root ? $root->id : ($it->unit->id ?? null),
                'unit_id' => $it->unit_id,
                            ];
                        }
                    @endphp
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium">From Store</label>
                            <select name="from_store_id" class="w-full border rounded p-2" required>
                                <option value="">Select</option>
                                @foreach ($stores as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium">To Store</label>
                            <select name="to_store_id" class="w-full border rounded p-2" required>
                                <option value="">Select</option>
                                @foreach ($stores as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Transfer Date</label>
                            <input type="date" name="transferred_at" class="w-full border rounded p-2" required value="{{ old('transferred_at', now()->format('Y-m-d')) }}" />
                        </div>
                    </div>

                    <div id="items" class="space-y-4">
                        <h3 class="font-semibold">Items</h3>
                        <div class="item grid grid-cols-1 md:grid-cols-3 gap-2">
                            <select name="items[0][inventory_item_id]" class="border rounded p-2 item-select" required>
                                <option value="">Select Item</option>
                                @foreach ($inventoryItems as $it)
                                    @php
                                        $anc = $it->unit ? $it->unit->ancestry() : [];
                                        $root = $anc ? end($anc) : null;
                                    @endphp
                                    <option value="{{ $it->id }}" data-unit-root="{{ $root ? $root->id : ($it->unit->id ?? '') }}">{{ $it->name }}</option>
                                @endforeach
                            </select>
                            <select name="items[0][unit_id]" class="border rounded p-2 unit-select" required>
                                <option value="">Select Unit</option>
                                @foreach ($units as $u)
                                    <option value="{{ $u->id }}" data-root="{{ $unitRootIds[$u->id] ?? '' }}">{{ $u->name }}</option>
                                @endforeach
                            </select>
                            <input type="number" step="0.01" name="items[0][quantity]" placeholder="Qty" class="border rounded p-2" required />
                        </div>
                    </div>

                    <button id="add-item" type="button" class="bg-gray-100 border rounded px-3 py-2">Add another</button>

                    <div class="flex gap-2">
                        <a href="{{ route('stock-transfers.index') }}" class="px-4 py-2 rounded border">Cancel</a>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        @php
            $unitsForJs = [];
            foreach ($units as $u) {
                $unitsForJs[] = [
                    'id' => $u->id,
                    'name' => $u->name,
                    'root_id' => $unitRootIds[$u->id] ?? null,
                ];
            }
        @endphp
        const UNITS = @json($unitsForJs);
        const INVENTORY_ITEMS = @json($inventoryList);

        function populateUnitOptions(unitSelect, rootId) {
            const current = unitSelect.value;
            unitSelect.innerHTML = '<option value="">Select Unit</option>' + UNITS
                .filter(u => !rootId || u.root_id == rootId)
                .map(u => `<option value="${u.id}" data-root="${u.root_id}">${u.name}</option>`)
                .join('');
            if (current && [...unitSelect.options].some(o => o.value === current)) {
                unitSelect.value = current;
            }
        }

        function onItemChange(itemSelect) {
            const rootId = itemSelect.selectedOptions[0]?.dataset.unitRoot || '';
            const unitSelect = itemSelect.closest('.item')?.querySelector('.unit-select');
            if (unitSelect) {
                populateUnitOptions(unitSelect, rootId);
                const inv = INVENTORY_ITEMS.find(x => String(x.id) === String(itemSelect.value));
                if (inv && inv.unit_id && [...unitSelect.options].some(o => o.value === String(inv.unit_id))) {
                    unitSelect.value = String(inv.unit_id);
                }
            }
        }

        function buildItemOptions() {
            return '<option value="">Select Item</option>' + INVENTORY_ITEMS
                .map(it => `<option value="${it.id}" data-unit-root="${it.unit_root ?? ''}">${it.name}</option>`)
                .join('');
        }

        let i = 0;
        document.getElementById('add-item').addEventListener('click', () => {
            i++;
            const wrap = document.getElementById('items');
            wrap.insertAdjacentHTML('beforeend', `
                <div class="item grid grid-cols-1 md:grid-cols-3 gap-2">
                    <select name="items[${i}][inventory_item_id]" class="border rounded p-2 item-select" required></select>
                    <select name="items[${i}][unit_id]" class="border rounded p-2 unit-select" required>
                        <option value="">Select Unit</option>
                        @foreach ($units as $u)
                            <option value="{{ $u->id }}" data-root="{{ $unitRootIds[$u->id] ?? '' }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                    <input type="number" step="0.01" name="items[${i}][quantity]" placeholder="Qty" class="border rounded p-2" required />
                </div>
            `);
            const itemSelects = wrap.querySelectorAll('.item-select');
            const last = itemSelects[itemSelects.length - 1];
            if (last) {
                last.innerHTML = buildItemOptions();
                last.addEventListener('change', (e) => onItemChange(e.target));
            }
        });

        document.querySelectorAll('.item-select').forEach(sel => sel.addEventListener('change', (e) => onItemChange(e.target)));
        document.querySelectorAll('.item').forEach(row => {
            const itemSel = row.querySelector('.item-select');
            if (itemSel && itemSel.value) onItemChange(itemSel);
            if (itemSel && !itemSel.innerHTML.trim()) {
                itemSel.innerHTML = buildItemOptions();
            }
        });
    </script>
</x-app-layout>
