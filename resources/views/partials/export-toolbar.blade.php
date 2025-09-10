@props(['route','base' => null])
@php($q = request()->query())
@php($base = $base ?: str_replace('-', '_', explode('.', $route)[0] ?? ''))
<div class="flex flex-wrap items-center gap-2">
    @can('export_' . $base)
    <a href="{{ route($route, array_merge($q, ['export' => 'csv'])) }}" class="inline-flex items-center gap-1 px-3 py-2 rounded text-sm font-medium bg-slate-700 hover:bg-slate-800 text-white" title="Export CSV">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M3 3a2 2 0 012-2h6l4 4v2h-2V6h-3a1 1 0 01-1-1V2H5a1 1 0 00-1 1v5H2V3a2 2 0 011-2z"/><path d="M3 9a2 2 0 012-2h10a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2V9zm6 1v4h2v-4H9zm-4 0v4h2v-4H5zm8 0v4h2v-4h-2z"/></svg>
        CSV
    </a>
    <a href="{{ route($route, array_merge($q, ['export' => 'xlsx'])) }}" class="inline-flex items-center gap-1 px-3 py-2 rounded text-sm font-medium bg-green-600 hover:bg-green-700 text-white" title="Export Excel">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M19 2H8a2 2 0 00-2 2v3H5a1 1 0 000 2h1v2H5a1 1 0 000 2h1v2H5a1 1 0 000 2h1v3a2 2 0 002 2h11a2 2 0 002-2V4a2 2 0 00-2-2zm-8.59 14.41L8 14l2.41-2.41L8 9.17l1.41-1.41L12.83 12l-3.42 4.41zM14 7h4v2h-4V7zm0 4h4v2h-4v-2zm0 4h4v2h-4v-2z"/></svg>
        Excel
    </a>
    <a href="{{ route($route, array_merge($q, ['export' => 'pdf'])) }}" target="_blank" class="inline-flex items-center gap-1 px-3 py-2 rounded text-sm font-medium bg-red-600 hover:bg-red-700 text-white" title="Export PDF">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M6 2h9l5 5v11a4 4 0 01-4 4H6a4 4 0 01-4-4V6a4 4 0 014-4zm8 1.5V7h3.5L14 3.5zM8 9h8v2H8V9zm0 4h8v2H8v-2z"/></svg>
        PDF
    </a>
    @endcan
    @can('print_' . $base)
    <a href="{{ route($route, array_merge($q, ['print' => 1])) }}" target="_blank" class="inline-flex items-center gap-1 px-3 py-2 rounded text-sm font-medium bg-indigo-600 hover:bg-indigo-700 text-white" title="Print">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M6 9V2h12v7h2a2 2 0 012 2v5h-4v4H8v-4H4v-5a2 2 0 012-2h0zm2-5v5h8V4H8zm0 12v4h8v-4H8z"/></svg>
        Print
    </a>
    @endcan
</div>
