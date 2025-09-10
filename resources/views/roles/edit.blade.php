<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Manage Permissions — {{ $role->name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('roles.update', $role) }}">
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                        @foreach($permissions as $perm)
                            <label class="flex items-center gap-2 border rounded p-2">
                                <input type="checkbox" name="permissions[]" value="{{ $perm->id }}" class="rounded" @checked(in_array($perm->id, $assigned)) />
                                <span class="text-sm">{{ $perm->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    <div class="mt-4 flex gap-2">
                        <a href="{{ route('roles.index') }}" class="px-4 py-2 rounded border">Cancel</a>
                        <button class="bg-blue-600 text-white px-4 py-2 rounded" type="submit">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
