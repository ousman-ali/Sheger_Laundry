<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Payment Ledgers') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6" x-data="{ open:false, html:'', async show(id){ this.open=true; this.html='Loading...'; try{ const res = await fetch(`{{ url('ledgers') }}/${id}/breakdown`); this.html = await res.text(); } catch(e){ this.html='Failed to load.'; } }, close(){ this.open=false; this.html=''; } }">
                <form method="GET" class="flex flex-wrap gap-2 mb-4">
                    <select name="status" class="border rounded p-2 text-sm">
                        <option value="">All statuses</option>
                        @foreach(['pending','partial','paid'] as $s)
                            <option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Order or customer" class="border rounded p-2 text-sm" />
                    <button class="bg-gray-800 text-white px-3 py-2 rounded text-sm">Apply</button>
                </form>

                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="p-2 text-left">Order</th>
                                <th class="p-2 text-left">Customer</th>
                                <th class="p-2 text-right">Total</th>
                                <th class="p-2 text-right">Received</th>
                                <th class="p-2 text-right">Due</th>
                                <th class="p-2 text-left">Status</th>
                                <th class="p-2 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ledgers as $l)
                                @php $due = max(0, (float)$l->total_amount - (float)$l->amount_received); @endphp
                                <tr class="border-b">
                                    <td class="p-2"><button class="text-blue-600 underline" @click="show({{ $l->id }})">{{ $l->order->order_id }}</button></td>
                                    <td class="p-2">{{ $l->order->customer->name ?? '-' }}</td>
                                    <td class="p-2 text-right">{{ number_format($l->total_amount, 2) }}</td>
                                    <td class="p-2 text-right">{{ number_format($l->amount_received, 2) }}</td>
                                    <td class="p-2 text-right">{{ number_format($due, 2) }}</td>
                                    <td class="p-2">{{ ucfirst($l->status) }}</td>
                                    <td class="p-2"><a 
                                        class="inline-flex items-center justify-center w-8 h-8 bg-blue-100 hover:bg-blue-200 text-blue-600 rounded-md transition" 
                                        href="{{ route('orders.show', $l->order) }}"
                                    >
                                        <x-heroicon-o-eye class="w-5 h-5" />    
                                    </a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $ledgers->links() }}</div>
                <!-- Modal -->
                <div x-cloak x-show="open" class="fixed inset-0 bg-black/40 z-40 flex items-center justify-center p-4">
                    <div class="bg-white rounded shadow-lg w-full max-w-4xl max-h-[90vh] overflow-auto">
                        <div class="flex items-center justify-between border-b p-3">
                            <div class="font-semibold">Payment Breakdown</div>
                            <button class="p-2 hover:bg-gray-100 rounded" @click="close()">✕</button>
                        </div>
                        <div class="p-2" x-html="html"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
