<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Roles & Permissions</h2>
            <div class="flex items-center gap-2">
                @role('Admin')
                    <a href="{{ route('roles.matrix') }}" class="px-3 py-2 rounded border">Permissions Matrix</a>
                @endrole
                @can('assign_roles')
                    <x-create-button :href="route('roles.create')" label="New Role" />
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Roles</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="p-2 text-left">Name</th>
                                <th class="p-2 text-left">Users</th>
                                <th class="p-2 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($roles as $r)
                                <tr class="border-b">
                                    <td class="p-2">{{ $r->name }}</td>
                                    <td class="p-2">{{ $r->users_count }}</td>
                                    <td class="p-2">
                                        @can('assign_roles')
                                            <a href="{{ route('roles.edit', $r) }}" class="text-blue-600 hover:underline">Manage permissions</a>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
