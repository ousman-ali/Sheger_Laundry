<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Payment') }}
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
                @if($payment->requires_approval)
                    <div class="mb-4 p-3 rounded bg-amber-50 text-amber-800 text-sm flex items-center justify-between">
                        <span>This payment is awaiting Admin approval due to penalty waiver. You cannot change it until it is approved.</span>
                        @can('edit_payments')
                            @if(auth()->user() && auth()->user()->hasRole('Admin'))
                                <form method="POST" action="{{ route('payments.approve', $payment) }}">
                                    @csrf
                                    <button class="bg-emerald-600 text-white px-3 py-2 rounded text-sm">Approve Waiver</button>
                                </form>
                            @endif
                        @endcan
                    </div>
                @elseif($payment->waived_penalty)
                    <div class="mb-4 p-3 rounded bg-blue-50 text-blue-800 text-sm">Penalty was waived and approved. You may mark this payment as Completed.</div>
                @endif
                <form action="{{ route('payments.update', $payment) }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-sm font-medium">Order</label>
                        <input class="w-full border rounded p-2 bg-gray-50" value="{{ $payment->order->order_id }}" disabled />
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium">Amount</label>
                            <input type="number" step="0.01" name="amount" class="w-full border rounded p-2" value="{{ old('amount', $payment->amount) }}" required />
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Method</label>
                            <input name="payment_method" class="w-full border rounded p-2" value="{{ old('payment_method', $payment->method) }}" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Status</label>
                            <select name="status" class="w-full border rounded p-2">
                                @foreach(['completed','pending','refunded'] as $s)
                                    <option value="{{ $s }}" @selected(old('status', $payment->status)===$s)>{{ ucfirst($s) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium">Paid At</label>
                            <input type="datetime-local" name="paid_at" class="w-full border rounded p-2" value="{{ old('paid_at', optional($payment->paid_at)->format('Y-m-d\TH:i')) }}" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Notes</label>
                            <input name="notes" class="w-full border rounded p-2" value="{{ old('notes', $payment->notes) }}" />
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
</x-app-layout>
