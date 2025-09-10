<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Record Payment') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                @if ($errors->any())
                    <div class="mb-4 p-3 rounded bg-red-50 text-red-700">
                        <ul class="list-disc list-inside text-sm">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <form action="{{ route('payments.store') }}" method="POST" class="space-y-4" id="payment-form">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium">Order</label>
                        <div class="relative">
                            <input type="hidden" name="order_id" id="order_id" value="{{ old('order_id', request('order_id')) }}">
                            <input type="text" id="order_search" class="w-full border rounded p-2" placeholder="Search by order, customer name, phone, date..." autocomplete="off" />
                            <ul id="order_results" class="absolute z-10 bg-white border rounded w-full mt-1 max-h-56 overflow-auto hidden"></ul>
                        </div>
                        <p id="suggest-note" class="text-xs text-gray-600 mt-1 hidden"></p>
                        <span id="has-penalty" class="text-xs text-amber-700 ml-1 hidden">(has penalty)</span>
                        <p id="penalty-note" class="text-xs mt-1 hidden"></p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium">Amount</label>
                            <input type="number" step="0.01" name="amount" id="amount" class="w-full border rounded p-2" value="{{ old('amount') }}" required />
                        </div>
                        <div x-data="{ method: '{{ old('payment_method','cash') }}' }">
                            <label class="block text-sm font-medium">Method</label>
                            <select name="payment_method" class="w-full border rounded p-2" x-model="method">
                                <option value="cash">Cash</option>
                                <option value="bank">Bank</option>
                            </select>
                            <div class="mt-2" x-show="method==='bank'">
                                <label class="block text-sm font-medium">Bank</label>
                                <select name="bank_id" class="w-full border rounded p-2">
                                    @foreach(\App\Models\Bank::where('is_active',true)->orderBy('name')->get() as $bank)
                                        <option value="{{ $bank->id }}" @selected(old('bank_id')==$bank->id)>{{ $bank->name }}@if($bank->branch) — {{ $bank->branch }}@endif</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Status</label>
                            <select name="status" class="w-full border rounded p-2">
                                @foreach(['completed','pending','refunded'] as $s)
                                    <option value="{{ $s }}" @selected(old('status')===$s)>{{ ucfirst($s) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium">Paid At</label>
                            <input type="datetime-local" name="paid_at" class="w-full border rounded p-2" value="{{ old('paid_at', now()->format('Y-m-d\\TH:i')) }}" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Notes</label>
                            <input name="notes" class="w-full border rounded p-2" value="{{ old('notes') }}" />
                        </div>
                    </div>

                    <div class="border rounded p-3">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="waived_penalty" id="waived_penalty" class="rounded" @checked(old('waived_penalty'))>
                            <span class="text-sm">Request to waive penalty</span>
                        </label>
                        <div id="waiver_reason_wrap" class="mt-2 hidden">
                            <label class="block text-sm font-medium">Reason for waiver (required)</label>
                            <textarea name="waiver_reason" id="waiver_reason" class="w-full border rounded p-2" rows="2" placeholder="Customer reason...">{{ old('waiver_reason') }}</textarea>
                            <p class="text-xs text-amber-700 mt-1">If there is an active penalty and you are not an Admin, this payment will require Admin approval.</p>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <a href="{{ route('payments.index') }}" class="px-4 py-2 rounded border">Cancel</a>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    const orderHidden = document.getElementById('order_id');
    const orderInput = document.getElementById('order_search');
    const orderResults = document.getElementById('order_results');
        const amountInput = document.getElementById('amount');
        const note = document.getElementById('suggest-note');
    const waive = document.getElementById('waived_penalty');
        const waiveWrap = document.getElementById('waiver_reason_wrap');
    const penaltyNote = document.getElementById('penalty-note');

    function fmt(n){ return Number(n).toFixed(2); }

        async function fetchSuggestion(orderId){
            if(!orderId){ amountInput.value=''; note.classList.add('hidden'); return; }
            try {
                const res = await fetch(`{{ route('payments.suggest') }}?order_id=${orderId}`, { headers: { 'Accept': 'application/json' }});
                if(!res.ok) return;
                const data = await res.json();
                amountInput.value = fmt(data.due ?? data.total);
                note.textContent = `Suggested: base ${fmt(data.base)} + penalty ${fmt(data.penalty)} = total ${fmt(data.total)} — paid ${fmt(data.paid ?? 0)} — due ${fmt(data.due ?? data.total)}`;
                note.classList.remove('hidden');
                if(Number(data.penalty) > 0){
                    document.getElementById('has-penalty').classList.remove('hidden');
                    penaltyNote.textContent = 'Penalty applies — waiving requires Admin approval for non-admin users.';
                    penaltyNote.className = 'text-xs text-amber-700 mt-1';
                    penaltyNote.classList.remove('hidden');
                } else {
                    document.getElementById('has-penalty').classList.add('hidden');
                    penaltyNote.classList.add('hidden');
                }
            } catch(e) { /* ignore */ }
        }
        async function searchOrders(term){
            const url = new URL(`{{ route('payments.order-search') }}`);
            if(term) url.searchParams.set('q', term);
            const res = await fetch(url, { headers: { 'Accept': 'application/json' }});
            if(!res.ok) return [];
            return await res.json();
        }
        function renderResults(items){
            orderResults.innerHTML='';
            if(!items.length){ orderResults.classList.add('hidden'); return; }
            items.forEach(it=>{
                const li = document.createElement('li');
                const labelSpan = document.createElement('span');
                labelSpan.textContent = it.label;
                li.appendChild(labelSpan);
                if(it.has_penalty){
                    const badge = document.createElement('span');
                    badge.textContent = 'Penalty';
                    badge.className = 'ml-2 inline-block px-1.5 py-0.5 rounded text-[10px] bg-amber-100 text-amber-800';
                    li.appendChild(badge);
                }
                li.className = 'px-2 py-1 hover:bg-gray-100 cursor-pointer flex items-center justify-between';
                li.addEventListener('click', ()=>{
                    orderHidden.value = it.id;
                    orderInput.value = it.label;
                    orderResults.classList.add('hidden');
                    fetchSuggestion(it.id);
                });
                orderResults.appendChild(li);
            });
            orderResults.classList.remove('hidden');
        }
        let searchDebounce;
        orderInput.addEventListener('focus', async () => {
            // Load initial list on focus for scrolling
            const items = await searchOrders('');
            renderResults(items);
        });
        orderInput.addEventListener('click', async () => {
            if(orderResults.classList.contains('hidden')){
                const items = await searchOrders('');
                renderResults(items);
            }
        });
        orderInput.addEventListener('input', e => {
            const term = e.target.value.trim();
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(async ()=>{
                const items = await searchOrders(term);
                renderResults(items);
            }, 200);
        });
        document.addEventListener('click', (e)=>{ if(!orderResults.contains(e.target) && e.target!==orderInput){ orderResults.classList.add('hidden'); }});
        document.addEventListener('DOMContentLoaded', async () => {
            // hydrate initial selection if old('order_id') exists
            if(orderHidden.value){
                const items = await searchOrders('');
                const match = items.find(i=>String(i.id)===String(orderHidden.value));
                if(match){ orderInput.value = match.label; }
                fetchSuggestion(orderHidden.value);
            }
            if(waive.checked){ waiveWrap.classList.remove('hidden'); }
        });
        waive.addEventListener('change', e => {
            if(e.target.checked){ waiveWrap.classList.remove('hidden'); } else { waiveWrap.classList.add('hidden'); }
        });
        document.getElementById('payment-form').addEventListener('submit', e => {
            if(waive.checked){
                const r = document.getElementById('waiver_reason').value.trim();
                if(!r){ e.preventDefault(); alert('Please provide reason for penalty waiver.'); }
            }
        });
    </script>
</x-app-layout>
