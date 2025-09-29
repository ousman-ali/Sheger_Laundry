<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create Order') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h1 class="text-2xl font-bold mb-4">Create Order</h1>
                <form action="{{ route('orders.store') }}" method="POST">
                    @csrf
                    @role('Admin')  
                    <div class="mb-4">
                        <label for="order_id" class="block text-sm font-medium">Order ID (optional override)</label>
                        <input type="text" name="order_id" id="order_id" value="{{ old('order_id') }}" class="w-full border rounded p-2" placeholder="Leave blank to auto-generate">
                        <p class="text-xs text-gray-500 mt-1">Format: {{ config('shebar.order_id_prefix') }}-{{ now()->format(config('shebar.order_id_format')) }}-NNN. VIP orders auto-prefix with {{ config('shebar.vip_order_id_prefix','VIP') }}-.</p>
                        @error('order_id')
                            <span class="text-red-600 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    @endrole
                    
                    @php
                        // Maps to support client-side unit filtering by cloth item's unit root
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
                        
                        // Get remark presets for template generation
                        $remarkPresets = \App\Models\RemarkPreset::where('is_active', true)->orderBy('sort_order')->orderBy('label')->get();
                    @endphp

                    <div class="mb-4">
                        <label for="customer_id" class="block text-sm font-medium">Customer</label>
                        <div x-data="customerSelect()" class="relative" x-init="init()" @click.outside="closeList()">
                            <div class="flex gap-2">
                                <div class="flex-1 relative">
                                    <input type="hidden" name="customer_id" x-model="selectedId" required>
                                    <input
                                        type="text"
                                        x-model="query"
                                        @input.debounce="search()"
                                        @focus="openList()"
                                        @keydown.arrow-down.prevent="highlightNext()"
                                        @keydown.arrow-up.prevent="highlightPrev()"
                                        @keydown.enter.prevent="chooseHighlighted()"
                                        placeholder="Search or select customer"
                                        class="w-full border rounded p-2"
                                        aria-autocomplete="list"
                                        aria-controls="customer-list"
                                    >

                                    <ul id="customer-list" x-show="open && results.length>0" x-cloak class="absolute z-50 bg-white border rounded mt-1 w-full max-h-48 overflow-auto">
                                        <template x-for="(r, i) in results" :key="r.id">
                                            <li :class="{ 'bg-gray-100': i===highlighted }" @mouseenter="highlighted = i" @click="choose(r)" class="px-3 py-2 cursor-pointer" x-text="r.label"></li>
                                        </template>
                                    </ul>
                                </div>
                                <button type="button" x-on:click="openModal = true" class="px-3 py-2 bg-blue-500 text-white rounded">Add new customer</button>
                            </div>

                            <!-- Add Customer Modal (inside same Alpine scope) -->
                            <div x-show="openModal" x-cloak style="display:none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
                                <div class="bg-white rounded p-6 w-full max-w-md">
                                    <h3 class="font-semibold mb-3">Add Customer</h3>
                                    <div class="space-y-2">
                                        <div>
                                            <label class="block text-sm">Name</label>
                                            <input type="text" x-model="modal.name" class="w-full border rounded p-2">
                                        </div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <label class="block text-sm">Code</label>
                                                <input type="text" x-model="modal.code" class="w-full border rounded p-2" placeholder="Auto if empty">
                                            </div>
                                            <div class="flex items-end gap-2">
                                                <label class="text-sm">VIP</label>
                                                <input type="checkbox" x-model="modal.is_vip" class="rounded">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-sm">Phone</label>
                                            <input type="text" x-model="modal.phone" class="w-full border rounded p-2">
                                        </div>
                                        <div>
                                            <label class="block text-sm">Address</label>
                                            <textarea x-model="modal.address" class="w-full border rounded p-2"></textarea>
                                        </div>
                                        <div class="flex items-center justify-end gap-2 mt-3">
                                            <button type="button" @click="openModal=false" class="px-3 py-1 border rounded">Cancel</button>
                                            <button type="button" @click="addCustomer()" class="px-3 py-1 bg-green-600 text-white rounded">Add</button>
                                        </div>
                                        <div x-show="modal.error" class="text-sm text-red-600" x-text="modal.error"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @error('customer_id')
                            <span class="text-red-600 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Add Customer Modal (Alpine) -->
                    <div x-data="{ open: false, name:'', code:'', is_vip:false, phone:'', address:'', loading:false, error:'' }" x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
                        <div class="bg-white rounded p-6 w-full max-w-md">
                            <h3 class="font-semibold mb-3">Add Customer</h3>
                            <div class="space-y-2">
                                <div>
                                    <label class="block text-sm">Name</label>
                                    <input type="text" x-model="name" class="w-full border rounded p-2">
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-sm">Code</label>
                                        <input type="text" x-model="code" class="w-full border rounded p-2" placeholder="Auto if empty">
                                    </div>
                                    <div class="flex items-end gap-2">
                                        <label class="text-sm">VIP</label>
                                        <input type="checkbox" x-model="is_vip" class="rounded">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm">Phone</label>
                                    <input type="text" x-model="phone" class="w-full border rounded p-2">
                                </div>
                                <div>
                                    <label class="block text-sm">Address</label>
                                    <textarea x-model="address" class="w-full border rounded p-2"></textarea>
                                </div>
                                <div class="flex items-center justify-end gap-2 mt-3">
                                    <button type="button" @click="open=false" class="px-3 py-1 border rounded">Cancel</button>
                                    <button type="button" @click="(async()=>{ error=''; loading=true; try{ const res = await fetch('{{ route('customers.store') }}',{ method:'POST', headers:{ 'Content-Type':'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body: JSON.stringify({ name, phone, address, code, is_vip }) }); if(res.status===201){ const data = await res.json(); document.querySelector('[x-data=\"customerSelect()\"] input[type=hidden][name=customer_id]').value = data.id; document.querySelector('[x-data=\"customerSelect()\"] input[type=text]').value = data.label; open=false; name=''; code=''; is_vip=false; phone=''; address=''; } else { const j = await res.json(); error = j.message || 'Validation failed'; } } catch(e){ error = e.message } finally{ loading=false } })()" class="px-3 py-1 bg-green-600 text-white rounded">Add</button>
                                </div>
                                <div x-show="error" class="text-sm text-red-600" x-text="error"></div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-6 bg-gray-50 border rounded p-4 space-y-3">
                        <div class="flex flex-col gap-2">
                            <label class="inline-flex items-center gap-2">
                                <input type="checkbox" name="apply_all_services" value="1" id="apply_all_services" class="rounded">
                                <span class="text-sm font-medium">Apply ALL services to ALL items</span>
                            </label>
                            <label class="inline-flex items-center gap-2">
                                <input type="checkbox" name="mark_all_urgent" value="1" id="mark_all_urgent" class="rounded">
                                <span class="text-sm font-medium">Mark all selected services urgent (override)</span>
                            </label>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-medium mb-1">Default urgency tier (fallback)</label>
                                <select name="default_urgency_tier_id" class="w-full border rounded p-2 text-sm">
                                    <option value="">None</option>
                                    @foreach($urgencyTiers as $tier)
                                        <option value="{{ $tier->id }}">{{ $tier->label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium mb-1">Global urgent tier (when mark all urgent)</label>
                                <select name="all_urgency_tier_id" id="all_urgency_tier_id" class="w-full border rounded p-2 text-sm">
                                    <option value="">Select tier</option>
                                    @foreach($urgencyTiers as $tier)
                                        <option value="{{ $tier->id }}">{{ $tier->label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex items-end">
                                <button type="button" id="select_all_services" class="text-xs bg-slate-600 text-white px-3 py-2 rounded">Select all services (manual mode)</button>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500">Apply-all ignores manual service checkboxes. Manual mode: tick services per item and adjust quantity / urgency inline.</p>
                    </div>

                    <div id="items-container" class="mb-4 space-y-6">
                        <h2 class="text-lg font-semibold">Items</h2>
                        <div class="item border p-4 rounded" data-item-index="0">
                            <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                                <h3 class="text-sm font-semibold text-gray-800">Item</h3>
                                <div class="flex items-center gap-2">
                                    <label class="text-xs text-gray-600">Copy services from</label>
                                    <select class="copy-from border rounded p-1 text-xs min-w-40">
                                        <option value="">Select item…</option>
                                    </select>
                                    <button type="button" class="copy-btn text-xs text-blue-600 underline">Copy</button>
                                </div>
                                <button type="button" class="remove-item text-xs text-red-600 underline">Remove item</button>
                            </div>
                            <div class="grid md:grid-cols-5 gap-4 mb-4">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium">Cloth Item</label>
                                    <select name="items[0][cloth_item_id]" class="w-full border rounded p-2 cloth-select" required>
                                        <option value="">Select Item</option>
                                        @foreach ($clothItems as $item)
                                            <option value="{{ $item->id }}">{{ $item->item_code }} {{ $item->name }} ({{ $item->unit->name }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium">Unit</label>
                                    <select name="items[0][unit_id]" class="w-full border rounded p-2" required>
                                        <option value="">Select Unit</option>
                                        @foreach ($units as $unit)
                                            <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium">Quantity</label>
                                    <input type="number" name="items[0][quantity]" step="0.01" class="w-full border rounded p-2" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium">Item Urgency (default)</label>
                                    <select name="items[0][default_urgency_tier_id]" class="w-full border rounded p-2">
                                        <option value="">None</option>
                                        @foreach ($urgencyTiers as $tier)
                                            <option value="{{ $tier->id }}">{{ $tier->label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="block text-sm font-medium">Remarks</label>
                                <div class="mb-1 text-xs text-gray-600">Common remarks (this item)</div>
                                <div class="flex flex-wrap gap-2 mb-2" id="item-remarks-0">
                                    @foreach($remarkPresets as $rp)
                                        <label class="inline-flex items-center gap-2 border rounded px-2 py-1 text-xs">
                                            <input type="checkbox" name="items[0][remark_preset_ids][]" value="{{ $rp->id }}" class="rounded">
                                            <span>{{ $rp->label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                <textarea name="items[0][remarks]" class="w-full border rounded p-2" placeholder="Item-specific remarks..."></textarea>
                            </div>
                            <div class="services-panel">
                                <h3 class="text-sm font-semibold mb-2 flex items-center justify-between">Services (manual mode)
                                    <span class="flex items-center gap-2">
                                        <button type="button" class="item-select-all text-xs text-blue-600 underline">Select all</button>
                                        <button type="button" class="item-select-none text-xs text-blue-600 underline">Select none</button>
                                        <button type="button" class="item-clear text-xs text-slate-700 underline">Clear</button>
                                        <button type="button" class="text-xs text-blue-600 underline add-svc-row" data-item="0">Add custom row</button>
                                    </span>
                                </h3>
                                <div class="service-table space-y-2" data-services-wrapper>
                                    @foreach($services as $service)
                                        <div class="flex flex-wrap items-center gap-2 border rounded p-2 bg-gray-50" data-service-line data-service-id="{{ $service->id }}">
                                            <label class="inline-flex items-center gap-1 mr-2">
                                                <input type="checkbox" name="items[0][services][{{ $service->id }}][selected]" value="1" class="svc-check">
                                                <span class="text-xs font-medium">{{ $service->name }}</span>
                                            </label>
                                            <div class="flex items-center gap-1">
                                                <span class="text-[11px] text-gray-500">Qty</span>
                                                <input type="number" step="0.01" name="items[0][services][{{ $service->id }}][quantity]" class="w-20 border rounded p-1 text-xs" placeholder="auto">
                                            </div>
                                            <div class="flex items-center gap-1">
                                                <span class="text-[11px] text-gray-500">Urgency</span>
                                                <select name="items[0][services][{{ $service->id }}][urgency_tier_id]" class="border rounded p-1 text-xs">
                                                    <option value="">Item/Global</option>
                                                    @foreach($urgencyTiers as $tier)
                                                        <option value="{{ $tier->id }}">{{ $tier->label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="add-item bg-blue-500 text-white px-3 py-1 rounded text-sm mb-6">Add Item</button>

                    <!-- Replace the entire date picker section with this code -->
                    <input type="hidden" name="date_type" id="date_type_field" value="EC">

                    <div class="grid gap-6 mb-6">
                        <!-- Row 1: Discount + Date Type -->
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium mb-1">Discount</label>
                                <input type="number" step="0.01" name="discount" 
                                    class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-300">
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1">Date Type</label>
                                <select id="calendar_type" name="date_type"
                                        class="w-full border rounded-lg p-2 text-sm focus:ring focus:ring-blue-300">
                                    <option value="GC">Gregorian (GC)</option>
                                    <option value="EC" selected>Ethiopian (EC)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Row 2: Dates Side by Side -->
                        <div class="grid md:grid-cols-2 gap-6">
                            <!-- Order Received Date -->
                            <div>
                                <label class="block text-sm font-medium mb-1">Order Received Date & Time</label>
                                <div class="relative">
                                    <!-- Gregorian Date (hidden by default) -->
                                    <input type="datetime-local" id="gc_appointment_date" name="appointment_date" 
                                        class="w-full border rounded-lg p-2 hidden focus:ring focus:ring-blue-300">

                                    <!-- Ethiopian Date Picker -->
                                    <div class="ethiopian-date-picker-container">
                                        <input type="text" id="ec_appointment_date" 
                                            class="w-full border rounded-lg p-2 ethiopian-date-input focus:ring focus:ring-blue-300" 
                                            placeholder="የትዕዛዝ ቀን ይምረጡ (YYYY-MM-DD)" 
                                            readonly
                                            data-target="gc_appointment_date">
                                        <div class="ethiopian-calendar-dropdown" id="ec_appointment_calendar"></div>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Defaults to current time; you can edit this to record a different received time.</p>
                            </div>

                            <!-- Pickup Date -->
                            <div>
                                <label class="block text-sm font-medium mb-1">Pickup Date</label>
                                <div class="relative">
                                    <!-- Gregorian Date (hidden) -->
                                    <input type="datetime-local" id="gc_pickup_date" name="pickup_date" 
                                        class="w-full border rounded-lg p-2 hidden focus:ring focus:ring-blue-300">

                                    <!-- Ethiopian Date Picker -->
                                    <div class="ethiopian-date-picker-container">
                                        <input type="text" id="ec_pickup_date" 
                                            class="w-full border rounded-lg p-2 ethiopian-date-input focus:ring focus:ring-blue-300" 
                                            placeholder="የማምጣት ቀን ይምረጡ (YYYY-MM-DD)" 
                                            readonly
                                            data-target="gc_pickup_date">
                                        <div class="ethiopian-calendar-dropdown" id="ec_pickup_calendar"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="block text-sm font-medium">Remarks</label>
                        <div class="mb-2 text-xs text-gray-600">Common remarks</div>
                        <div class="flex flex-wrap gap-2 mb-2">
                            @foreach($remarkPresets as $rp)
                                <label class="inline-flex items-center gap-2 border rounded px-2 py-1 text-xs">
                                    <input type="checkbox" name="remark_preset_ids[]" value="{{ $rp->id }}" class="rounded">
                                    <span>{{ $rp->label }}</span>
                                </label>
                            @endforeach
                        </div>
                        <textarea name="remarks" class="w-full border rounded p-2" placeholder="Additional free-text remarks..."></textarea>
                    </div>
                    
                    <div class="flex gap-2">
                        <a href="{{ route('orders.index') }}" class="px-4 py-2 rounded border">Cancel</a>
                        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded">Create Order</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

     <!-- Ethiopian Calendar Library -->
    <script src="https://cdn.jsdelivr.net/npm/ethiopic-calendar@latest/dist/ethiopic-calendar.min.js"></script>

    <style>
        .ethiopian-date-picker-container {
            position: relative;
            width: 100%;
        }

        .ethiopian-calendar-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            z-index: 1000;
            background: white;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 5px;
            display: none;
            width: 320px;
            max-height: 400px;
            overflow-y: auto;
        }

        .ethiopian-calendar {
            padding: 15px;
            font-family: Arial, sans-serif;
            width: 100%;
            box-sizing: border-box;
            min-height: 350px;
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .calendar-nav-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 14px;
        }

        .calendar-nav-btn:hover {
            background: #0056b3;
        }

        .calendar-title {
            font-weight: bold;
            font-size: 16px;
            margin: 0 10px;
        }

        .year-month-selectors {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            position: sticky;
            top: 60px;
            background: white;
            z-index: 5;
            padding: 5px 0;
        }

        .year-selector, .month-selector {
            flex: 1;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background: #ddd;
            border: 1px solid #ddd;
            min-height: 240px;
        }

        .calendar-day-header {
            text-align: center;
            font-weight: bold;
            padding: 10px 0;
            background: #e9ecef;
            font-size: 12px;
            position: sticky;
            top: 120px;
            z-index: 5;
        }

        .calendar-day {
            padding: 12px 0;
            text-align: center;
            background: white;
            cursor: pointer;
            border: none;
            font-size: 14px;
            transition: all 0.2s;
            min-height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 40px;
            box-sizing: border-box;
        }

        .calendar-day:hover {
            background: #007bff;
            color: white;
        }

        .calendar-day.selected {
            background: #28a745;
            color: white;
        }

        .calendar-day.other-month {
            color: #999;
            background: #f8f9fa;
        }

        .calendar-day.today {
            background: #ffc107;
            color: #000;
            font-weight: bold;
        }

        .calendar-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            position: sticky;
            bottom: 0;
            background: white;
            padding: 10px 0;
            border-top: 1px solid #eee;
        }

        .today-btn, .clear-btn {
            background: #6c757d;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            margin: 0 5px;
            font-size: 14px;
        }

        .today-btn:hover {
            background: #545b62;
        }

        .clear-btn {
            background: #dc3545;
        }

        .clear-btn:hover {
            background: #c82333;
        }

        .ethiopian-calendar-dropdown::-webkit-scrollbar {
            width: 8px;
        }

        .ethiopian-calendar-dropdown::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .ethiopian-calendar-dropdown::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        .ethiopian-calendar-dropdown::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
    </style>

    <!-- Replace the entire Ethiopian Calendar script section with this code -->
    <script>
        class EthiopianDatePicker {
            constructor(inputId, calendarId) {
                this.input = document.getElementById(inputId);
                this.calendarElement = document.getElementById(calendarId);
                this.targetInput = document.getElementById(this.input.dataset.target);
                this.isOpen = false;
                this.currentDate = this.getCurrentEthiopianDate();
                this.selectedDate = { ...this.currentDate };
                
                this.init();
            }
            
            getCurrentEthiopianDate() {
                const now = new Date();
                const gYear = now.getFullYear();
                const gMonth = now.getMonth() + 1;
                const gDay = now.getDate();
                
                if (window.EthiopicCalendar) {
                    const ethDate = window.EthiopicCalendar.toEthiopic(gYear, gMonth, gDay);
                    return ethDate;
                }
                
                // Manual conversion calculation
                const ethiopianNewYear = (gYear % 4 === 3) ? 12 : 11;
                
                if (gMonth < 9 || (gMonth === 9 && gDay < ethiopianNewYear)) {
                    return {
                        year: gYear - 8,
                        month: gMonth + 4,
                        day: gDay
                    };
                } else {
                    const newYearDate = new Date(gYear, 8, ethiopianNewYear);
                    const currentDate = new Date(gYear, gMonth - 1, gDay);
                    const diffTime = currentDate - newYearDate;
                    const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
                    
                    const ethYear = gYear - 7;
                    const ethMonth = Math.floor(diffDays / 30) + 1;
                    const ethDay = (diffDays % 30) + 1;
                    
                    return {
                        year: ethYear,
                        month: Math.min(13, ethMonth),
                        day: Math.min(30, ethDay)
                    };
                }
            }
            
            getCurrentGregorianDateTime() {
                const now = new Date();
                const year = now.getFullYear();
                const month = String(now.getMonth() + 1).padStart(2, '0');
                const day = String(now.getDate()).padStart(2, '0');
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                
                return `${year}-${month}-${day}T${hours}:${minutes}`;
            }
            
            getDaysInMonth(year, month) {
                if (month === 13) {
                    return (year % 4 === 3) ? 6 : 5;
                }
                return 30;
            }
            
            getFirstDayOfMonth(year, month) {
                 return (year + Math.floor(year / 4) + 2 * month + 6) % 7;
            }
            
            getEthiopianMonthName(month) {
                const months = [
                    'መስከረም', 'ጥቅምት', 'ኅዳር', 'ታህሳስ', 
                    'ጥር', 'የካቲት', 'መጋቢት', 'ሚያዝያ', 
                    'ግንቦት', 'ሰኔ', 'ሐምሌ', 'ነሐሴ', 'ጷጉሜ'
                ];
                return months[month - 1] || '';
            }
            
            getDayNames() {
                return ['ሰ', 'ማ', 'ረ', 'ሐ', 'ዓ', 'ቅ', 'እ'];
            }
            
            getMonthsList() {
                return [
                    {value: 1, name: 'መስከረም'}, {value: 2, name: 'ጥቅምት'}, 
                    {value: 3, name: 'ኅዳር'}, {value: 4, name: 'ታህሳስ'}, 
                    {value: 5, name: 'ጥር'}, {value: 6, name: 'የካቲት'}, 
                    {value: 7, name: 'መጋቢት'}, {value: 8, name: 'ሚያዝያ'}, 
                    {value: 9, name: 'ግንቦት'}, {value: 10, name: 'ሰኔ'}, 
                    {value: 11, name: 'ሐምሌ'}, {value: 12, name: 'ነሐሴ'}, 
                    {value: 13, name: 'ጷጉሜ'}
                ];
            }
            
            getYearsList() {
                const currentYear = this.currentDate.year;
                const years = [];
                for (let i = currentYear - 10; i <= currentYear + 10; i++) {
                    years.push(i);
                }
                return years;
            }
            
            renderCalendar() {
                if (this.currentDate.month < 1) this.currentDate.month = 1;
                if (this.currentDate.month > 13) this.currentDate.month = 13;
                
                const monthName = this.getEthiopianMonthName(this.currentDate.month);
                const dayNames = this.getDayNames();
                const daysInMonth = this.getDaysInMonth(this.currentDate.year, this.currentDate.month);
                const months = this.getMonthsList();
                const years = this.getYearsList();
                
                const firstDayOfWeek = this.getFirstDayOfMonth(this.currentDate.year, this.currentDate.month);
                
                let calendarHTML = `
                    <div class="ethiopian-calendar">
                        <div class="calendar-header">
                            <button type="button" class="calendar-nav-btn prev-year" title="Previous Year">‹‹</button>
                            <button type="button" class="calendar-nav-btn prev-month" title="Previous Month">‹</button>
                            <span class="calendar-title">${monthName} ${this.currentDate.year}</span>
                            <button type="button" class="calendar-nav-btn next-month" title="Next Month">›</button>
                            <button type="button" class="calendar-nav-btn next-year" title="Next Year">››</button>
                        </div>
                        
                        <div class="year-month-selectors">
                            <select class="year-selector">
                                ${years.map(year => 
                                    `<option value="${year}" ${year === this.currentDate.year ? 'selected' : ''}>${year}</option>`
                                ).join('')}
                            </select>
                            <select class="month-selector">
                                ${months.map(month => 
                                    `<option value="${month.value}" ${month.value === this.currentDate.month ? 'selected' : ''}>${month.name}</option>`
                                ).join('')}
                            </select>
                        </div>
                        
                        <div class="calendar-grid">
                `;
                
                // Day headers
                dayNames.forEach(day => {
                    calendarHTML += `<div class="calendar-day-header">${day}</div>`;
                });
                
                // Empty cells for days before the first day of the month
                for (let i = 0; i < firstDayOfWeek; i++) {
                    calendarHTML += `<div class="calendar-day other-month">&nbsp;</div>`;
                }
                
                // Days of the month
                for (let day = 1; day <= daysInMonth; day++) {
                    const isSelected = this.selectedDate && 
                                    this.selectedDate.year === this.currentDate.year && 
                                    this.selectedDate.month === this.currentDate.month && 
                                    this.selectedDate.day === day;
                    const isToday = this.currentDate.year === this.currentDate.year && 
                                this.currentDate.month === this.currentDate.month && 
                                day === this.currentDate.day;
                    
                    const selectedClass = isSelected ? 'selected' : '';
                    const todayClass = isToday ? 'today' : '';
                    
                    calendarHTML += `
                        <button type="button" class="calendar-day ${selectedClass} ${todayClass}" 
                                data-day="${day}" 
                                data-month="${this.currentDate.month}" 
                                data-year="${this.currentDate.year}">
                            ${day}
                        </button>
                    `;
                }
                
                // Fill remaining cells
                const totalCells = 42;
                const filledCells = firstDayOfWeek + daysInMonth;
                const remainingCells = Math.max(0, totalCells - filledCells);
                
                for (let i = 0; i < remainingCells; i++) {
                    calendarHTML += `<div class="calendar-day other-month">&nbsp;</div>`;
                }
                
                calendarHTML += `
                        </div>
                        
                        <div class="calendar-actions">
                            <button type="button" class="today-btn">Today</button>
                            <button type="button" class="clear-btn">Clear</button>
                        </div>
                    </div>
                `;
                
                this.calendarElement.innerHTML = calendarHTML;
                this.attachEventListeners();
            }
            
            attachEventListeners() {
                const stopPropagation = (e) => e.stopPropagation();
                
                // Navigation buttons
                this.calendarElement.querySelector('.prev-year').addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.currentDate.year--;
                    this.renderCalendar();
                });
                
                this.calendarElement.querySelector('.prev-month').addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.navigateMonth(-1);
                });
                
                this.calendarElement.querySelector('.next-month').addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.navigateMonth(1);
                });
                
                this.calendarElement.querySelector('.next-year').addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.currentDate.year++;
                    this.renderCalendar();
                });
                
                // Year selector
                this.calendarElement.querySelector('.year-selector').addEventListener('change', (e) => {
                    e.stopPropagation();
                    this.currentDate.year = parseInt(e.target.value);
                    this.renderCalendar();
                });
                
                // Month selector
                this.calendarElement.querySelector('.month-selector').addEventListener('change', (e) => {
                    e.stopPropagation();
                    const newMonth = parseInt(e.target.value);
                    if (newMonth >= 1 && newMonth <= 13) {
                        this.currentDate.month = newMonth;
                        this.currentDate.day = 1;
                        this.renderCalendar();
                    }
                });
                
                // Day selection
                setTimeout(() => {
                    const dayButtons = this.calendarElement.querySelectorAll('.calendar-day:not(.other-month)');
                    dayButtons.forEach(dayBtn => {
                        dayBtn.addEventListener('click', (e) => {
                            e.stopPropagation();
                            const day = parseInt(dayBtn.textContent);
                            const month = parseInt(dayBtn.dataset.month);
                            const year = parseInt(dayBtn.dataset.year);
                            this.selectDate(year, month, day);
                        });
                    });
                }, 0);
                
                // Today button
                this.calendarElement.querySelector('.today-btn').addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.currentDate = this.getCurrentEthiopianDate();
                    this.selectDate(this.currentDate.year, this.currentDate.month, this.currentDate.day);
                });
                
                // Clear button
                this.calendarElement.querySelector('.clear-btn').addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.input.value = '';
                    this.targetInput.value = '';
                    this.selectedDate = null;
                    this.hideCalendar();
                });
                
                this.calendarElement.addEventListener('click', stopPropagation);
            }
            
            navigateMonth(direction) {
                let newMonth = this.currentDate.month + direction;
                let newYear = this.currentDate.year;
                
                if (newMonth > 13) {
                    newMonth = 1;
                    newYear++;
                } else if (newMonth < 1) {
                    newMonth = 13;
                    newYear--;
                }
                
                this.currentDate = { 
                    year: newYear, 
                    month: Math.max(1, Math.min(13, newMonth)), 
                    day: 1 
                };
                this.renderCalendar();
            }
            
            selectDate(year, month, day) {
                if (year && month >= 1 && month <= 13 && day >= 1 && day <= 30) {
                    this.selectedDate = { year, month, day };
                    this.currentDate = { year, month, day };
                    
                    const formattedEthiopianDate = `${year}-${month.toString().padStart(2, '0')}-${day.toString().padStart(2, '0')}`;
                    this.input.value = formattedEthiopianDate;
                    
                    // Get current time
                    const now = new Date();
                    const hours = now.getHours().toString().padStart(2, '0');
                    const minutes = now.getMinutes().toString().padStart(2, '0');
                    
                    // For Ethiopian calendar, send Ethiopian date directly to backend
                    // Backend will handle conversion
                    this.targetInput.value = `${formattedEthiopianDate}T${hours}:${minutes}`;
                }
                
                this.hideCalendar();
            }
            
            showCalendar() {
                this.calendarElement.style.display = 'block';
                this.isOpen = true;
                this.renderCalendar();
                this.calendarElement.scrollTop = 0;
            }
            
            hideCalendar() {
                this.calendarElement.style.display = 'none';
                this.isOpen = false;
            }
            
            toggleCalendar() {
                if (this.isOpen) {
                    this.hideCalendar();
                } else {
                    this.showCalendar();
                }
            }
            
            init() {
                // Always set initial Ethiopian date to current date
                const currentDate = this.getCurrentEthiopianDate();
                this.selectDate(currentDate.year, currentDate.month, currentDate.day);
                
                this.input.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.toggleCalendar();
                });
                
                document.addEventListener('click', (e) => {
                    if (this.isOpen && 
                        !this.calendarElement.contains(e.target) && 
                        e.target !== this.input) {
                        this.hideCalendar();
                    }
                });
            }
        }

        // Date Manager to handle all date operations
        class DateManager {
            constructor() {
                this.calendarType = 'EC';
                this.dateTypeField = document.getElementById('date_type_field');
                this.calendarTypeSelect = document.getElementById('calendar_type');
                this.appointmentPicker = null;
                this.pickupPicker = null;
                
                this.init();
            }
            
            init() {
                // Initialize date pickers
                this.appointmentPicker = new EthiopianDatePicker('ec_appointment_date', 'ec_appointment_calendar');
                this.pickupPicker = new EthiopianDatePicker('ec_pickup_date', 'ec_pickup_calendar');
                
                // Set initial dates
                this.setInitialDates();
                
                // Setup calendar type change listener
                this.setupCalendarTypeListener();
                
                // Setup form submission handler
                this.setupFormSubmission();
            }
            
            setInitialDates() {
                // Set appointment date to current date
                const currentEthDate = this.appointmentPicker.getCurrentEthiopianDate();
                this.appointmentPicker.selectDate(currentEthDate.year, currentEthDate.month, currentEthDate.day);
                
                // Set pickup date to current Ethiopian date
                this.pickupPicker.selectDate(currentEthDate.year, currentEthDate.month, currentEthDate.day);
            }
            
            setupCalendarTypeListener() {
                if (this.calendarTypeSelect) {
                    this.calendarTypeSelect.addEventListener('change', (e) => {
                        this.calendarType = e.target.value;
                        this.dateTypeField.value = this.calendarType;
                        
                        console.log('Calendar type changed to:', this.calendarType);
                        console.log('Date type field value:', this.dateTypeField.value);
                        
                        this.toggleCalendarDisplay();
                        this.syncDates();
                    });
                }
            }
            
            toggleCalendarDisplay() {
                const isEC = this.calendarType === 'EC';
                
                // Toggle visibility
                document.querySelectorAll('input[type="datetime-local"]').forEach(input => {
                    input.style.display = isEC ? 'none' : 'block';
                });
                
                document.querySelectorAll('.ethiopian-date-picker-container').forEach(container => {
                    container.style.display = isEC ? 'block' : 'none';
                });
            }
            
            syncDates() {
                if (this.calendarType === 'GC') {
                    // Switching to Gregorian - set current date/time for both dates
                    const currentDateTime = this.appointmentPicker.getCurrentGregorianDateTime();
                    document.getElementById('gc_appointment_date').value = currentDateTime;
                    
                    // Set pickup date to current date (not +7 days)
                    document.getElementById('gc_pickup_date').value = currentDateTime;
                } else {
                    // Switching to Ethiopian - use the Ethiopian pickers
                    const currentEthDate = this.appointmentPicker.getCurrentEthiopianDate();
                    this.appointmentPicker.selectDate(currentEthDate.year, currentEthDate.month, currentEthDate.day);
                    this.pickupPicker.selectDate(currentEthDate.year, currentEthDate.month, currentEthDate.day);
                }
            }
            
            setupFormSubmission() {
                const form = document.querySelector('form');
                if (form) {
                    form.addEventListener('submit', (e) => {
                        // Ensure all date fields are properly set before submission
                        this.prepareFormData();
                        
                        // Validate required dates
                        if (!this.validateDates()) {
                            e.preventDefault();
                            return;
                        }
                        
                        console.log('Form submission - Date type:', this.dateTypeField.value);
                        console.log('Appointment date:', document.getElementById('gc_appointment_date').value);
                        console.log('Pickup date:', document.getElementById('gc_pickup_date').value);
                    });
                }
            }
            
            prepareFormData() {
                // Always ensure date_type is set correctly
                this.dateTypeField.value = this.calendarType;
                
                if (this.calendarType === 'EC') {
                    // For Ethiopian dates, ensure both Ethiopian and Gregorian fields are set
                    const appointmentEthDate = document.getElementById('ec_appointment_date').value;
                    const pickupEthDate = document.getElementById('ec_pickup_date').value;
                    
                    if (appointmentEthDate && !document.getElementById('gc_appointment_date').value) {
                        const now = new Date();
                        const hours = now.getHours().toString().padStart(2, '0');
                        const minutes = now.getMinutes().toString().padStart(2, '0');
                        document.getElementById('gc_appointment_date').value = `${appointmentEthDate}T${hours}:${minutes}`;
                    }
                    
                    if (pickupEthDate && !document.getElementById('gc_pickup_date').value) {
                        document.getElementById('gc_pickup_date').value = `${pickupEthDate}T12:00`;
                    }
                }
            }
            
            validateDates() {
                const appointmentDate = document.getElementById('gc_appointment_date').value;
                const pickupDate = document.getElementById('gc_pickup_date').value;
                
                if (!appointmentDate) {
                    alert('Please select an appointment date');
                    return false;
                }
                
                if (!pickupDate) {
                    alert('Please select a pickup date');
                    return false;
                }
                
                return true;
            }
        }

        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof EthiopicCalendar === 'undefined') {
                console.warn('Ethiopic calendar library not loaded, using fallback conversion');
            }
            
            // Initialize the date manager
            window.dateManager = new DateManager();
        });
    </script>

    <script>
        // Map of cloth_item_id => array of allowed service_ids (pricing tiers)
        const PRICING_SERVICE_MAP = @json($pricingServiceMap ?? []);
        
        function customerSelect() {
            return {
                query: '',
                results: [],
                selectedId: '',
                open: false,
                highlighted: -1,
                openModal: false,
                modal: { name: '', code: '', is_vip: false, phone: '', address: '', error: '' },
                init() {
                    // If an existing hidden value present, try to lookup label (optional)
                    const hid = document.querySelector('input[type=hidden][name=customer_id]');
                    if (hid && hid.value) {
                        this.selectedId = hid.value;
                        fetch(`/api/customers/search?q=`).then(r=>r.json()).then(d=>{}).catch(()=>{});
                    }
                },
                search() {
                    // If query empty, load initial list (first 20) so dropdown acts like a searchable select
                    const q = (this.query || '').trim();
                    const url = `/api/customers/search?q=${encodeURIComponent(q)}`;
                    fetch(url)
                        .then(r => r.json())
                        .then(data => { this.results = data.map(c => ({ id: c.id, label: `${c.name} (${c.phone})` })); this.open = this.results.length>0; this.highlighted = 0; });
                },
                openList() { if (this.results.length===0) { this.search(); } this.open = this.results.length>0; },
                closeList() { this.open = false; },
                highlightNext() { if(!this.open) return; this.highlighted = Math.min(this.highlighted+1, this.results.length-1); this.scrollToHighlighted(); },
                highlightPrev() { if(!this.open) return; this.highlighted = Math.max(this.highlighted-1, 0); this.scrollToHighlighted(); },
                scrollToHighlighted() { const ul = document.getElementById('customer-list'); if (!ul) return; const item = ul.children[this.highlighted]; if (item) item.scrollIntoView({ block: 'nearest' }); },
                chooseHighlighted() { if (this.open && this.results[this.highlighted]) this.choose(this.results[this.highlighted]); },
                choose(r) { this.selectedId = r.id; this.query = r.label; this.results = []; this.open = false; },
                async addCustomer() {
                    this.modal.error = '';
                    try {
                        const token = document.querySelector('meta[name=csrf-token]').content;
                        const res = await fetch('{{ route('customers.store') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': token
                            },
                            body: JSON.stringify({ name: this.modal.name, phone: this.modal.phone, address: this.modal.address, code: this.modal.code, is_vip: this.modal.is_vip })
                        });

                        const contentType = res.headers.get('content-type') || '';
                        if (contentType.includes('application/json')) {
                            const payload = await res.json();
                            if (res.status === 201) {
                                this.selectedId = payload.id;
                                this.query = payload.label;
                                this.openModal = false;
                                this.modal = { name:'', code:'', is_vip:false, phone:'', address:'', error: '' };
                                return;
                            }
                            if (res.status === 422) {
                                const first = Object.values(payload.errors || payload)[0];
                                this.modal.error = Array.isArray(first) ? first[0] : (payload.message || 'Validation failed');
                                return;
                            }
                            this.modal.error = payload.message || 'Failed to create customer';
                            return;
                        }

                        // Non-JSON response (HTML) - show status text or HTML snippet
                        const text = await res.text();
                        this.modal.error = (res.status >= 400) ? (text.replace(/<[^>]*>/g, '').slice(0, 300) || res.statusText) : 'Unexpected response';
                    } catch (e) { this.modal.error = e.message; }
                }
            }
        }
        
        let itemIndex = 0;
        const servicesList = @json($services->map(fn($s)=>['id'=>$s->id,'name'=>$s->name]));
        const urgencyOptions = `@foreach($urgencyTiers as $tier)<option value="{{ $tier->id }}">{{ $tier->label }}</option>@endforeach`;
        const UNITS = @json($units->map(fn($u) => [ 'id' => $u->id, 'name' => $u->name, 'root_id' => $unitRootIds[$u->id] ?? null ]));
        const CLOTH_ROOTS = @json($clothRootIds);
        const CLOTH_UNIT_IDS = @json($clothUnitIds);
        
        // Generate remark presets HTML server-side
        const remarkPresetsHTML = `@foreach($remarkPresets as $rp)<label class="inline-flex items-center gap-2 border rounded px-2 py-1 text-xs"><input type="checkbox" name="items[INDEX][remark_preset_ids][]" value="{{ $rp->id }}" class="rounded"><span>{{ $rp->label }}</span></label>@endforeach`;

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

        function handleClothChange(e) {
            const selectEl = e.target;
            if (!(selectEl.name && selectEl.name.endsWith('[cloth_item_id]'))) return;
            const clothId = selectEl.value;
            const itemWrapper = selectEl.closest('.item');
            const rootId = CLOTH_ROOTS[clothId] || '';
            const unitSelect = itemWrapper?.querySelector('select[name$="[unit_id]"]');
            if (unitSelect) {
                populateUnitOptions(unitSelect, rootId);
                const defUnit = CLOTH_UNIT_IDS[clothId];
                if (defUnit && [...unitSelect.options].some(o => o.value === String(defUnit))) {
                    unitSelect.value = String(defUnit);
                }
            }
            filterServicesForItem(itemWrapper, clothId);
        }

        function filterServicesForItem(itemWrapper, clothId) {
            if (!itemWrapper) return;
            const allowed = (PRICING_SERVICE_MAP[clothId] || []).map(id => parseInt(id,10));
            const lines = itemWrapper.querySelectorAll('[data-service-line]');
            let visibleCount = 0;
            lines.forEach(line => {
                const sid = parseInt(line.getAttribute('data-service-id'), 10);
                const show = allowed.includes(sid);
                line.style.display = show ? '' : 'none';
                if (!show) {
                    // Clear any selections/inputs if hidden
                    const cb = line.querySelector('input[type="checkbox"]'); if (cb) cb.checked = false;
                    const qty = line.querySelector('input[type="number"]'); if (qty) qty.value='';
                } else { visibleCount++; }
            });
            // Message handling
            let msg = itemWrapper.querySelector('.no-priced-services-msg');
            if (!msg) {
                msg = document.createElement('div');
                msg.className = 'no-priced-services-msg text-xs text-amber-600 mt-2';
                const svcTable = itemWrapper.querySelector('[data-services-wrapper]');
                svcTable?.parentNode?.appendChild(msg);
            }
            if (clothId && visibleCount === 0) {
                msg.textContent = 'No priced services available for this cloth item.';
                msg.style.display = '';
            } else {
                msg.textContent = '';
                msg.style.display = 'none';
            }
        }

        document.addEventListener('change', handleClothChange);

        function toggleModes() {
            const applyAll = document.getElementById('apply_all_services').checked;
            document.querySelectorAll('.services-panel').forEach(panel => {
                panel.classList.toggle('opacity-50', applyAll);
                panel.classList.toggle('pointer-events-none', applyAll);
            });
        }
        document.getElementById('apply_all_services').addEventListener('change', toggleModes);
        toggleModes();

        document.getElementById('mark_all_urgent').addEventListener('change', e => {
            const tierSelect = document.getElementById('all_urgency_tier_id');
            tierSelect.disabled = !e.target.checked;
        });

        document.getElementById('select_all_services').addEventListener('click', () => {
            document.querySelectorAll('.svc-check').forEach(cb => { cb.checked = true; });
        });

        // Load Tom Select for searchable dropdowns (no build step)
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
                try { if (!el.tomselect) new TomSelect(el, { create: false, sortField: { field: 'text', direction: 'asc' } }); } catch(_) {}
            });
        }

        document.querySelector('.add-item').addEventListener('click', () => {
            itemIndex++;
            const wrapper = document.getElementById('items-container');
            
            // Create remark presets HTML with proper item index
            const remarksHtml = remarkPresetsHTML.replace(/INDEX/g, itemIndex);
            
            const lines = servicesList.map(s => `
                <div class="flex flex-wrap items-center gap-2 border rounded p-2 bg-gray-50" data-service-line data-service-id="${s.id}">
                    <label class="inline-flex items-center gap-1 mr-2">
                        <input type="checkbox" name="items[${itemIndex}][services][${s.id}][selected]" value="1" class="svc-check">
                        <span class="text-xs font-medium">${s.name}</span>
                    </label>
                    <div class="flex items-center gap-1">
                        <span class="text-[11px] text-gray-500">Qty</span>
                        <input type="number" step="0.01" name="items[${itemIndex}][services][${s.id}][quantity]" class="w-20 border rounded p-1 text-xs" placeholder="auto">
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="text-[11px] text-gray-500">Urgency</span>
                        <select name="items[${itemIndex}][services][${s.id}][urgency_tier_id]" class="border rounded p-1 text-xs"><option value="">Item/Global</option>${urgencyOptions}</select>
                    </div>
                </div>`).join('');
            
            const html = `<div class="item border p-4 rounded" data-item-index="${itemIndex}">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                    <h3 class="text-sm font-semibold text-gray-800">Item</h3>
                    <div class="flex items-center gap-2">
                        <label class="text-xs text-gray-600">Copy services from</label>
                        <select class="copy-from border rounded p-1 text-xs min-w-40">
                            <option value="">Select item…</option>
                        </select>
                        <button type="button" class="copy-btn text-xs text-blue-600 underline">Copy</button>
                    </div>
                    <button type="button" class="remove-item text-xs text-red-600 underline">Remove item</button>
                </div>
                <div class="grid md:grid-cols-5 gap-4 mb-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium">Cloth Item</label>
                        <select name="items[${itemIndex}][cloth_item_id]" class="w-full border rounded p-2 cloth-select" required>
                            <option value="">Select Item</option>
                            @foreach ($clothItems as $item)
                                <option value="{{ $item->id }}">{{ $item->item_code }} {{ $item->name }} ({{ $item->unit->name }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Unit</label>
                        <select name="items[${itemIndex}][unit_id]" class="w-full border rounded p-2" required>
                            <option value="">Select Unit</option>
                            @foreach ($units as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Quantity</label>
                        <input type="number" name="items[${itemIndex}][quantity]" step="0.01" class="w-full border rounded p-2" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Item Urgency (default)</label>
                        <select name="items[${itemIndex}][default_urgency_tier_id]" class="w-full border rounded p-2"><option value="">None</option>@foreach ($urgencyTiers as $tier)<option value="{{ $tier->id }}">{{ $tier->label }}</option>@endforeach</select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="block text-sm font-medium">Remarks</label>
                    <div class="mb-1 text-xs text-gray-600">Common remarks (this item)</div>
                    <div class="flex flex-wrap gap-2 mb-2" id="item-remarks-${itemIndex}">
                        ${remarksHtml}
                    </div>
                    <textarea name="items[${itemIndex}][remarks]" class="w-full border rounded p-2" placeholder="Item-specific remarks..."></textarea>
                </div>
                <div class="services-panel">
                    <h3 class="text-sm font-semibold mb-2 flex items-center justify-between">Services (manual mode)
                        <span class="flex items-center gap-2">
                            <button type="button" class="item-select-all text-xs text-blue-600 underline">Select all</button>
                            <button type="button" class="item-select-none text-xs text-blue-600 underline">Select none</button>
                            <button type="button" class="item-clear text-xs text-slate-700 underline">Clear</button>
                            <button type="button" class="text-xs text-blue-600 underline add-svc-row" data-item="${itemIndex}">Add custom row</button>
                        </span>
                    </h3>
                    <div class="service-table space-y-2" data-services-wrapper>${lines}</div>
                </div>
            </div>`;
            
            wrapper.insertAdjacentHTML('beforeend', html);
            toggleModes();
            initClothSelects();
            refreshCopyOptions();
            
            // Initialize the new item
            const newRow = wrapper.querySelector('.item:last-of-type');
            const clothSel = newRow?.querySelector('select[name$="[cloth_item_id]"]');
            if (clothSel) {
                const evt = new Event('change', { bubbles: true });
                clothSel.dispatchEvent(evt);
            }
        });

        // Remove item handler with safeguard to keep at least one item
        document.addEventListener('click', (e) => {
            if (e.target && e.target.classList.contains('remove-item')) {
                e.preventDefault();
                const items = document.querySelectorAll('#items-container .item');
                if (items.length <= 1) {
                    alert('At least one item is required.');
                    return;
                }
                const item = e.target.closest('.item');
                if (item) item.remove();
                refreshCopyOptions();
            }
            if (e.target && e.target.classList.contains('item-select-all')) {
                const item = e.target.closest('.item');
                item?.querySelectorAll('[data-service-line] input[type="checkbox"]').forEach(cb => cb.checked = true);
            }
            if (e.target && e.target.classList.contains('item-select-none')) {
                const item = e.target.closest('.item');
                item?.querySelectorAll('[data-service-line] input[type="checkbox"]').forEach(cb => cb.checked = false);
            }
            if (e.target && e.target.classList.contains('item-clear')) {
                const item = e.target.closest('.item');
                if (!item) return;
                item.querySelectorAll('[data-service-line]').forEach(row => {
                    const cb = row.querySelector('input[type="checkbox"]');
                    const qty = row.querySelector('input[type="number"]');
                    const urg = row.querySelector('select');
                    if (cb) cb.checked = false;
                    if (qty) qty.value = '';
                    if (urg) urg.value = '';
                });
            }
            if (e.target && e.target.classList.contains('copy-btn')) {
                e.preventDefault();
                const toItem = e.target.closest('.item');
                const select = toItem.querySelector('.copy-from');
                const fromIndex = select.value;
                if (!fromIndex) return;
                copyServices(parseInt(fromIndex, 10), parseInt(toItem.getAttribute('data-item-index'), 10));
            }
        });

        function refreshCopyOptions() {
            const items = Array.from(document.querySelectorAll('#items-container .item'));
            const indices = items.map(it => parseInt(it.getAttribute('data-item-index'), 10));
            items.forEach(toItem => {
                const toIdx = parseInt(toItem.getAttribute('data-item-index'), 10);
                const select = toItem.querySelector('.copy-from');
                if (!select) return;
                const prev = select.value;
                select.innerHTML = '<option value="">Select item…</option>' + indices
                    .filter(i => i !== toIdx)
                    .map(i => `<option value="${i}">Item #${i+1}</option>`)
                    .join('');
                if (prev && [...select.options].some(o => o.value === prev)) {
                    select.value = prev;
                }
            });
        }

        function copyServices(fromIndex, toIndex) {
            const fromItem = document.querySelector(`.item[data-item-index="${fromIndex}"]`);
            const toItem = document.querySelector(`.item[data-item-index="${toIndex}"]`);
            if (!fromItem || !toItem) return;
            // Prevent copying services not priced for destination cloth item
            const clothSelect = toItem.querySelector('select[name$="[cloth_item_id]"]');
            const clothId = clothSelect ? clothSelect.value : null;
            const allowed = (PRICING_SERVICE_MAP[clothId] || []).map(i=>parseInt(i,10));
            const fromRows = fromItem.querySelectorAll('[data-service-line]');
            const toRows = toItem.querySelectorAll('[data-service-line]');
            const fromMap = new Map();
            fromRows.forEach(r => {
                const id = r.getAttribute('data-service-id');
                if (id) fromMap.set(id, r);
            });
            toRows.forEach(r => {
                const id = r.getAttribute('data-service-id');
                const src = id ? fromMap.get(id) : null;
                if (!src) return;
                if (clothId && allowed.length && !allowed.includes(parseInt(id,10))) { return; }
                const srcCheck = src.querySelector('input[type="checkbox"]');
                const dstCheck = r.querySelector('input[type="checkbox"]');
                if (srcCheck && dstCheck) dstCheck.checked = srcCheck.checked;
                const srcQty = src.querySelector('input[type="number"]');
                const dstQty = r.querySelector('input[type="number"]');
                if (srcQty && dstQty) dstQty.value = srcQty.value;
                const srcUrg = src.querySelector('select');
                const dstUrg = r.querySelector('select');
                if (srcUrg && dstUrg) dstUrg.value = srcUrg.value;
            });
            filterServicesForItem(toItem, clothId);
        }

        // Initial setup
        refreshCopyOptions();
        // For first row and any preselected cloth items, trigger service filtering
        document.querySelectorAll('select[name$="[cloth_item_id]"]').forEach(sel => {
            const evt = new Event('change', { bubbles: true });
            sel.dispatchEvent(evt);
        });
    </script>
</x-app-layout>