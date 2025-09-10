<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Add Urgency Tier') }}</h2>
        </div>
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
                <form action="{{ route('urgency-tiers.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium">Label</label>
                        <input type="text" name="label" class="w-full border rounded p-2" value="{{ old('label') }}" required />
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium">Duration (days)</label>
                            <input type="number" name="duration_days" class="w-full border rounded p-2" min="0" max="365" value="{{ old('duration_days') }}" required />
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Multiplier</label>
                            <input type="number" step="0.01" name="multiplier" class="w-full border rounded p-2" min="1" max="10" value="{{ old('multiplier', 1) }}" required />
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('urgency-tiers.index') }}" class="px-4 py-2 rounded border">Cancel</a>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
