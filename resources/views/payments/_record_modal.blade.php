@props(['orderId' => null, 'orderLabel' => null, 'open' => false, 'modalId' => 'recordPaymentModal'])

<div x-data="{ open: {{ $open ? 'true' : 'false' }}, orderId: {{ $orderId ? (int)$orderId : 'null' }}, orderLabel: @js($orderLabel), suggest: null, hasPenalty: false }"
    x-on:open-record-payment.window="orderId = $event.detail.orderId; orderLabel = $event.detail.orderLabel || null; open = true; $nextTick(()=>{ try { const el = $refs.paidAt; if(el){ const pad=n=>String(n).padStart(2,'0'); const d=new Date(); const val=`${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`; el.value = val; } } catch(_){}; fetchSuggest(); });"
     x-id="['modal-title']"
     class="relative">
    <template x-if="open">
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40" @click="open=false" aria-hidden="true"></div>
            <div class="relative w-full max-w-xl bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="px-4 py-3 border-b flex items-center justify-between">
                    <h3 class="font-semibold text-lg" :id="$id('modal-title')">Record Payment</h3>
                    <button type="button" class="p-1 rounded hover:bg-gray-100" @click="open=false" aria-label="Close dialog">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5"><path d="M6.225 4.811L4.811 6.225 10.586 12l-5.775 5.775 1.414 1.414L12 13.414l5.775 5.775 1.414-1.414L13.414 12l5.775-5.775-1.414-1.414L12 10.586z"/></svg>
                    </button>
                </div>
                <form action="{{ route('payments.store') }}" method="POST" class="p-4 space-y-4" @submit="if($refs.waived.checked && !$refs.waiver_reason.value.trim()){ $event.preventDefault(); alert('Please provide reason for penalty waiver.'); }">
                    @csrf
                    <input type="hidden" name="order_id" :value="orderId">
                    <input type="hidden" name="redirect" value="{{ url()->current() }}">

                    <div>
                        <label class="block text-sm font-medium">Order</label>
                        <div class="text-sm text-gray-700" x-text="orderLabel || ('#'+orderId)"></div>
                        <p class="text-xs text-gray-600 mt-1" x-show="suggest" x-text="`Suggested: base ${Number(suggest?.base||0).toFixed(2)} + penalty ${Number(suggest?.penalty||0).toFixed(2)} = total ${Number(suggest?.total||0).toFixed(2)} — paid ${Number(suggest?.paid||0).toFixed(2)} — due ${Number(suggest?.due ?? suggest?.total || 0).toFixed(2)}`"></p>
                        <p class="text-xs mt-1" x-show="hasPenalty" :class="hasPenalty ? 'text-amber-700' : 'text-gray-500'">Penalty applies — waiving requires Admin approval for non-admin users.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium">Amount</label>
                            <input type="number" step="0.01" name="amount" class="w-full border rounded p-2" x-bind:value="suggest ? Number((suggest.due ?? suggest.total) || 0).toFixed(2) : ''" required />
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Method</label>
                            <select name="payment_method" class="w-full border rounded p-2" x-on:change="$dispatch('payment-method-change', $event.target.value)">
                                <option value="cash">Cash</option>
                                <option value="bank">Bank</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Status</label>
                            <select name="status" class="w-full border rounded p-2">
                                <option value="completed">Completed</option>
                                <option value="pending">Pending</option>
                                <option value="refunded">Refunded</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-1" x-data="{ method: 'cash' }" x-on:payment-method-change.window="method = $event.detail">
                        <div x-show="method==='bank'">
                            <label class="block text-sm font-medium">Bank</label>
                            <select name="bank_id" class="w-full border rounded p-2">
                                @foreach(\App\Models\Bank::where('is_active',true)->orderBy('name')->get() as $bank)
                                    <option value="{{ $bank->id }}">{{ $bank->name }}@if($bank->branch) — {{ $bank->branch }}@endif</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium">Paid At</label>
                            <input type="datetime-local" name="paid_at" x-ref="paidAt" class="w-full border rounded p-2" value="{{ old('paid_at', now()->format('Y-m-d\\TH:i')) }}" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Notes</label>
                            <input name="notes" class="w-full border rounded p-2" />
                        </div>
                    </div>

                    <div class="border rounded p-3">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="waived_penalty" x-ref="waived" class="rounded">
                            <span class="text-sm">Request to waive penalty</span>
                        </label>
                        <div class="mt-2" x-show="$refs.waived && $refs.waived.checked">
                            <label class="block text-sm font-medium">Reason for waiver (required)</label>
                            <textarea name="waiver_reason" x-ref="waiver_reason" class="w-full border rounded p-2" rows="2" placeholder="Customer reason..."></textarea>
                            <p class="text-xs text-amber-700 mt-1">If there is an active penalty and you are not an Admin, this payment will require Admin approval.</p>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2">
                        <button type="button" class="px-4 py-2 rounded border" @click="open=false">Cancel</button>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <script>
        async function fetchSuggestFor(orderId){
            try{
                const url = new URL(@js(route('payments.suggest')));
                url.searchParams.set('order_id', orderId);
                const res = await fetch(url, { headers: { 'Accept': 'application/json' }});
                if(!res.ok) return null;
                return await res.json();
            } catch(e){ return null; }
        }
    </script>
    <script>
        function fetchSuggest(){
            const root = document.currentScript.closest('[x-data]');
            if(!root) return;
            const state = Alpine.$data(root);
            if(!state?.orderId) return;
            fetchSuggestFor(state.orderId).then(data=>{
                state.suggest = data;
                state.hasPenalty = Number(data?.penalty||0) > 0;
            });
        }
        // If an initial orderId was passed and open is true on load, fetch immediately
        document.addEventListener('alpine:init', ()=>{
            const root = document.querySelector('[x-data][x-id]');
            if(!root) return;
            try {
                const state = Alpine.$data(root);
                if(state?.orderId && state?.open){ fetchSuggest(); }
            } catch(_) {}
        });
    </script>
</div>
