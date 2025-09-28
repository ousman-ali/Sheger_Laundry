<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Order ID Settings</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                @if (session('success'))
                    <div class="mb-4 p-3 rounded bg-green-50 text-green-800 text-sm">{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div class="mb-4 p-3 rounded bg-red-50 text-red-700 text-sm">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('settings.orderid.update') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium">Order ID Prefix</label>
                        <input type="text" name="order_id_prefix" class="w-full border rounded p-2" value="{{ old('order_id_prefix', $settings['order_id_prefix']) }}" maxlength="10" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Date Format (PHP date)</label>
                        <input type="text" name="order_id_format" class="w-full border rounded p-2" value="{{ old('order_id_format', $settings['order_id_format']) }}" maxlength="20" required />
                        <p class="text-xs text-gray-600">Examples: Ymd, Y-m-d, ymdHi</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Sequence Width</label>
                        <input type="number" min="1" max="10" name="order_id_sequence_length" class="w-full border rounded p-2" value="{{ old('order_id_sequence_length', (int)$settings['order_id_sequence_length']) }}" required />
                        <p class="text-xs text-gray-600">Controls zero-padding for the daily counter.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">VIP Prefix (optional)</label>
                        <input type="text" name="vip_order_id_prefix" class="w-full border rounded p-2" value="{{ old('vip_order_id_prefix', $settings['vip_order_id_prefix']) }}" maxlength="10" />
                        <p class="text-xs text-gray-600">When set, VIP orders will be prefixed like VIP-ORD-20250101-001.</p>
                    </div>
                    <!-- 🔹 VAT Percentage -->
                    <div>
                        <label class="block text-sm font-medium">VAT Percentage</label>
                        <input type="number" step="0.01" min="0" max="100" name="vat_percentage"
                               class="w-full border rounded p-2"
                               value="{{ old('vat_percentage', $settings['vat_percentage']) }}" required />
                        <p class="text-xs text-gray-600">Set the VAT percentage (e.g., 15 for 15%).</p>
                    </div>
                    <div class="flex gap-2">
                        <button class="bg-blue-600 text-white px-4 py-2 rounded">Save</button>
                        <a href="{{ route('dashboard') }}" class="px-4 py-2 rounded border">Back</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
