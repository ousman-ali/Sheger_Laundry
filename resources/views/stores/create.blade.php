<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Add Store') }}</h2>
            <a href="{{ route('stores.index') }}" class="text-sm text-gray-600 underline">Back</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                @if ($errors->any())
                    <div class="mb-4 text-red-700 bg-red-100 border border-red-200 rounded p-3">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('stores.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium">Name</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="w-full border rounded p-2" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Type</label>
                        <select name="type" class="w-full border rounded p-2" required>
                            <option value="">Select Type</option>
                            <option value="main" @selected(old('type')==='main')>Main</option>
                            <option value="sub" @selected(old('type')==='sub')>Sub</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Description</label>
                        <textarea name="description" class="w-full border rounded p-2" rows="3">{{ old('description') }}</textarea>
                    </div>
                    <div class="flex gap-3">
                        <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded">Save</button>
                        <a href="{{ route('stores.index') }}" class="px-4 py-2 rounded border">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
