<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Remark Presets') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 space-y-6">
                <h1 class="text-2xl font-bold">Manage Common Remark Presets</h1>

                @if (session('success'))
                    <div class="p-2 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div class="p-2 bg-red-100 text-red-800 rounded">
                        <ul class="list-disc list-inside text-sm">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('remark-presets.store') }}" class="space-y-3">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-6 gap-3">
                        <div class="sm:col-span-3">
                            <label class="block text-sm font-medium">Label</label>
                            <input type="text" name="label" class="w-full border rounded p-2" required>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium">Sort order</label>
                            <input type="number" name="sort_order" class="w-full border rounded p-2" value="0" min="0">
                        </div>
                        <div class="sm:col-span-1 flex items-end">
                            <label class="inline-flex items-center gap-2">
                                <input type="checkbox" name="is_active" value="1" class="rounded" checked>
                                <span class="text-sm">Active</span>
                            </label>
                        </div>
                    </div>
                    <button class="bg-blue-600 text-white px-4 py-2 rounded">Add Preset</button>
                </form>

                <div>
                    <h2 class="text-lg font-semibold mb-2">Existing Presets</h2>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left p-2">Label</th>
                                <th class="text-left p-2">Sort</th>
                                <th class="text-left p-2">Active</th>
                                <th class="text-right p-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($presets as $preset)
                                <tr class="border-b">
                                    <td class="p-2">
                                        <form method="POST" action="{{ route('remark-presets.update', $preset) }}" class="flex items-center gap-2">
                                            @csrf
                                            @method('PUT')
                                            <input type="text" name="label" value="{{ $preset->label }}" class="border rounded p-1 w-full">
                                    </td>
                                    <td class="p-2">
                                            <input type="number" name="sort_order" value="{{ $preset->sort_order }}" class="border rounded p-1 w-24">
                                    </td>
                                    <td class="p-2">
                                            <input type="checkbox" name="is_active" value="1" class="rounded" @checked($preset->is_active)>
                                    </td>
                                    <td class="p-2 text-right space-x-2">
                                            <button class="px-3 py-1 bg-green-600 text-white rounded">Save</button>
                                        </form>
                                        <form method="POST" action="{{ route('remark-presets.destroy', $preset) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button class="px-3 py-1 bg-red-600 text-white rounded" type="submit" data-confirm="Delete this preset?" data-confirm-title="Please Confirm" data-confirm-ok="Delete" data-confirm-cancel="Cancel">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td class="p-2" colspan="4">No presets yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
