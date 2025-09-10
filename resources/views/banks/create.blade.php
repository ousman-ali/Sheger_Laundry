<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create Bank</h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('banks.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium">Name</label>
                        <input name="name" class="w-full border rounded p-2" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Branch</label>
                        <input name="branch" class="w-full border rounded p-2" />
                    </div>
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" class="rounded" checked>
                        <span class="text-sm">Active</span>
                    </label>
                    <div class="flex gap-2">
                        <a href="{{ route('banks.index') }}" class="px-4 py-2 rounded border">Cancel</a>
                        <button class="bg-blue-600 text-white px-4 py-2 rounded" type="submit">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
