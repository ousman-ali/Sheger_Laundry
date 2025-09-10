<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InventoryItem;
use App\Models\Unit;

class InventoryController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
        $this->middleware('role_or_permission:Admin|view_inventory')->only(['index','stock']);
        $this->middleware('role_or_permission:Admin|create_inventory')->only(['create','store']);
        $this->middleware('role_or_permission:Admin|edit_inventory')->only(['edit','update','updateStock']);
        $this->middleware('role_or_permission:Admin|delete_inventory')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
            'unit_id' => 'nullable|exists:units,id',
            'per_page' => 'nullable|integer|in:10,25,50,100',
            'sort' => 'nullable|in:name,minimum_stock,created_at',
            'direction' => 'nullable|in:asc,desc',
               'export' => 'nullable|in:csv,xlsx,pdf',
        ]);

        $perPage = (int) ($validated['per_page'] ?? $request->session()->get('inventory.per_page', 10));
        $request->session()->put('inventory.per_page', $perPage);

        $query = InventoryItem::query()->with('unit');
        if (!empty($validated['q'] ?? null)) {
            $q = $validated['q'];
            $query->where('name', 'like', "%{$q}%");
        }
        if (!empty($validated['unit_id'] ?? null)) {
            $query->where('unit_id', $validated['unit_id']);
        }
        $sort = $validated['sort'] ?? 'name';
        $direction = $validated['direction'] ?? 'asc';
        $query->orderBy($sort, $direction);

        // Exports
        if (!empty($validated['export'] ?? null)) {
            abort_unless(\Illuminate\Support\Facades\Gate::allows('export_inventory'), 403);
        }
        if (!empty($validated['export'] ?? null)) {
            abort_unless(\Illuminate\Support\Facades\Gate::allows('export_inventory'), 403);
        }
        if (($validated['export'] ?? null) === 'csv') {
            $filename = 'inventory_items_' . now()->format('Ymd_His') . '.csv';
            $rows = (clone $query)->get();
            return response()->streamDownload(function () use ($rows) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Name','Unit','Minimum Stock','Created At']);
                foreach ($rows as $it) {
                    fputcsv($out, [
                        $it->name,
                        optional($it->unit)->name,
                        $it->minimum_stock,
                        optional($it->created_at)->toDateTimeString(),
                    ]);
                }
                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv']);
        }
           if (($validated['export'] ?? null) === 'xlsx') {
               $rows = (clone $query)->get()->map(fn($it) => [
                   'Name' => $it->name,
                   'Unit' => optional($it->unit)->name,
                   'Minimum Stock' => $it->minimum_stock,
                   'Created At' => optional($it->created_at)->toDateTimeString(),
               ]);
               return \App\Services\ExcelExportService::streamSimpleXlsx(
                   'inventory_items_'.now()->format('Ymd_His').'.xlsx',
                   ['Name','Unit','Minimum Stock','Created At'],
                   $rows
               );
           }
        if (($validated['export'] ?? null) === 'pdf') {
            $rows = (clone $query)->get()->map(fn($it) => [
                'Name' => $it->name,
                'Unit' => optional($it->unit)->name,
                'Minimum Stock' => $it->minimum_stock,
                'Created At' => optional($it->created_at)->toDateTimeString(),
            ]);
            return \App\Services\PdfExportService::streamSimpleTable(
                'inventory_items_'.now()->format('Ymd_His').'.pdf',
                'Inventory Items',
                ['Name','Unit','Minimum Stock','Created At'],
                $rows
            );
        }
        if ($request->boolean('print')) {
            abort_unless(\Illuminate\Support\Facades\Gate::allows('print_inventory'), 403);
            $rows = (clone $query)->get()->map(fn($it) => [
                'Name' => $it->name,
                'Unit' => optional($it->unit)->name,
                'Minimum Stock' => $it->minimum_stock,
                'Created At' => optional($it->created_at)->toDateTimeString(),
            ]);
            return view('exports.simple_table', [
                'title' => 'Inventory Items',
                'columns' => ['Name','Unit','Minimum Stock','Created At'],
                    'rows' => $rows,
            ]);
        }

        $items = $query->paginate($perPage)
            ->appends(array_merge($request->only(['q','unit_id','sort','direction']), ['per_page' => $perPage]));
        $units = Unit::orderBy('name')->get();
        return view('inventory.index', compact('items', 'units', 'sort', 'direction'));
    }
    public function create()
    {
        $units = Unit::orderBy('name')->get();
        return view('inventory.create', compact('units'));
    }

    public function store(\App\Http\Requests\StoreInventoryItemRequest $request)
    {
        InventoryItem::create($request->validated());
        return redirect()->route('inventory.index')->with('success', 'Inventory item created.');
    }

    public function edit(InventoryItem $inventory)
    {
        $units = Unit::orderBy('name')->get();
        return view('inventory.edit', ['item' => $inventory, 'units' => $units]);
    }

    public function update(\App\Http\Requests\UpdateInventoryItemRequest $request, InventoryItem $inventory)
    {
        $inventory->update($request->validated());
        return redirect()->route('inventory.index')->with('success', 'Inventory item updated.');
    }

    public function destroy(InventoryItem $inventory)
    {
        try {
            if ($inventory->inventoryStock()->exists()) {
                return redirect()->route('inventory.index')->with('error', 'Cannot delete an item with stock records.');
            }
            if ($inventory->purchaseItems()->exists() || $inventory->stockTransferItems()->exists() || $inventory->stockUsage()->exists()) {
                return redirect()->route('inventory.index')->with('error', 'Cannot delete an item referenced by purchases or stock movements.');
            }
            $inventory->delete();
            return redirect()->route('inventory.index')->with('success', 'Inventory item deleted.');
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle potential FK 1451 or similar constraint errors defensively
            return redirect()->route('inventory.index')->with('error', 'Delete failed due to related records. Remove related stock/usages/purchases/transfers first.');
        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('inventory.index')->with('error', 'Delete failed. Please try again.');
        }
    }

    /**
     * Read-only stock listing per item per store with filters/exports.
     */
    public function stock(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
            'inventory_item_id' => 'nullable|exists:inventory_items,id',
            'store_id' => 'nullable|exists:stores,id',
            'per_page' => 'nullable|integer|in:10,25,50,100',
            'sort' => 'nullable|in:item,store,quantity,minimum_stock,created_at',
            'direction' => 'nullable|in:asc,desc',
            'export' => 'nullable|in:csv,xlsx,pdf',
        ]);

        $perPage = (int) ($validated['per_page'] ?? $request->session()->get('inventory.stock.per_page', 10));
        $request->session()->put('inventory.stock.per_page', $perPage);

        $query = \App\Models\InventoryStock::query()
            ->select(
                'inventory_stock.*',
                'inventory_items.name as item_name',
                'inventory_items.minimum_stock as item_minimum_stock',
                'stores.name as store_name',
                'units.name as item_unit_name'
            )
            ->join('inventory_items', 'inventory_items.id', '=', 'inventory_stock.inventory_item_id')
            ->join('units', 'units.id', '=', 'inventory_items.unit_id')
            ->join('stores', 'stores.id', '=', 'inventory_stock.store_id');

        if (!empty($validated['q'] ?? null)) {
            $q = $validated['q'];
            $query->where(function($w) use ($q) {
                $w->where('inventory_items.name', 'like', "%{$q}%")
                  ->orWhere('stores.name', 'like', "%{$q}%");
            });
        }
        if (!empty($validated['inventory_item_id'] ?? null)) {
            $query->where('inventory_items.id', $validated['inventory_item_id']);
        }
        if (!empty($validated['store_id'] ?? null)) {
            $query->where('stores.id', $validated['store_id']);
        }

        $sort = $validated['sort'] ?? 'item';
        $direction = $validated['direction'] ?? 'asc';
        $sortMap = [
            'item' => 'inventory_items.name',
            'store' => 'stores.name',
            'quantity' => 'inventory_stock.quantity',
            'minimum_stock' => 'inventory_items.minimum_stock',
            'created_at' => 'inventory_stock.created_at',
        ];
        $query->orderBy($sortMap[$sort] ?? 'inventory_items.name', $direction);

        // Enforce RBAC for exports as well (UI is already guarded via @can)
        if (!empty($validated['export'] ?? null)) {
            abort_unless(\Illuminate\Support\Facades\Gate::allows('export_inventory'), 403);
        }

    if (($validated['export'] ?? null) === 'csv') {
            $filename = 'inventory_stock_' . now()->format('Ymd_His') . '.csv';
            $rows = (clone $query)->get();
            return response()->streamDownload(function () use ($rows) {
                $out = fopen('php://output', 'w');
        fputcsv($out, ['Item','Store','Unit','Quantity','Minimum Stock','Low?','Updated At']);
                foreach ($rows as $r) {
                    $low = $r->quantity <= $r->item_minimum_stock ? 'YES' : '';
                    fputcsv($out, [
                        $r->item_name,
                        $r->store_name,
            optional($r->item_unit_name),
                        number_format((float)$r->quantity, 2, '.', ''),
                        number_format((float)$r->item_minimum_stock, 2, '.', ''),
                        $low,
                        optional($r->updated_at)->toDateTimeString(),
                    ]);
                }
                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv']);
        }

        if (($validated['export'] ?? null) === 'xlsx') {
            $rows = (clone $query)->get()->map(fn($r) => [
                $r->item_name,
                $r->store_name,
                optional($r->item_unit_name),
                number_format((float)$r->quantity, 2, '.', ''),
                number_format((float)$r->item_minimum_stock, 2, '.', ''),
                $r->quantity <= $r->item_minimum_stock ? 'YES' : '',
                optional($r->updated_at)->toDateTimeString(),
            ]);
            return \App\Services\ExcelExportService::streamSimpleXlsx(
                'inventory_stock_'.now()->format('Ymd_His').'.xlsx',
                ['Item','Store','Unit','Quantity','Minimum Stock','Low?','Updated At'],
                $rows
            );
        }
        if (($validated['export'] ?? null) === 'pdf') {
            $rows = (clone $query)->get()->map(fn($r) => [
                'Item' => $r->item_name,
                'Store' => $r->store_name,
                'Unit' => optional($r->item_unit_name),
                'Quantity' => number_format((float)$r->quantity, 2, '.', ''),
                'Minimum Stock' => number_format((float)$r->item_minimum_stock, 2, '.', ''),
                'Low?' => $r->quantity <= $r->item_minimum_stock ? 'YES' : '',
                'Updated At' => optional($r->updated_at)->toDateTimeString(),
            ]);
            return \App\Services\PdfExportService::streamSimpleTable(
                'inventory_stock_'.now()->format('Ymd_His').'.pdf',
                'Inventory Stock',
                ['Item','Store','Unit','Quantity','Minimum Stock','Low?','Updated At'],
                $rows
            );
        }

        if ($request->boolean('print')) {
            abort_unless(\Illuminate\Support\Facades\Gate::allows('print_inventory'), 403);
            $rows = (clone $query)->get()->map(fn($r) => [
                'Item' => $r->item_name,
                'Store' => $r->store_name,
                'Unit' => optional($r->item_unit_name),
                'Quantity' => number_format((float)$r->quantity, 2, '.', ''),
                'Minimum Stock' => number_format((float)$r->item_minimum_stock, 2, '.', ''),
                'Low?' => $r->quantity <= $r->item_minimum_stock ? 'YES' : '',
                'Updated At' => optional($r->updated_at)->toDateTimeString(),
            ]);
            return view('exports.simple_table', [
                'title' => 'Inventory Stock',
                'columns' => ['Item','Store','Unit','Quantity','Minimum Stock','Low?','Updated At'],
                'rows' => $rows,
            ]);
        }

        $stocks = $query->paginate($perPage)
            ->appends(array_merge($request->only(['q','inventory_item_id','store_id','sort','direction']), ['per_page' => $perPage]));
        $items = \App\Models\InventoryItem::orderBy('name')->get();
        $stores = \App\Models\Store::orderBy('name')->get();

        return view('inventory.stock', compact('stocks','items','stores','sort','direction'));
    }

    /**
     * Disallow manual stock updates to preserve integrity.
     */
    public function updateStock(\Illuminate\Http\Request $request)
    {
        return back()->with('error', 'Manual stock adjustments are not allowed. Use Purchases, Stock Transfers, or Usage.');
    }
}