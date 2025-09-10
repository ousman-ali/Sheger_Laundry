@php use Illuminate\Support\Str; @endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Activity Logs</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                    <form method="GET" class="flex flex-wrap items-end gap-2" onsubmit="this.page && (this.page.value=1);">
                        <div class="flex flex-col">
                            <label class="text-sm text-gray-700">Search</label>
                            <input type="text" name="q" value="{{ request('q') }}" placeholder="Action" class="border rounded p-2 text-sm" />
                        </div>
                        <div class="flex flex-col">
                            <label class="text-sm text-gray-700">User ID</label>
                            <input type="number" name="user_id" value="{{ request('user_id') }}" class="border rounded p-2 text-sm" />
                        </div>
                        <div class="flex flex-col">
                            <label class="text-sm text-gray-700">From</label>
                            <input type="date" name="from_date" value="{{ request('from_date') }}" class="border rounded p-2 text-sm" />
                        </div>
                        <div class="flex flex-col">
                            <label class="text-sm text-gray-700">To</label>
                            <input type="date" name="to_date" value="{{ request('to_date') }}" class="border rounded p-2 text-sm" />
                        </div>
                        <div class="flex flex-col">
                            <label class="text-sm text-gray-700">Per page</label>
                            <select name="per_page" class="border rounded p-2 text-sm">
                                @foreach([10,25,50,100] as $n)
                                    <option value="{{ $n }}" @selected((int)request('per_page',25)===$n)>Show {{ $n }}</option>
                                @endforeach
                            </select>
                        </div>
                        @include('partials.export-toolbar', ['route' => 'activity-logs.index'])
                        <button class="bg-gray-800 text-white px-3 py-2 rounded text-sm">Apply</button>
                        @if(request()->hasAny(['per_page','q','user_id','from_date','to_date']))
                            <a href="{{ route('activity-logs.index') }}" class="text-sm text-gray-600 underline">Clear</a>
                        @endif
                    </form>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse text-sm">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="p-2 text-left">Time</th>
                                <th class="p-2 text-left">User</th>
                                <th class="p-2 text-left">Action</th>
                                <th class="p-2 text-left">Subject</th>
                                <th class="p-2 text-left">Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $r)
                                @php
                                    $action = Str::of($r->action)->replace('_',' ')->title();
                                    $changes = is_array($r->changes) ? $r->changes : [];
                                    $subjectLabel = class_basename($r->subject_type ?? '');
                                    $subjectLink = null;
                                    if ($r->subject_type === App\Models\Order::class) {
                                        $subjectLink = route('orders.show', $r->subject_id);
                                        $subjectLabel = 'Order #'.$r->subject_id;
                                    } elseif ($r->subject_type === App\Models\Customer::class) {
                                        // Prefer non-edit link to avoid implying edit permission
                                        $subjectLink = null;
                                        $subjectLabel = 'Customer #'.$r->subject_id;
                                    }
                                @endphp
                                <tr class="border-b">
                                    <td class="p-2">{{ optional($r->created_at)->toDateTimeString() }}</td>
                                    <td class="p-2">{{ optional($r->user)->name }}</td>
                                    <td class="p-2">
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                            {{ str_contains(strtolower($r->action),'delete') ? 'bg-red-100 text-red-800' : (str_contains(strtolower($r->action),'create') ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800') }}
                                        ">{{ $action }}</span>
                                    </td>
                                    <td class="p-2">
                                        @if($subjectLink)
                                            <a href="{{ $subjectLink }}" class="text-blue-600 hover:underline">{{ $subjectLabel }}</a>
                                        @else
                                            {{ $subjectLabel ?: '—' }} @if($r->subject_id) #{{ $r->subject_id }} @endif
                                        @endif
                                    </td>
                                    <td class="p-2 align-top">
                                        @if(!empty($changes))
                                            <ul class="list-disc list-inside space-y-0.5">
                                                @foreach(array_slice($changes,0,5) as $k => $v)
                                                    @php
                                                        $val = is_array($v) ? json_encode($v) : (string)$v;
                                                        $val = Str::limit($val, 80);
                                                    @endphp
                                                    <li><span class="text-gray-600">{{ Str::title(str_replace('_',' ',$k)) }}:</span> {{ $val }}</li>
                                                @endforeach
                                                @if(count($changes) > 5)
                                                    <li class="text-gray-500">…and {{ count($changes) - 5 }} more</li>
                                                @endif
                                            </ul>
                                        @else
                                            <span class="text-gray-500">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div class="text-sm text-gray-600">Showing {{ $logs->firstItem() ?? 0 }} to {{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }} results</div>
                    <div>{{ $logs->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
