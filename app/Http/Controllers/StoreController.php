<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStoreRequest;
use App\Http\Requests\StoreUpdateRequest;
use App\Models\InventoryStock;
use App\Models\PurchaseItem;
use App\Models\StockOutRequest;
use App\Models\StockTransfer;
use App\Models\StockUsage;
use App\Models\Store;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function __construct()
    {
        $this->middleware('role_or_permission:Admin|view_stores')->only(['index']);
        $this->middleware('role_or_permission:Admin|create_stores')->only(['create', 'store']);
        $this->middleware('role_or_permission:Admin|edit_stores')->only(['edit', 'update']);
        $this->middleware('role_or_permission:Admin|delete_stores')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
            'type' => 'nullable|in:main,sub',
            'per_page' => 'nullable|integer|in:10,25,50,100',
            'sort' => 'nullable|in:name,type,created_at',
            'direction' => 'nullable|in:asc,desc',
            'export' => 'nullable|in:csv,xlsx,pdf',
        ]);
        $perPage = (int) ($validated['per_page'] ?? $request->session()->get('stores.per_page', 10));
        $request->session()->put('stores.per_page', $perPage);

        $query = Store::query();
        if (!empty($validated['q'] ?? null)) {
            $q = $validated['q'];
            $query->where(function($w) use ($q){
                $w->where('name','like',"%{$q}%")
                  ->orWhere('description','like',"%{$q}%");
            });
        }
        if (!empty($validated['type'] ?? null)) {
            $query->where('type', $validated['type']);
        }
        $sort = $validated['sort'] ?? 'name';
        $direction = $validated['direction'] ?? 'asc';
        $query->orderBy($sort, $direction);

        if (!empty($validated['export'] ?? null)) {
            abort_unless(\Illuminate\Support\Facades\Gate::allows('export_inventory'), 403);
        }
        if (($validated['export'] ?? null) === 'csv') {
            $filename = 'stores_' . now()->format('Ymd_His') . '.csv';
            $rows = (clone $query)->get();
            return response()->streamDownload(function () use ($rows) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Name','Type','Description','Created At']);
                foreach ($rows as $s) {
                    fputcsv($out, [
                        $s->name,
                        $s->type,
                        $s->description,
                        optional($s->created_at)->toDateTimeString(),
                    ]);
                }
                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv']);
        }
        if (($validated['export'] ?? null) === 'xlsx') {
            $rows = (clone $query)->get()->map(fn($s) => [
                $s->name, $s->type, $s->description, optional($s->created_at)->toDateTimeString(),
            ]);
            return \App\Services\ExcelExportService::streamSimpleXlsx(
                'stores_'.now()->format('Ymd_His').'.xlsx',
                ['Name','Type','Description','Created At'],
                $rows
            );
        }
        if (($validated['export'] ?? null) === 'pdf') {
            $rows = (clone $query)->get()->map(fn($s) => [
                'Name' => $s->name,
                'Type' => $s->type,
                'Description' => $s->description,
                'Created At' => optional($s->created_at)->toDateTimeString(),
            ]);
            return \App\Services\PdfExportService::streamSimpleTable(
                'stores_'.now()->format('Ymd_His').'.pdf',
                'Stores',
                ['Name','Type','Description','Created At'],
                $rows
            );
        }
        if ($request->boolean('print')) {
            abort_unless(\Illuminate\Support\Facades\Gate::allows('print_inventory'), 403);
            $rows = (clone $query)->get()->map(fn($s) => [
                'Name' => $s->name,
                'Type' => $s->type,
                'Description' => $s->description,
                'Created At' => optional($s->created_at)->toDateTimeString(),
            ]);
            return view('exports.simple_table', [
                'title' => 'Stores',
                'columns' => ['Name','Type','Description','Created At'],
                'rows' => $rows,
            ]);
        }

        $stores = $query->paginate($perPage)
            ->appends(array_merge($request->only(['q','type','sort','direction']), ['per_page' => $perPage]));
        return view('stores.index', compact('stores', 'sort', 'direction'));
    }

    public function create()
    {
        return view('stores.create');
    }

    public function store(StoreStoreRequest $request)
    {
        Store::create($request->validated());
        return redirect()->route('stores.index')->with('success', 'Store created successfully.');
    }

    public function edit(Store $store)
    {
        return view('stores.edit', compact('store'));
    }

    public function update(StoreUpdateRequest $request, Store $store)
    {
        $store->update($request->validated());
        return redirect()->route('stores.index')->with('success', 'Store updated successfully.');
    }

    public function destroy(Store $store)
    {
        // Prevent delete if referenced anywhere
        if (InventoryStock::where('store_id', $store->id)->exists()) {
            return redirect()->route('stores.index')->with('error', 'Cannot delete: store has inventory stock.');
        }
        if (StockUsage::where('store_id', $store->id)->exists()) {
            return redirect()->route('stores.index')->with('error', 'Cannot delete: store is referenced by stock usage.');
        }
        if (StockTransfer::where('from_store_id', $store->id)->orWhere('to_store_id', $store->id)->exists()) {
            return redirect()->route('stores.index')->with('error', 'Cannot delete: store is referenced by stock transfers.');
        }
        if (StockOutRequest::where('store_id', $store->id)->exists()) {
            return redirect()->route('stores.index')->with('error', 'Cannot delete: store is referenced by stock-out requests.');
        }
        if (PurchaseItem::where('to_store_id', $store->id)->exists()) {
            return redirect()->route('stores.index')->with('error', 'Cannot delete: store is referenced by purchase items.');
        }

        try {
            $store->delete();
        } catch (QueryException $e) {
            return redirect()->route('stores.index')->with('error', 'Cannot delete: store is referenced by other records.');
        }
        return redirect()->route('stores.index')->with('success', 'Store deleted successfully.');
    }
}
