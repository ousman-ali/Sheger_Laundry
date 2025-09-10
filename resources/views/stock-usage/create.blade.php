<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Record Stock Usage') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form action="{{ route('stock-usage.store') }}" method="POST" class="space-y-4">
                    @csrf
                    @php
                        // Precompute unit root ids for client-side filtering
                        $unitRootIds = [];
                        foreach (\App\Models\Unit::orderBy('name')->get() as $u) {
                            $anc = $u->ancestry();
                            $root = end($anc);
                            $unitRootIds[$u->id] = $root ? $root->id : $u->id;
                        }
                        // Inventory list for JS
                        $inventoryList = [];
                        foreach (\App\Models\InventoryItem::with('unit')->get() as $it) {
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
                        // Units for JS
                        $unitsForJs = [];
                        foreach (\App\Models\Unit::orderBy('name')->get() as $u) {
                            $unitsForJs[] = [
                                'id' => $u->id,
                                'name' => $u->name,
                                'root_id' => $unitRootIds[$u->id] ?? null,
                            ];
                        }
                    @endphp
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium">Store</label>
                            <select name="store_id" class="w-full border rounded p-2" required>
                                <option value="">Select Store</option>
                                @foreach(\App\Models\Store::orderBy('name')->get() as $st)
                                    <option value="{{ $st->id }}">{{ $st->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Usage Date</label>
                            <input type="datetime-local" name="usage_date" class="w-full border rounded p-2" required value="{{ old('usage_date', now()->format('Y-m-d\\TH:i')) }}" />
                        </div>
                    </div>

                    <div id="items" class="space-y-4">
                        <h3 class="font-semibold">Items</h3>
                        <div class="item grid grid-cols-1 md:grid-cols-5 gap-2">
                            <select name="items[0][inventory_item_id]" class="border rounded p-2 item-select" required>
                                <option value="">Select Item</option>
                                @foreach (\App\Models\InventoryItem::with('unit')->get() as $it)
                                    @php
                                        $anc = $it->unit ? $it->unit->ancestry() : [];
                                        $root = $anc ? end($anc) : null;
                                    @endphp
                                    <option value="{{ $it->id }}" data-unit-root="{{ $root ? $root->id : ($it->unit->id ?? '') }}">{{ $it->name }} ({{ $it->unit->name }})</option>
                                @endforeach
                            </select>
                            <select name="items[0][unit_id]" class="border rounded p-2 unit-select">
                                <option value="">Item Unit</option>
                                @foreach (\App\Models\Unit::orderBy('name')->get() as $u)
                                    <option value="{{ $u->id }}" data-root="{{ $unitRootIds[$u->id] ?? '' }}">{{ $u->name }}</option>
                                @endforeach
                            </select>
                            <input type="number" step="0.01" name="items[0][quantity_used]" placeholder="Qty Used" class="border rounded p-2" required />
                            <select name="items[0][operation_type]" class="border rounded p-2" required>
                                <option value="">Operation</option>
                                @foreach (['washing','drying','ironing','packaging','other'] as $op)
                                    <option value="{{ $op }}">{{ ucfirst($op) }}</option>
                                @endforeach
                            </select>
                            <div class="flex items-center text-gray-500">&nbsp;</div>
                        </div>
                    </div>

                    <button id="add-item" type="button" class="bg-gray-100 border rounded px-3 py-2">Add another</button>

                    <div class="flex gap-2">
                        <a href="{{ route('stock-usage.index') }}" class="px-4 py-2 rounded border">Cancel</a>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const UNITS = @json($unitsForJs);
        const INVENTORY_ITEMS = @json($inventoryList);

        function populateUnitOptions(unitSelect, rootId) {
            const current = unitSelect.value;
            unitSelect.innerHTML = '<option value="">Item Unit</option>' + UNITS
                .filter(u => !rootId || u.root_id == rootId)
                .map(u => `<option value="${u.id}" data-root="${u.root_id}">${u.name}</option>`)
                .join('');
            if (current && [...unitSelect.options].some(o => o.value === current)) {
                unitSelect.value = current;
            }
        }

        function buildItemOptions() {
            return '<option value="">Select Item</option>' + INVENTORY_ITEMS
                .map(it => `<option value="${it.id}" data-unit-root="${it.unit_root ?? ''}">${it.name} (${it.unit})</option>`)
                .join('');
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

        let i = 0;
        document.getElementById('add-item').addEventListener('click', () => {
            i++;
            const wrap = document.getElementById('items');
            wrap.insertAdjacentHTML('beforeend', `
                <div class="item grid grid-cols-1 md:grid-cols-5 gap-2">
                    <select name="items[${i}][inventory_item_id]" class="border rounded p-2 item-select" required></select>
                    <select name="items[${i}][unit_id]" class="border rounded p-2 unit-select">
                        <option value="">Item Unit</option>
                    </select>
                    <input type="number" step="0.01" name="items[${i}][quantity_used]" placeholder="Qty Used" class="border rounded p-2" required />
                    <select name="items[${i}][operation_type]" class="border rounded p-2" required>
                        <option value="">Operation</option>
                        <option value="washing">Washing</option>
                        <option value="drying">Drying</option>
                        <option value="ironing">Ironing</option>
                        <option value="packaging">Packaging</option>
                        <option value="other">Other</option>
                    </select>
                    <div class="flex items-center text-gray-500">&nbsp;</div>
                </div>
            `);
            const itemSelects = wrap.querySelectorAll('.item-select');
            const last = itemSelects[itemSelects.length - 1];
            if (last) {
                last.innerHTML = buildItemOptions();
                last.addEventListener('change', (e) => onItemChange(e.target));
            }
        });

        // Wire existing rows
        document.querySelectorAll('.item-select').forEach(sel => sel.addEventListener('change', (e) => onItemChange(e.target)));
        document.querySelectorAll('.item').forEach(row => {
            const itemSel = row.querySelector('.item-select');
            if (itemSel && !itemSel.innerHTML.trim()) itemSel.innerHTML = buildItemOptions();
            if (itemSel && itemSel.value) onItemChange(itemSel);
        });
    </script>
</x-app-layout>
