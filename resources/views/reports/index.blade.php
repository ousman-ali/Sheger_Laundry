@extends('layouts.app')

@section('title', 'Reports')

@section('content')
<div class="p-4">
    <div class="flex items-center justify-between mb-3">
        <form method="get" action="{{ route('reports.index') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs text-gray-600">Report</label>
                <select name="type" class="border rounded px-2 py-1">
                    <option value="revenue" {{ $type==='revenue'?'selected':'' }}>Revenue (Daily)</option>
                    <option value="orders" {{ $type==='orders'?'selected':'' }}>Orders by Status</option>
                    <option value="top_services" {{ $type==='top_services'?'selected':'' }}>Top Services</option>
                    <option value="low_stock" {{ $type==='low_stock'?'selected':'' }}>Low Stock</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-600">Start</label>
                <input type="date" name="start_date" value="{{ $start_date }}" class="border rounded px-2 py-1" />
            </div>
            <div>
                <label class="block text-xs text-gray-600">End</label>
                <input type="date" name="end_date" value="{{ $end_date }}" class="border rounded px-2 py-1" />
            </div>
            <div class="self-end">
                <button class="px-3 py-2 rounded bg-blue-600 hover:bg-blue-700 text-white">Apply</button>
            </div>
        </form>
        @include('partials.export-toolbar', ['route' => 'reports.index'])
    </div>

    <div class="bg-white border rounded">
        <div class="px-3 py-2 border-b flex items-center justify-between">
            <h3 class="font-semibold">{{ $title }}</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        @foreach($columns as $c)
                            <th class="text-left px-3 py-2 font-medium text-gray-700">{{ $c }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $r)
                        <tr class="border-t">
                            @foreach($r as $v)
                                <td class="px-3 py-2">{{ $v }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td class="px-3 py-6 text-center text-gray-500" colspan="{{ count($columns) }}">No data found for selected period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
