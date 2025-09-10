<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Customer') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form action="{{ route('customers.update', $customer) }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label for="name" class="block text-sm font-medium">Name</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $customer->name) }}" class="w-full border rounded p-2" required>
                        @error('name')
                            <span class="text-red-600 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    <div>
                        <label for="code" class="block text-sm font-medium">Customer Code</label>
                        <input type="text" name="code" id="code" value="{{ old('code', $customer->code) }}" class="w-full border rounded p-2">
                        @error('code')
                            <span class="text-red-600 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-medium">Phone</label>
                        <input type="text" name="phone" id="phone" value="{{ old('phone', $customer->phone) }}" class="w-full border rounded p-2" required>
                        @error('phone')
                            <span class="text-red-600 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    <div>
                        <label for="address" class="block text-sm font-medium">Address</label>
                        <textarea name="address" id="address" class="w-full border rounded p-2">{{ old('address', $customer->address) }}</textarea>
                    </div>
                    <div>
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_vip" value="1" class="rounded" {{ old('is_vip', $customer->is_vip) ? 'checked' : '' }}>
                            <span>VIP Customer</span>
                        </label>
                    </div>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Update</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
