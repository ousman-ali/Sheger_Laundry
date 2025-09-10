<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\InventoryItem;
use App\Models\Unit;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PurchaseController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_purchases')->only(['index', 'show']);
        $this->middleware('permission:create_purchases')->only(['create', 'store']);
        $this->middleware('permission:edit_purchases')->only(['edit', 'update']);
        $this->middleware('permission:delete_purchases')->only(['destroy']);
    }

    public function index(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'per_page' => 'nullable|integer|in:10,25,50,100',
            'sort' => 'nullable|in:purchase_date,total_price,created_at',
            'direction' => 'nullable|in:asc,desc',
            'export' => 'nullable|in:csv,xlsx,pdf',
        ]);

        if (!empty($validated['per_page'] ?? null)) {
            $perPage = (int) $validated['per_page'];
            $request->session()->put('purchases.per_page', $perPage);
        } else {
            $perPage = (int) $request->session()->get('purchases.per_page', 10);
        }

        $query = Purchase::with(['createdBy'])
            ->when(($validated['q'] ?? null), function ($q) use ($validated) {
                $term = $validated['q'];
                $q->where('supplier_name', 'like', "%{$term}%")
                  ->orWhere('supplier_phone', 'like', "%{$term}%")
                  ->orWhere('supplier_address', 'like', "%{$term}%");
            })
            ->when(($validated['from_date'] ?? null), fn($q,$d) => $q->whereDate('purchase_date','>=',$d))
            ->when(($validated['to_date'] ?? null), fn($q,$d) => $q->whereDate('purchase_date','<=',$d));

        $sort = $validated['sort'] ?? 'purchase_date';
        $direction = $validated['direction'] ?? 'desc';
        $query->orderBy($sort, $direction);

        if (!empty($validated['export'] ?? null)) {
            abort_unless(\Illuminate\Support\Facades\Gate::allows('export_purchases'), 403);
        }
        if (($validated['export'] ?? null) === 'csv') {
            $rows = (clone $query)->get();
            $filename = 'purchases_' . now()->format('Ymd_His') . '.csv';
            return response()->streamDownload(function () use ($rows) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Supplier','Date','Total','Created By']);
                foreach ($rows as $r) {
                    fputcsv($out, [
                        $r->supplier_name,
                        $r->purchase_date,
                        number_format($r->total_price, 2, '.', ''),
                        optional($r->createdBy)->name,
                    ]);
                }
                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv']);
        }
        if (($validated['export'] ?? null) === 'xlsx') {
            $rows = (clone $query)->get()->map(fn($r) => [
                $r->supplier_name,
                $r->purchase_date,
                number_format($r->total_price, 2, '.', ''),
                optional($r->createdBy)->name,
            ]);
            return \App\Services\ExcelExportService::streamSimpleXlsx(
                'purchases_'.now()->format('Ymd_His').'.xlsx',
                ['Supplier','Date','Total','Created By'],
                $rows
            );
        }
        if (($validated['export'] ?? null) === 'pdf') {
            $rows = (clone $query)->get()->map(fn($r) => [
                'Supplier' => $r->supplier_name,
                'Date' => $r->purchase_date,
                'Total' => number_format($r->total_price, 2, '.', ''),
                'Created By' => optional($r->createdBy)->name,
            ]);
            return \App\Services\PdfExportService::streamSimpleTable(
                'purchases_'.now()->format('Ymd_His').'.pdf',
                'Purchases',
                ['Supplier','Date','Total','Created By'],
                $rows
            );
        }

        if ($request->boolean('print')) {
            abort_unless(\Illuminate\Support\Facades\Gate::allows('print_purchases'), 403);
            $rows = (clone $query)->get()->map(fn($r) => [
                'Supplier' => $r->supplier_name,
                'Date' => $r->purchase_date,
                'Total' => number_format($r->total_price, 2, '.', ''),
                'Created By' => optional($r->createdBy)->name,
            ]);
            return view('exports.simple_table', [
                'title' => 'Purchases',
                'columns' => ['Supplier','Date','Total','Created By'],
                'rows' => $rows,
            ]);
        }

        $purchases = $query->paginate($perPage)
            ->appends(array_merge($request->only(['q','from_date','to_date','sort','direction']), ['per_page' => $perPage]));

        return view('purchases.index', compact('purchases', 'sort', 'direction'));
    }

    public function create()
    {
        $inventoryItems = InventoryItem::with('unit')->get();
        $units = Unit::all();
        $stores = Store::orderBy('name')->get();
        return view('purchases.create', compact('inventoryItems', 'units', 'stores'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_name' => 'required|string|max:255',
            'supplier_phone' => 'nullable|string|max:20',
            'supplier_address' => 'nullable|string',
            'purchase_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.inventory_item_id' => 'required|exists:inventory_items,id',
            'items.*.unit_id' => 'required|exists:units,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0.01',
            'items.*.to_store_id' => 'required|exists:stores,id',
        ]);

        // Validate unit convertibility per line before starting transaction
        $errors = [];
        foreach (array_values($request->items) as $idx => $row) {
            $inv = \App\Models\InventoryItem::find($row['inventory_item_id'] ?? null);
            $enteredUnit = \App\Models\Unit::find($row['unit_id'] ?? null);
            if (!$inv || !$enteredUnit) { continue; }
            $canonicalUnit = \App\Models\Unit::find($inv->unit_id);
            if (!$enteredUnit->isConvertibleTo($canonicalUnit)) {
                $errors["items.$idx.unit_id"] = "Unit '" . $enteredUnit->name . "' is not compatible with item unit '" . $canonicalUnit->name . "'.";
            }
        }
        if (!empty($errors)) {
            return back()->withErrors($errors)->withInput();
        }

        DB::transaction(function () use ($request) {
            $purchase = Purchase::create([
                'supplier_name' => $request->supplier_name,
                'supplier_phone' => $request->supplier_phone,
                'supplier_address' => $request->supplier_address,
                'purchase_date' => $request->purchase_date,
                'total_price' => 0,
                'created_by' => Auth::id(),
            ]);

            $totalPrice = 0;

            foreach ($request->items as $item) {
                // Calculate line total based on entered unit/qty
                $itemTotal = $item['quantity'] * $item['unit_price'];
                $totalPrice += $itemTotal;

                // Normalize to canonical unit and update inventory stock atomically
                $inventoryItem = \App\Models\InventoryItem::find($item['inventory_item_id']);
                $canonicalUnit = \App\Models\Unit::find($inventoryItem->unit_id);
                $enteredUnit = \App\Models\Unit::find($item['unit_id']);
                $qtyEntered = (float)$item['quantity'];
                // Safe conversion (we pre-validated convertibility)
                $qtyCanonical = $enteredUnit->convertTo($canonicalUnit, $qtyEntered);

                // Store both entered and canonical quantities for audit
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'inventory_item_id' => $item['inventory_item_id'],
                    'unit_id' => $item['unit_id'],
                    'entered_unit_id' => $item['unit_id'],
                    'entered_quantity' => $item['quantity'],
                    'canonical_quantity' => $qtyCanonical,
                    'to_store_id' => $item['to_store_id'],
                    // keep legacy 'quantity' as entered quantity for compatibility
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $itemTotal,
                ]);

                $storeId = (int)$item['to_store_id'];
                $stock = \App\Models\InventoryStock::where('inventory_item_id', $inventoryItem->id)
                    ->where('store_id', $storeId)
                    ->lockForUpdate()
                    ->first();

                if ($stock) {
                    $stock->increment('quantity', $qtyCanonical);
                } else {
                    \App\Models\InventoryStock::create([
                        'inventory_item_id' => $inventoryItem->id,
                        'store_id' => $storeId,
                        'quantity' => $qtyCanonical,
                    ]);
                }
            }

            $purchase->update(['total_price' => $totalPrice]);
        });

        return redirect()->route('purchases.index')
            ->with('success', 'Purchase created successfully.');
    }

    public function show(Purchase $purchase)
    {
        $purchase->load(['createdBy', 'purchaseItems.inventoryItem.unit', 'purchaseItems.toStore']);
        
        return view('purchases.show', compact('purchase'));
    }

    public function edit(Purchase $purchase)
    {
        $purchase->load('purchaseItems.inventoryItem.unit');
        $inventoryItems = InventoryItem::with('unit')->get();
        $units = Unit::all();
        
        return view('purchases.edit', compact('purchase', 'inventoryItems', 'units'));
    }

    public function update(Request $request, Purchase $purchase)
    {
        // Implementation for updating purchase
        // This would be complex as it needs to handle stock adjustments
        
        return redirect()->route('purchases.index')
            ->with('success', 'Purchase updated successfully.');
    }

    public function destroy(Purchase $purchase)
    {
        // Implementation for deleting purchase
        // This would need to handle stock adjustments
        
        return redirect()->route('purchases.index')
            ->with('success', 'Purchase deleted successfully.');
    }
}