<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Permissions Matrix</h2>
            <a href="{{ route('roles.index') }}" class="px-3 py-2 rounded border">Back to Roles</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                @if (session('success'))
                    <div class="mb-4 p-3 rounded bg-green-50 text-green-800 text-sm">{{ session('success') }}</div>
                @endif
                <form method="POST" action="{{ route('roles.matrix.sync') }}" class="space-y-4">
                    @csrf
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-collapse text-sm">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="text-left p-2 border">Permission \\ Role</th>
                                    @foreach($roles as $r)
                                        <th class="text-left p-2 border">{{ $r->name }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($permissions as $p)
                                    <tr>
                                        <td class="p-2 border font-medium">{{ $p->name }}</td>
                                        @foreach($roles as $r)
                                            <td class="p-2 border text-center">
                                                <input type="checkbox" name="assign[{{ $r->id }}][{{ $p->id }}]" value="1" class="rounded"
                                                    @checked(isset($assigned[$r->id][$p->id])) />
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="flex gap-2">
                        <button class="bg-blue-600 text-white px-4 py-2 rounded">Save</button>
                        <a class="px-4 py-2 rounded border" href="{{ route('roles.index') }}">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
