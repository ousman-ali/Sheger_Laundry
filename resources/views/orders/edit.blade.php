<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Order') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                {{-- Template for item remark presets --}}
                <template id="item-remark-presets-tpl">
                    <div class="flex flex-wrap gap-2 mb-2">
                        @foreach(\App\Models\RemarkPreset::where('is_active', true)->orderBy('sort_order')->orderBy('label')->get() as $rp)
                            <label class="inline-flex items-center gap-2 border rounded px-2 py-1 text-xs">
                                <input type="checkbox" data-item-remark-preset value="{{ $rp->id }}" class="rounded">
                                <span>{{ $rp->label }}</span>
                            </label>
                        @endforeach
                    </div>
                </template>
                <h1 class="text-2xl font-bold mb-4">Edit Order #{{ $order->order_id }}</h1>
                <form action="{{ route('orders.update', $order) }}" method="POST">
                    @csrf
                    @method('PUT')
                    @php
                        // Maps for client-side unit filtering and default auto-select
                        $unitRootIds = [];
                        foreach ($units as $u) {
                            $anc = $u->ancestry();
                            $root = end($anc);
                            $unitRootIds[$u->id] = $root ? $root->id : $u->id;
                        }
                        $clothRootIds = [];
                        $clothUnitIds = [];
                        foreach ($clothItems as $ci) {
                            $anc = $ci->unit ? $ci->unit->ancestry() : [];
                            $root = $anc ? end($anc) : null;
                            $clothRootIds[$ci->id] = $root ? $root->id : ($ci->unit->id ?? null);
                            $clothUnitIds[$ci->id] = $ci->unit_id;
                        }
                    @endphp

                    @role('Admin')
                    <div class="mb-4">
                        <label for="order_id" class="block text-sm font-medium">Order ID (Admin override)</label>
                        <input type="text" name="order_id" id="order_id" value="{{ old('order_id', $order->order_id) }}" class="w-full border rounded p-2">
                        <p class="text-xs text-gray-500 mt-1">Leave unchanged to keep current. New orders autofollow {{ config('shebar.order_id_prefix') }}-{{ now()->format(config('shebar.order_id_format')) }}-NNN. VIP auto-prefix {{ config('shebar.vip_order_id_prefix','VIP') }}-.</p>
                        @error('order_id')<span class="text-red-600 text-sm">{{ $message }}</span>@enderror
                    </div>
                    @endrole

                    <div class="mb-4">
                        <label for="customer_id" class="block text-sm font-medium">Customer</label>
                        <select name="customer_id" id="customer_id" class="w-full border rounded p-2" required>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}" @selected($order->customer_id==$customer->id)>{{ $customer->name }} ({{ $customer->phone }})</option>
                            @endforeach
                        </select>
                        @error('customer_id')<span class="text-red-600 text-sm">{{ $message }}</span>@enderror
                    </div>

                    <div class="mb-6 bg-gray-50 border rounded p-4 space-y-3">
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" name="apply_all_services" value="1" class="rounded" id="apply_all_services">
                            <span class="text-sm">Apply all services to all items (auto add missing services)</span>
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium">Default urgency tier (optional)</label>
                                <select name="default_urgency_tier_id" class="w-full border rounded p-2">
                                    <option value="">None</option>
                                    @foreach($urgencyTiers as $tier)
                                        <option value="{{ $tier->id }}">{{ $tier->label }}</option>
                                    @endforeach
                                </select>
                                @error('default_urgency_tier_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <p class="text-xs text-gray-500">Unchecked services when auto mode is enabled will persist as removed (won't be re-added).</p>
                    </div>

                    <div id="items-container" class="mb-4">
                        <h2 class="text-lg font-semibold mb-2">Items</h2>
                        @foreach($order->orderItems as $iIndex => $item)
                            <div class="item mb-4 border p-4 rounded" data-item-index="{{ $iIndex }}">
                                <input type="hidden" name="items[{{ $iIndex }}][item_id]" value="{{ $item->id }}">
                                <div class="mb-2">
                                    <label class="block text-sm font-medium">Cloth Item</label>
                                    <select name="items[{{ $iIndex }}][cloth_item_id]" class="w-full border rounded p-2 cloth-select" required>
                                        @foreach ($clothItems as $cloth)
                                            <option value="{{ $cloth->id }}" @selected($item->cloth_item_id==$cloth->id)>{{ $cloth->name }} ({{ $cloth->unit->name }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="block text-sm font-medium">Unit</label>
                                    <select name="items[{{ $iIndex }}][unit_id]" class="w-full border rounded p-2" required>
                                        @foreach ($units as $unit)
                                            <option value="{{ $unit->id }}" @selected($item->unit_id==$unit->id)>{{ $unit->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="block text-sm font-medium">Quantity</label>
                                    <input type="number" step="0.01" name="items[{{ $iIndex }}][quantity]" value="{{ $item->quantity }}" class="w-full border rounded p-2" required>
                                </div>
                                <div class="mb-2">
                                    <label class="block text-sm font-medium">Remarks</label>
                                    <div class="mb-1 text-xs text-gray-600">Common remarks (this item)</div>
                                    <div class="flex flex-wrap gap-2 mb-2">
                                        @php $selectedItemPresets = $item->remarkPresets->pluck('id')->all(); @endphp
                                        @foreach(\App\Models\RemarkPreset::where('is_active', true)->orderBy('sort_order')->orderBy('label')->get() as $rp)
                                            <label class="inline-flex items-center gap-2 border rounded px-2 py-1 text-xs">
                                                <input type="checkbox" name="items[{{ $iIndex }}][remark_preset_ids][]" value="{{ $rp->id }}" class="rounded" @checked(in_array($rp->id, $selectedItemPresets))>
                                                <span>{{ $rp->label }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    <textarea name="items[{{ $iIndex }}][remarks]" class="w-full border rounded p-2">{{ $item->remarks }}</textarea>
                                </div>
                                <div class="mb-2">
                                    <label class="block text-sm font-medium">Default urgency (this item)</label>
                                    <select name="items[{{ $iIndex }}][default_urgency_tier_id]" class="w-full border rounded p-2">
                                        <option value="">Use global/default</option>
                                        @foreach ($urgencyTiers as $tier)
                                            <option value="{{ $tier->id }}">{{ $tier->label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="services-container">
                                    <h3 class="text-md font-semibold flex items-center justify-between">Services <button type="button" class="add-service bg-blue-500 text-white px-2 py-1 rounded text-xs" data-item="{{ $iIndex }}">Add</button></h3>
                                    @php $svcIndex=0; @endphp
                                    @foreach($item->orderItemServices as $svc)
                                        <div class="service mb-2 border p-2 rounded" data-service-index="{{ $svcIndex }}">
                                            <input type="hidden" name="items[{{ $iIndex }}][services][{{ $svcIndex }}][service_row_id]" value="{{ $svc->id }}">
                                            <div class="mb-2">
                                                <label class="block text-sm font-medium">Service</label>
                                                <select name="items[{{ $iIndex }}][services][{{ $svcIndex }}][service_id]" class="w-full border rounded p-2" required>
                                                    @foreach ($services as $service)
                                                        <option value="{{ $service->id }}" @selected($svc->service_id==$service->id)>{{ $service->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="mb-2">
                                                <label class="block text-sm font-medium">Quantity</label>
                                                <input type="number" step="0.01" name="items[{{ $iIndex }}][services][{{ $svcIndex }}][quantity]" value="{{ $svc->quantity }}" class="w-full border rounded p-2" required>
                                            </div>
                                            <div class="mb-2">
                                                <label class="block text-sm font-medium">Urgency</label>
                                                <select name="items[{{ $iIndex }}][services][{{ $svcIndex }}][urgency_tier_id]" class="w-full border rounded p-2">
                                                    <option value="">Normal</option>
                                                    @foreach ($urgencyTiers as $tier)
                                                        <option value="{{ $tier->id }}" @selected($svc->urgency_tier_id==$tier->id)>{{ $tier->label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <button type="button" class="remove-service text-red-600 text-xs" data-remove>Remove</button>
                                        </div>
                                        @php $svcIndex++; @endphp
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                        <button type="button" class="add-item bg-blue-500 text-white px-2 py-1 rounded">Add Item</button>
                    </div>

                    <div class="mb-4">
                        <label for="discount" class="block text-sm font-medium">Discount</label>
                        <input type="number" step="0.01" name="discount" id="discount" value="{{ $order->discount }}" class="w-full border rounded p-2">
                    </div>
                    <div class="mb-4">
                        <label for="appointment_date" class="block text-sm font-medium">Order Received Date & Time</label>
                        <input type="datetime-local" name="appointment_date" id="appointment_date" value="{{ optional($order->appointment_date)->format('Y-m-d\TH:i') }}" class="w-full border rounded p-2">
                    </div>
                    <div class="mb-4">
                        <label for="pickup_date" class="block text-sm font-medium">Pickup Date</label>
                        <input type="datetime-local" name="pickup_date" id="pickup_date" value="{{ optional($order->pickup_date)->format('Y-m-d\TH:i') }}" class="w-full border rounded p-2">
                    </div>
                    <div class="mb-4">
                        <label for="remarks" class="block text-sm font-medium">Remarks</label>
                        <div class="mb-2 text-xs text-gray-600">Common remarks</div>
                        <div class="flex flex-wrap gap-2 mb-2">
                            @php($selected = $order->remarkPresets->pluck('id')->all())
                            @foreach(\App\Models\RemarkPreset::where('is_active', true)->orderBy('sort_order')->orderBy('label')->get() as $rp)
                                <label class="inline-flex items-center gap-2 border rounded px-2 py-1 text-xs">
                                    <input type="checkbox" name="remark_preset_ids[]" value="{{ $rp->id }}" class="rounded" @checked(in_array($rp->id, $selected))>
                                    <span>{{ $rp->label }}</span>
                                </label>
                            @endforeach
                        </div>
                        <textarea name="remarks" id="remarks" class="w-full border rounded p-2" placeholder="Additional free-text remarks...">{{ $order->remarks }}</textarea>
                    </div>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Update Order</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        let itemIndex = {{ count($order->orderItems) - 1 }};
        const servicesOptionsHtml = `@foreach ($services as $service)<option value="{{ $service->id }}">{{ $service->name }}</option>@endforeach`;
        const urgencyOptionsHtml = `<option value="">Normal</option>@foreach ($urgencyTiers as $tier)<option value="{{ $tier->id }}">{{ $tier->label }}</option>@endforeach`;
        const clothItemsOptionsHtml = `@foreach ($clothItems as $cloth)<option value="{{ $cloth->id }}">{{ $cloth->name }} ({{ $cloth->unit->name }})</option>@endforeach`;
        const UNITS = @json($units->map(fn($u) => [ 'id' => $u->id, 'name' => $u->name, 'root_id' => $unitRootIds[$u->id] ?? null ]));
        const CLOTH_ROOTS = @json($clothRootIds);
        const CLOTH_UNIT_IDS = @json($clothUnitIds);
        function buildUnitsOptions(rootId){
            return UNITS
                .filter(u => !rootId || u.root_id == rootId)
                .map(u => `<option value="${u.id}" data-root="${u.root_id}">${u.name}</option>`)
                .join('');
        }

        document.querySelector('.add-item').addEventListener('click', () => {
            itemIndex++;
            const container = document.getElementById('items-container');
        const html = `<div class="item mb-4 border p-4 rounded" data-item-index="${itemIndex}">
                <div class="mb-2">
                    <label class="block text-sm font-medium">Cloth Item</label>
                    <select name="items[${itemIndex}][cloth_item_id]" class="w-full border rounded p-2" required>${clothItemsOptionsHtml}</select>
                </div>
                <div class="mb-2">
                    <label class="block text-sm font-medium">Unit</label>
            <select name="items[${itemIndex}][unit_id]" class="w-full border rounded p-2" required><option value="">Select Unit</option>${buildUnitsOptions('')}</select>
                </div>
                <div class="mb-2">
                    <label class="block text-sm font-medium">Quantity</label>
                    <input type="number" step="0.01" name="items[${itemIndex}][quantity]" class="w-full border rounded p-2" required>
                </div>
                <div class="mb-2">
                    <label class="block text-sm font-medium">Remarks</label>
                    <div class="mb-1 text-xs text-gray-600">Common remarks (this item)</div>
                    <div class="item-remark-presets">${document.getElementById('item-remark-presets-tpl').innerHTML}</div>
                    <textarea name="items[${itemIndex}][remarks]" class="w-full border rounded p-2"></textarea>
                </div>
                <div class="mb-2">
                    <label class="block text-sm font-medium">Default urgency (this item)</label>
                    <select name="items[${itemIndex}][default_urgency_tier_id]" class="w-full border rounded p-2"><option value="">Use global/default</option>${urgencyOptionsHtml}</select>
                </div>
                <div class="services-container">
                    <h3 class="text-md font-semibold flex items-center justify-between">Services <button type="button" class="add-service bg-blue-500 text-white px-2 py-1 rounded text-xs" data-item="${itemIndex}">Add</button></h3>
                </div>
            </div>`;
            container.insertAdjacentHTML('beforeend', html);
            const newRow = container.querySelector('.item:last-of-type');
            newRow?.querySelectorAll('[data-item-remark-preset]')?.forEach(inp => {
                inp.setAttribute('name', `items[${itemIndex}][remark_preset_ids][]`);
            });
            // Wire cloth change for the new row to filter units and auto-select default
            const clothSel = newRow.querySelector(`select[name="items[${itemIndex}][cloth_item_id]"]`);
            const unitSel = newRow.querySelector(`select[name="items[${itemIndex}][unit_id]"]`);
            if (clothSel && unitSel) {
                clothSel.addEventListener('change', () => {
                    const cid = clothSel.value;
                    const rootId = CLOTH_ROOTS[cid] || '';
                    unitSel.innerHTML = `<option value="">Select Unit</option>${buildUnitsOptions(rootId)}`;
                    const defUnit = CLOTH_UNIT_IDS[cid];
                    if (defUnit && [...unitSel.options].some(o => o.value === String(defUnit))) {
                        unitSel.value = String(defUnit);
                    }
                });
            }
        });

        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('add-service')) {
                const itemIdx = e.target.getAttribute('data-item');
                const itemDiv = e.target.closest('.item');
                const servicesContainer = itemDiv.querySelector('.services-container');
                const svcIndex = servicesContainer.querySelectorAll('.service').length;
                const svcHtml = `<div class=\"service mb-2 border p-2 rounded\" data-service-index=\"${svcIndex}\">\n                        <div class=\"mb-2\">\n                            <label class=\"block text-sm font-medium\">Service</label>\n                            <select name=\"items[${itemIdx}][services][${svcIndex}][service_id]\" class=\"w-full border rounded p-2\" required>${servicesOptionsHtml}</select>\n                        </div>\n                        <div class=\"mb-2\">\n                            <label class=\"block text-sm font-medium\">Quantity</label>\n                            <input type=\"number\" step=\"0.01\" name=\"items[${itemIdx}][services][${svcIndex}][quantity]\" class=\"w-full border rounded p-2\" required>\n                        </div>\n                        <div class=\"mb-2\">\n                            <label class=\"block text-sm font-medium\">Urgency</label>\n                            <select name=\"items[${itemIdx}][services][${svcIndex}][urgency_tier_id]\" class=\"w-full border rounded p-2\">${urgencyOptionsHtml}</select>\n                        </div>\n                        <button type=\"button\" class=\"remove-service text-red-600 text-xs\" data-remove>Remove</button>\n                    </div>`;
                servicesContainer.insertAdjacentHTML('beforeend', svcHtml);
            }
            if (e.target.hasAttribute('data-remove')) {
                const svcDiv = e.target.closest('.service');
                svcDiv.remove();
            }
        });

        // Load Tom Select and enhance cloth selects
        (function loadTomSelect(){
            if (window.TomSelect) { initClothSelects(); return; }
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css';
            document.head.appendChild(link);
            const s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js';
            s.onload = initClothSelects;
            document.head.appendChild(s);
        })();

        function initClothSelects(){
            document.querySelectorAll('select.cloth-select').forEach(el => {
                try { if (!el.tomselect) new TomSelect(el, { create:false, sortField:{ field:'text', direction:'asc' } }); } catch(_) {}
            });
        }

        // Existing rows: when cloth item changes, filter compatible units and auto-select default cloth unit
        document.addEventListener('change', (e) => {
            const sel = e.target;
            if (!(sel.name && sel.name.includes('[cloth_item_id]'))) return;
            const row = sel.closest('.item');
            const unitSel = row?.querySelector('select[name$="[unit_id]"]');
            const cid = sel.value;
            const rootId = CLOTH_ROOTS[cid] || '';
            if (unitSel) {
                const current = unitSel.value;
                unitSel.innerHTML = `<option value="">Select Unit</option>${buildUnitsOptions(rootId)}`;
                const defUnit = CLOTH_UNIT_IDS[cid];
                if (defUnit && [...unitSel.options].some(o => o.value === String(defUnit))) {
                    unitSel.value = String(defUnit);
                } else if (current && [...unitSel.options].some(o => o.value === String(current))) {
                    unitSel.value = String(current);
                }
            }
        });
    </script>
</x-app-layout>
