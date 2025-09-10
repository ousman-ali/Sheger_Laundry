<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Payment Details') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 space-y-4">
                @if (session('success'))
                    <div class="mb-4 p-3 rounded bg-green-50 text-green-800 text-sm">{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div class="mb-4 p-3 rounded bg-red-50 text-red-800 text-sm">
                        {{ $errors->first() }}
                    </div>
                @endif
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div><span class="text-gray-600 text-sm">Order</span><div class="font-medium">{{ $payment->order->order_id }}</div></div>
                    <div><span class="text-gray-600 text-sm">Amount</span><div class="font-medium">{{ number_format($payment->amount, 2) }}</div></div>
                    <div><span class="text-gray-600 text-sm">Method</span><div class="font-medium">{{ $payment->method }}</div></div>
                    <div><span class="text-gray-600 text-sm">Status</span><div class="font-medium">{{ ucfirst($payment->status) }}</div></div>
                    <div><span class="text-gray-600 text-sm">Paid At</span><div class="font-medium">{{ $payment->paid_at }}</div></div>
                    <div><span class="text-gray-600 text-sm">By</span><div class="font-medium">{{ optional($payment->createdBy)->name }}</div></div>
                </div>

                <div class="border rounded p-3">
                    <div class="text-sm text-gray-700">Penalty Waiver</div>
                    @if($payment->requires_approval)
                        <div class="mt-1 text-amber-800 text-sm">Awaiting Admin approval</div>
                    @elseif($payment->waived_penalty)
                        <div class="mt-1 text-blue-800 text-sm">Waiver approved</div>
                    @else
                        <div class="mt-1 text-gray-600 text-sm">No waiver</div>
                    @endif
                    @if($payment->waiver_reason)
                        <div class="mt-2 text-sm"><span class="text-gray-600">Reason:</span> {{ $payment->waiver_reason }}</div>
                    @endif
                    @can('edit_payments')
                        @if($payment->requires_approval && auth()->user() && auth()->user()->hasRole('Admin'))
                            <form method="POST" action="{{ route('payments.approve', $payment) }}" class="mt-3">
                                @csrf
                                <button class="bg-emerald-600 text-white px-3 py-2 rounded text-sm">Approve Waiver</button>
                            </form>
                        @endif
                    @endcan
                </div>

                @can('edit_payments')
                    @if($payment->status === 'completed')
                        <div class="border rounded p-3">
                            <div class="font-medium mb-2">Refund Payment</div>
                            <form method="POST" action="{{ route('payments.refund', $payment) }}" class="flex flex-col md:flex-row gap-2 items-start md:items-end">
                                @csrf
                                <div>
                                    <label class="block text-sm text-gray-600">Amount</label>
                                    <input name="amount" type="number" step="0.01" min="0.01" class="border rounded px-2 py-1 w-40" required>
                                </div>
                                <div class="flex-1">
                                    <label class="block text-sm text-gray-600">Reason (optional)</label>
                                    <input name="reason" type="text" class="border rounded px-2 py-1 w-full">
                                </div>
                                <button class="bg-amber-600 text-white px-3 py-2 rounded">Refund</button>
                            </form>
                        </div>
                    @endif
                @endcan

                @role('Admin')
                    @if(in_array($payment->status, ['pending','completed']))
                        <div class="border rounded p-3">
                            <div class="font-medium mb-2">Reverse Payment</div>
                            <form method="POST" action="{{ route('payments.reverse', $payment) }}" class="flex gap-2 items-end">
                                @csrf
                                <div class="flex-1">
                                    <label class="block text-sm text-gray-600">Reason (optional)</label>
                                    <input name="reason" type="text" class="border rounded px-2 py-1 w-full">
                                </div>
                                <button class="bg-red-600 text-white px-3 py-2 rounded">Reverse</button>
                            </form>
                        </div>
                    @endif
                @endrole

                <div class="flex gap-2">
                    <a href="{{ route('payments.index') }}" class="px-4 py-2 rounded border">Back</a>
                    @can('edit_payments')
                        <a href="{{ route('payments.edit', $payment) }}" class="bg-blue-600 text-white px-4 py-2 rounded">Edit</a>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
