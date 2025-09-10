<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Notifications</h2>
            @role('Admin')
            <div class="flex items-center gap-2 text-sm">
                <form method="POST" action="{{ route('notifications.destroyRead') }}" data-confirm="Delete read notifications?" data-confirm-title="Please Confirm" data-confirm-ok="Delete" data-confirm-cancel="Cancel" class="flex items-center gap-1">
                    @csrf @method('DELETE')
                    <input type="number" name="older_days" placeholder=">= days" class="border rounded p-1 text-xs w-28" />
                    <button class="px-2 py-1 bg-gray-100 rounded border">Delete read</button>
                </form>
                <form method="POST" action="{{ route('notifications.destroyOlder') }}" data-confirm="Purge notifications older than N days?" data-confirm-title="Please Confirm" data-confirm-ok="Delete" data-confirm-cancel="Cancel" class="flex items-center gap-1">
                    @csrf @method('DELETE')
                    <input type="number" name="days" placeholder="> days" required class="border rounded p-1 text-xs w-24" />
                    <button class="px-2 py-1 bg-gray-100 rounded border">Purge older</button>
                </form>
            </div>
            @endrole
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                    <form method="GET" class="flex flex-wrap items-end gap-2" onsubmit="this.page && (this.page.value=1);">
                        <div class="flex flex-col">
                            <label class="text-sm text-gray-700">Search</label>
                            <input type="text" name="q" value="{{ request('q') }}" placeholder="Message contains..." class="border rounded p-2 text-sm" />
                        </div>
                        <div class="flex flex-col">
                            <label class="text-sm text-gray-700">Type</label>
                            <input type="text" name="type" value="{{ request('type') }}" class="border rounded p-2 text-sm" />
                        </div>
                        <div class="flex flex-col">
                            <label class="text-sm text-gray-700">Read</label>
                            <select name="is_read" class="border rounded p-2 text-sm">
                                <option value="">Any</option>
                                <option value="0" @selected(request('is_read')==='0')>Unread</option>
                                <option value="1" @selected(request('is_read')==='1')>Read</option>
                            </select>
                        </div>
                        @role('Admin')
                        <div class="flex flex-col">
                            <label class="text-sm text-gray-700">User ID</label>
                            <input type="number" name="user_id" value="{{ request('user_id') }}" class="border rounded p-2 text-sm" />
                        </div>
                        @endrole
                        <div class="flex flex-col">
                            <label class="text-sm text-gray-700">From</label>
                            <input type="date" name="from_date" value="{{ request('from_date') }}" class="border rounded p-2 text-sm" />
                        </div>
                        <div class="flex flex-col">
                            <label class="text-sm text-gray-700">To</label>
                            <input type="date" name="to_date" value="{{ request('to_date') }}" class="border rounded p-2 text-sm" />
                        </div>
                        <button class="bg-gray-800 text-white px-3 py-2 rounded text-sm">Apply</button>
                        @if(request()->hasAny(['q','type','is_read','user_id','from_date','to_date']))
                            <a href="{{ route('notifications.index') }}" class="text-sm text-gray-600 underline">Clear</a>
                        @endif
                    </form>
                    <form method="POST" action="{{ route('notifications.markAllRead') }}">
                        @csrf
                        <button class="bg-slate-700 text-white px-3 py-2 rounded text-sm">Mark all as read</button>
                    </form>
                </div>
                <div class="overflow-x-auto">
                <form method="POST" action="{{ route('notifications.bulkDestroy') }}" id="bulk-delete-form">
                    @csrf
                    @method('DELETE')
                    <table class="min-w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="p-2"><input type="checkbox" onclick="document.querySelectorAll('.row-check').forEach(cb=>cb.checked=this.checked);" /></th>
                                @role('Admin')<th class="p-2 text-left">User</th>@endrole
                                <th class="p-2 text-left">Type</th>
                                <th class="p-2 text-left">Message</th>
                                <th class="p-2 text-left">Link</th>
                                <th class="p-2 text-left">Read</th>
                                <th class="p-2 text-left">Created</th>
                                <th class="p-2 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($notifications as $n)
                                <tr class="border-b">
                                    <td class="p-2 align-top"><input class="row-check" type="checkbox" name="ids[]" value="{{ $n->id }}" /></td>
                                    @role('Admin')<td class="p-2">{{ optional($n->user)->name ?? '—' }}</td>@endrole
                                    <td class="p-2">{{ $n->type }}</td>
                                    <td class="p-2">{{ $n->message }}</td>
                                    <td class="p-2">@if($n->url)<a class="text-blue-600 hover:underline" href="{{ route('notifications.markRead', $n->id) }}">Open</a>@else — @endif</td>
                                    <td class="p-2">{{ $n->is_read ? 'Read' : 'Unread' }}</td>
                                    <td class="p-2">{{ optional($n->created_at)->format('Y-m-d H:i') }}</td>
                                    <td class="p-2">
                                        <form method="POST" action="{{ route('notifications.destroy', $n->id) }}" data-confirm="Delete this notification?" data-confirm-title="Please Confirm" data-confirm-ok="Delete" data-confirm-cancel="Cancel">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-red-600 hover:underline" type="submit">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td class="p-2" colspan="8">No notifications.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </form>
                </div>
                <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <div class="text-sm text-gray-600">Showing {{ $notifications->firstItem() ?? 0 }} to {{ $notifications->lastItem() ?? 0 }} of {{ $notifications->total() }} results</div>
                    </div>
                    <div class="flex items-center gap-3">
                        <button form="bulk-delete-form" class="text-sm text-red-700 hover:underline" data-confirm="Delete selected notifications?" data-confirm-title="Please Confirm" data-confirm-ok="Delete" data-confirm-cancel="Cancel">Delete selected</button>
                        @role('Admin')
                        <form method="POST" action="{{ route('notifications.destroyRead') }}" data-confirm="Delete read notifications?" data-confirm-title="Please Confirm" data-confirm-ok="Delete" data-confirm-cancel="Cancel" class="flex items-center gap-2">
                            @csrf
                            @method('DELETE')
                            <input type="number" name="older_days" placeholder="older than days" class="border rounded p-1 text-xs w-36" />
                            <button class="text-sm text-gray-700 hover:underline">Delete read</button>
                        </form>
                        <form method="POST" action="{{ route('notifications.destroyOlder') }}" data-confirm="Purge notifications older than N days?" data-confirm-title="Please Confirm" data-confirm-ok="Delete" data-confirm-cancel="Cancel" class="flex items-center gap-2">
                            @csrf
                            @method('DELETE')
                            <input type="number" name="days" placeholder="purge days" required class="border rounded p-1 text-xs w-28" />
                            <button class="text-sm text-gray-700 hover:underline">Purge older</button>
                        </form>
                        @endrole
                        <div>{{ $notifications->links() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
