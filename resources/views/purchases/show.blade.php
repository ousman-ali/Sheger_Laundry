<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Purchase Details') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold">Supplier: {{ $purchase->supplier_name }}</h3>
                    <p class="text-gray-600">Date: {{ $purchase->purchase_date }}</p>
                    <p class="text-gray-600">Total: {{ number_format($purchase->total_price, 2) }}</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="p-2 text-left">Item</th>
                                <th class="p-2 text-left">Unit</th>
                                <th class="p-2 text-left">Qty</th>
                                <th class="p-2 text-left">Unit Price</th>
                                <th class="p-2 text-left">Total</th>
                                <th class="p-2 text-left">To Store</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($purchase->purchaseItems as $it)
                                <tr class="border-b">
                                    <td class="p-2">{{ $it->inventoryItem->name }}</td>
                                    <td class="p-2">{{ $it->unit->name }}</td>
                                    <td class="p-2">{{ $it->quantity }}</td>
                                    <td class="p-2">{{ number_format($it->unit_price, 2) }}</td>
                                    <td class="p-2">{{ number_format($it->total_price, 2) }}</td>
                                    <td class="p-2">{{ optional($it->toStore)->name ?? 'Main' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
