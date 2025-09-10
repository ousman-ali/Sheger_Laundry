<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Stock Transfer Details') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="mb-6 flex items-start justify-between">
                    <div>
                        <h3 class="text-lg font-semibold">From: {{ $stockTransfer->fromStore->name }} → To: {{ $stockTransfer->toStore->name }}</h3>
                        <p class="text-gray-600">Date: {{ $stockTransfer->transferred_at }}</p>
                    </div>
                    <div class="flex gap-2">
                        @can('edit_stock_transfers')
                            <a href="{{ route('stock-transfers.edit', $stockTransfer) }}" class="px-3 py-2 rounded border">Edit</a>
                        @endcan
                        @can('create_stock_transfers')
                            <form action="{{ route('stock-transfers.return', $stockTransfer) }}" method="POST" data-confirm="Create a return transfer that moves these items back?" data-confirm-title="Please Confirm" data-confirm-ok="Create Return" data-confirm-cancel="Cancel">
                                @csrf
                                <button type="submit" class="px-3 py-2 rounded border">Return</button>
                            </form>
                        @endcan
                        @can('delete_stock_transfers')
                            <form action="{{ route('stock-transfers.destroy', $stockTransfer) }}" method="POST" data-confirm="Are you sure you want to delete this transfer? This will adjust stock back." data-confirm-title="Please Confirm" data-confirm-ok="Delete Transfer" data-confirm-cancel="Cancel">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-3 py-2 rounded border text-red-700">Delete</button>
                            </form>
                        @endcan
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="p-2 text-left">Item</th>
                                <th class="p-2 text-left">Unit</th>
                                <th class="p-2 text-left">Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($stockTransfer->stockTransferItems as $it)
                                <tr class="border-b">
                                    <td class="p-2">{{ $it->inventoryItem->name }}</td>
                                    <td class="p-2">{{ $it->unit->name }}</td>
                                    <td class="p-2">{{ $it->quantity }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
