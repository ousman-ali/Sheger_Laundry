<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">New Stock-out Request</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                @if ($errors->any())
                    <div class="mb-4 p-3 rounded bg-red-50 text-red-800 text-sm">{{ $errors->first() }}</div>
                @endif
                <form action="{{ route('stock-out-requests.store') }}" method="POST" class="space-y-4">
                    @csrf
                    @php
                        // Unit root IDs for client-side filtering
                        $unitRootIds = [];
                        foreach (\App\Models\Unit::orderBy('name')->get() as $u) {
                            $anc = $u->ancestry();
                            $root = end($anc);
                            $unitRootIds[$u->id] = $root ? $root->id : $u->id;
                        }
                        $inventoryList = [];
                        foreach (\App\Models\InventoryItem::with('unit')->orderBy('name')->get() as $it) {
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
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium">Remarks (optional)</label>
                            <input type="text" name="remarks" class="w-full border rounded p-2" />
                        </div>
                    </div>

                    <div id="items" class="space-y-4">
                        <h3 class="font-semibold">Items</h3>
                        <div class="item grid grid-cols-1 md:grid-cols-5 gap-2">
                            <select name="items[0][inventory_item_id]" class="border rounded p-2 item-select" required>
                                <option value="">Select Item</option>
                                @foreach($inventoryItems as $it)
                                    <option value="{{ $it->id }}">{{ $it->name }} ({{ optional($it->unit)->name }})</option>
                                @endforeach
                            </select>
                            <select name="items[0][unit_id]" class="border rounded p-2 unit-select" required>
                                <option value="">Unit</option>
                                @foreach($units as $u)
                                    @php $rid = $unitRootIds[$u->id] ?? null; @endphp
                                    <option value="{{ $u->id }}" data-root="{{ $rid }}">{{ $u->name }}</option>
                                @endforeach
                            </select>
                            <input type="number" name="items[0][quantity]" step="0.01" min="0.01" class="border rounded p-2" placeholder="Quantity" required />
                            <div class="flex items-center gap-2">
                                <button type="button" class="remove-line text-sm text-red-600">Remove</button>
                                <button type="button" class="duplicate-line text-sm text-slate-700">Duplicate</button>
                            </div>
                        </div>
                    </div>

                    <div>
                        <button type="button" id="add-item" class="px-3 py-2 rounded border">Add Item</button>
                    </div>

                    <div class="flex gap-2">
                        <a href="{{ route('stock-out-requests.index') }}" class="px-4 py-2 rounded border">Cancel</a>
                        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const INVENTORY = @json($inventoryList);
        const UNITS = @json($unitsForJs);
        const UNIT_ROOT = (id) => {
            const u = UNITS.find(x => x.id == id);
            return u ? u.root_id : null;
        };

        let idx = 0;
        function filterUnitsForRow(row) {
            const itemSel = row.querySelector('.item-select');
            const unitSel = row.querySelector('.unit-select');
            if (!itemSel || !unitSel) return;
            const itemId = itemSel.value;
            if (!itemId) {
                unitSel.querySelectorAll('option').forEach(opt => opt.hidden = false);
                return;
            }
            const inv = INVENTORY.find(x => x.id == itemId);
            const rootId = inv ? inv.unit_root : null;
            unitSel.querySelectorAll('option').forEach(opt => {
                if (!opt.value) return;
                const oroot = opt.getAttribute('data-root');
                opt.hidden = (rootId && oroot) ? (String(oroot) !== String(rootId)) : false;
            });
            const cur = unitSel.options[unitSel.selectedIndex];
            if (cur && cur.hidden) unitSel.value = '';
            // Auto-select the default unit for the chosen inventory item if present
            if (inv && inv.unit_id && [...unitSel.options].some(o => o.value === String(inv.unit_id))) {
                unitSel.value = String(inv.unit_id);
            }
        }
        document.getElementById('add-item').addEventListener('click', () => {
            idx++;
            const c = document.getElementById('items');
            const html = `
                <div class="item grid grid-cols-1 md:grid-cols-5 gap-2">
                    <select name="items[${idx}][inventory_item_id]" class="border rounded p-2 item-select" required>
                        <option value="">Select Item</option>
                        @foreach($inventoryItems as $it)
                            <option value="{{ $it->id }}">{{ $it->name }} ({{ optional($it->unit)->name }})</option>
                        @endforeach
                    </select>
                    <select name="items[${idx}][unit_id]" class="border rounded p-2 unit-select" required>
                        <option value="">Unit</option>
                        @foreach($units as $u)
                            @php $rid = $unitRootIds[$u->id] ?? null; @endphp
                            <option value="{{ $u->id }}" data-root="{{ $rid }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                    <input type="number" name="items[${idx}][quantity]" step="0.01" min="0.01" class="border rounded p-2" placeholder="Quantity" required />
                    <div class="flex items-center gap-2">
                        <button type="button" class="remove-line text-sm text-red-600">Remove</button>
                        <button type="button" class="duplicate-line text-sm text-slate-700">Duplicate</button>
                    </div>
                </div>`;
            c.insertAdjacentHTML('beforeend', html);
            const newRow = c.lastElementChild;
            filterUnitsForRow(newRow);
        });

        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('remove-line')) {
                const row = e.target.closest('.item');
                row.parentNode.removeChild(row);
            }
            if (e.target.classList.contains('duplicate-line')) {
                const row = e.target.closest('.item');
                const clone = row.cloneNode(true);
                // clear inputs
                clone.querySelectorAll('input').forEach(i => i.value = i.value);
                row.parentNode.insertBefore(clone, row.nextSibling);
                filterUnitsForRow(clone);
            }
        });

        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('item-select')) {
                const row = e.target.closest('.item');
                filterUnitsForRow(row);
            }
        });

        // Initial filter for first row
        filterUnitsForRow(document.querySelector('#items .item'));
    </script>
</x-app-layout>
