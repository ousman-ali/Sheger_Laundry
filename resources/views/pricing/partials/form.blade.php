@props(['pricingTier' => null, 'clothItems', 'services', 'action', 'method'])
<form action="{{ $action }}" method="POST" class="space-y-4">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <div>
        <label for="cloth_item_id" class="block text-sm font-medium mb-1">Cloth Item</label>
        <select name="cloth_item_id" id="cloth_item_id" class="w-full border rounded p-2 cloth-item-select" required>
            <option value="">Select cloth item</option>
            @foreach ($clothItems as $item)
                <option value="{{ $item->id }}" @selected((int) old('cloth_item_id', $pricingTier->cloth_item_id ?? '') === $item->id)>
                    {{ $item->name }} ({{ $item->unit->name }})
                </option>
            @endforeach
        </select>
        @error('cloth_item_id')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="service_id" class="block text-sm font-medium">Service</label>
    <select name="service_id" id="service_id" class="w-full border rounded p-2 service-select" required>
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

<script>
// Inline (layout lacks @stack('scripts'))
(function loadTomSelectPricing(){
    function init(){
        document.querySelectorAll('select.cloth-item-select').forEach(el => {
            try { if (!el.tomselect) new TomSelect(el, { create:false, maxOptions:500, sortField:{field:'text',direction:'asc'} }); } catch(_) {}
        });
        document.querySelectorAll('select.service-select').forEach(el => {
            try { if (!el.tomselect) new TomSelect(el, { create:false, sortField:{field:'text',direction:'asc'} }); } catch(_) {}
        });
    }
    if (window.TomSelect) { init(); return; }
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = 'https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css';
    document.head.appendChild(link);
    const s = document.createElement('script');
    s.src = 'https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js';
    s.onload = init;
    document.head.appendChild(s);
    // Fallback: if TomSelect failed to load after 1500ms, inject a manual filter input
    setTimeout(() => {
        if (window.TomSelect) return; // loaded successfully
        document.querySelectorAll('select.cloth-item-select').forEach(sel => {
            if (sel.dataset.enhancedFallback) return;
            sel.dataset.enhancedFallback = '1';
            const wrap = document.createElement('div');
            wrap.className = 'space-y-1';
            const filter = document.createElement('input');
            filter.type = 'text';
            filter.placeholder = 'Type to filter...';
            filter.className = 'w-full border rounded p-1 text-xs';
            filter.addEventListener('input', () => {
                const q = filter.value.toLowerCase();
                [...sel.options].forEach(o => {
                    if (!o.value) return; // keep placeholder always
                    o.hidden = !o.text.toLowerCase().includes(q);
                });
            });
            sel.parentNode.insertBefore(wrap, sel);
            wrap.appendChild(filter);
            wrap.appendChild(sel);
        });
    }, 1500);
})();
</script>

