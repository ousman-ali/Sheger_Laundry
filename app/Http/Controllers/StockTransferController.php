<?php

namespace App\Http\Controllers;

use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\InventoryItem;
use App\Models\Store;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class StockTransferController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_stock_transfers')->only(['index', 'show']);
        $this->middleware('permission:create_stock_transfers')->only(['create', 'store']);
        $this->middleware('permission:edit_stock_transfers')->only(['edit', 'update']);
        $this->middleware('permission:delete_stock_transfers')->only(['destroy']);
    }

    public function index(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'per_page' => 'nullable|integer|in:10,25,50,100',
            'sort' => 'nullable|in:transferred_at,created_at',
            'direction' => 'nullable|in:asc,desc',
            'export' => 'nullable|in:csv,xlsx,pdf',
        ]);

        if (!empty($validated['per_page'] ?? null)) {
            $perPage = (int) $validated['per_page'];
            $request->session()->put('stock_transfers.per_page', $perPage);
        } else {
            $perPage = (int) $request->session()->get('stock_transfers.per_page', 10);
        }

        $query = StockTransfer::with(['fromStore','toStore','createdBy']);
        if (!empty($validated['q'] ?? null)) {
            $q = $validated['q'];
            $query->whereHas('fromStore', fn($w) => $w->where('name','like', "%{$q}%"))
                ->orWhereHas('toStore', fn($w) => $w->where('name','like', "%{$q}%"));
        }
        if (!empty($validated['from_date'] ?? null)) {
            $query->whereDate('transferred_at', '>=', $validated['from_date']);
        }
        if (!empty($validated['to_date'] ?? null)) {
            $query->whereDate('transferred_at', '<=', $validated['to_date']);
        }
        $sort = $validated['sort'] ?? 'transferred_at';
        $direction = $validated['direction'] ?? 'desc';
        $query->orderBy($sort, $direction);

        if (!empty($validated['export'] ?? null)) {
            abort_unless(\Illuminate\Support\Facades\Gate::allows('export_stock_transfers'), 403);
        }
        if (($validated['export'] ?? null) === 'csv') {
            $rows = (clone $query)->get();
            $filename = 'stock_transfers_' . now()->format('Ymd_His') . '.csv';
            return response()->streamDownload(function () use ($rows) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['From','To','Date','Created By']);
                foreach ($rows as $r) {
                    fputcsv($out, [
                        optional($r->fromStore)->name,
                        optional($r->toStore)->name,
                        $r->transferred_at,
                        optional($r->createdBy)->name,
                    ]);
                }
                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv']);
        }
        if (($validated['export'] ?? null) === 'xlsx') {
            $rows = (clone $query)->get()->map(fn($r) => [
                optional($r->fromStore)->name,
                optional($r->toStore)->name,
                $r->transferred_at,
                optional($r->createdBy)->name,
            ]);
            return \App\Services\ExcelExportService::streamSimpleXlsx(
                'stock_transfers_'.now()->format('Ymd_His').'.xlsx',
                ['From','To','Date','Created By'],
                $rows
            );
        }
        if (($validated['export'] ?? null) === 'pdf') {
            $rows = (clone $query)->get()->map(fn($r) => [
                'From' => optional($r->fromStore)->name,
                'To' => optional($r->toStore)->name,
                'Date' => $r->transferred_at,
                'Created By' => optional($r->createdBy)->name,
            ]);
            return \App\Services\PdfExportService::streamSimpleTable(
                'stock_transfers_'.now()->format('Ymd_His').'.pdf',
                'Stock Transfers',
                ['From','To','Date','Created By'],
                $rows
            );
        }

        if ($request->boolean('print')) {
            abort_unless(\Illuminate\Support\Facades\Gate::allows('print_stock_transfers'), 403);
            $rows = (clone $query)->get()->map(fn($r) => [
                'From' => optional($r->fromStore)->name,
                'To' => optional($r->toStore)->name,
                'Date' => $r->transferred_at,
                'Created By' => optional($r->createdBy)->name,
            ]);
            return view('exports.simple_table', [
                'title' => 'Stock Transfers',
                'columns' => ['From','To','Date','Created By'],
                'rows' => $rows,
            ]);
        }

        $stockTransfers = $query->paginate($perPage)
            ->appends(array_merge($request->only(['q','from_date','to_date','sort','direction']), ['per_page' => $perPage]));

        return view('stock-transfers.index', compact('stockTransfers', 'sort', 'direction'));
    }

    public function create()
    {
        $stores = Store::all();
        $inventoryItems = InventoryItem::with(['unit', 'inventoryStock.store'])->get();
        $units = Unit::all();
        
        return view('stock-transfers.create', compact('stores', 'inventoryItems', 'units'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'from_store_id' => 'required|exists:stores,id',
            'to_store_id' => 'required|exists:stores,id|different:from_store_id',
            'transferred_at' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.inventory_item_id' => 'required|exists:inventory_items,id',
            'items.*.unit_id' => 'nullable|exists:units,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

    // Preflight: normalize to canonical units and validate convertibility
        $normalized = [];
        $errors = [];
        foreach (array_values($request->items) as $idx => $item) {
            $inventoryItem = InventoryItem::find($item['inventory_item_id'] ?? null);
            if (!$inventoryItem) continue; // rely on validator
            $canonicalUnit = Unit::find($inventoryItem->unit_id);
            $requestedUnit = isset($item['unit_id']) ? Unit::find($item['unit_id']) : $canonicalUnit;
            $qtyRequested = (float) ($item['quantity'] ?? 0);

            if ($requestedUnit && $requestedUnit->id !== $canonicalUnit->id) {
        if (!$requestedUnit->isConvertibleTo($canonicalUnit)) {
                    $errors["items.$idx.unit_id"] = "Unit '{$requestedUnit->name}' is not convertible to '{$canonicalUnit->name}' for {$inventoryItem->name}.";
                    continue;
                }
                $qtyCanonical = $requestedUnit->convertTo($canonicalUnit, $qtyRequested);
            } else {
                $qtyCanonical = $qtyRequested;
            }

            $normalized[] = [
                'inventory_item_id' => $inventoryItem->id,
                'unit_id' => $canonicalUnit->id,
                'entered_unit_id' => $requestedUnit?->id,
                'entered_quantity' => $qtyRequested,
                'canonical_quantity' => $qtyCanonical,
                'quantity' => $qtyCanonical,
            ];
        }

        if (!empty($errors)) {
            return back()->withErrors($errors)->withInput();
        }

        // Aggregate source availability per item
        $byItem = [];
        foreach ($normalized as $n) {
            $byItem[$n['inventory_item_id']] = ($byItem[$n['inventory_item_id']] ?? 0) + $n['quantity'];
        }

        try {
            DB::transaction(function () use ($request, $normalized, $byItem) {
                // Check availability upfront
                foreach ($byItem as $invId => $qtyNeeded) {
                    $sourceStock = \App\Models\InventoryStock::where('inventory_item_id', $invId)
                        ->where('store_id', $request->from_store_id)
                        ->lockForUpdate()
                        ->first();
                    if (!$sourceStock || $sourceStock->quantity < $qtyNeeded) {
                        $itemName = optional(InventoryItem::find($invId))->name ?? ('#'.$invId);
                        $available = $sourceStock?->quantity ?? 0;
                        throw new \RuntimeException("Insufficient stock for $itemName. Needed $qtyNeeded, available $available in selected source store.");
                    }
                }

                $stockTransfer = StockTransfer::create([
                    'from_store_id' => $request->from_store_id,
                    'to_store_id' => $request->to_store_id,
                    'transferred_at' => $request->transferred_at,
                    'created_by' => Auth::id(),
                ]);

                // Apply line by line using normalized quantities
                foreach ($normalized as $n) {
                        StockTransferItem::create([
                            'stock_transfer_id' => $stockTransfer->id,
                            'inventory_item_id' => $n['inventory_item_id'],
                            'unit_id' => $n['unit_id'],
                            'entered_unit_id' => $n['entered_unit_id'] ?? $n['unit_id'],
                            'entered_quantity' => $n['entered_quantity'] ?? $n['quantity'],
                            'canonical_quantity' => $n['quantity'],
                            'quantity' => $n['quantity'],
                        ]);

                    $sourceStock = \App\Models\InventoryStock::where('inventory_item_id', $n['inventory_item_id'])
                        ->where('store_id', $request->from_store_id)
                        ->lockForUpdate()
                        ->first();
                    $sourceStock->decrement('quantity', $n['quantity']);

                    $destStock = \App\Models\InventoryStock::where('inventory_item_id', $n['inventory_item_id'])
                        ->where('store_id', $request->to_store_id)
                        ->lockForUpdate()
                        ->first();
                    if ($destStock) {
                        $destStock->increment('quantity', $n['quantity']);
                    } else {
                        \App\Models\InventoryStock::create([
                            'inventory_item_id' => $n['inventory_item_id'],
                            'store_id' => $request->to_store_id,
                            'quantity' => $n['quantity'],
                        ]);
                    }
                }
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['items' => $e->getMessage()])->withInput();
        }

        return redirect()->route('stock-transfers.index')
            ->with('success', 'Stock transfer created successfully.');
    }

    public function show(StockTransfer $stockTransfer)
    {
        $stockTransfer->load([
            'fromStore', 'toStore', 'createdBy', 
            'stockTransferItems.inventoryItem.unit'
        ]);
        
        return view('stock-transfers.show', compact('stockTransfer'));
    }

    public function edit(StockTransfer $stockTransfer)
    {
        $stockTransfer->load('stockTransferItems.inventoryItem.unit');
        $stores = Store::all();
        $inventoryItems = InventoryItem::with(['unit', 'inventoryStock.store'])->get();
        $units = Unit::all();

        // Availability in the current source store for quick reference in the form
        $availableByItem = \App\Models\InventoryStock::where('store_id', $stockTransfer->from_store_id)
            ->get()
            ->pluck('quantity', 'inventory_item_id');
        
        return view('stock-transfers.edit', compact('stockTransfer', 'stores', 'inventoryItems', 'units', 'availableByItem'));
    }

    public function update(Request $request, StockTransfer $stockTransfer)
    {
        $request->validate([
            'from_store_id' => 'required|exists:stores,id',
            'to_store_id' => 'required|exists:stores,id|different:from_store_id',
            'transferred_at' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.inventory_item_id' => 'required|exists:inventory_items,id',
            'items.*.unit_id' => 'nullable|exists:units,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

    // Normalize and validate convertibility
        $normalized = [];
        $errors = [];
        foreach (array_values($request->items) as $idx => $item) {
            $inventoryItem = InventoryItem::find($item['inventory_item_id'] ?? null);
            if (!$inventoryItem) continue;
            $canonicalUnit = Unit::find($inventoryItem->unit_id);
            $requestedUnit = isset($item['unit_id']) ? Unit::find($item['unit_id']) : $canonicalUnit;
            $qtyRequested = (float) ($item['quantity'] ?? 0);

            if ($requestedUnit && $requestedUnit->id !== $canonicalUnit->id) {
        if (!$requestedUnit->isConvertibleTo($canonicalUnit)) {
                    $errors["items.$idx.unit_id"] = "Unit '{$requestedUnit->name}' is not convertible to '{$canonicalUnit->name}' for {$inventoryItem->name}.";
                    continue;
                }
                $qtyCanonical = $requestedUnit->convertTo($canonicalUnit, $qtyRequested);
            } else {
                $qtyCanonical = $qtyRequested;
            }

            $normalized[] = [
                'inventory_item_id' => $inventoryItem->id,
                'unit_id' => $canonicalUnit->id,
                'entered_unit_id' => $requestedUnit?->id,
                'entered_quantity' => $qtyRequested,
                'quantity' => $qtyCanonical,
            ];
        }
        if (!empty($errors)) {
            return back()->withErrors($errors)->withInput();
        }

        // Group old and new by item
        $stockTransfer->load('stockTransferItems');
        $oldByItem = [];
        foreach ($stockTransfer->stockTransferItems as $it) {
            $oldByItem[$it->inventory_item_id] = ($oldByItem[$it->inventory_item_id] ?? 0) + (float) $it->quantity;
        }
        $newByItem = [];
        foreach ($normalized as $n) {
            $key = $n['inventory_item_id'];
            $newByItem[$key] = ($newByItem[$key] ?? 0) + (float) $n['quantity'];
        }

        $sameStores = ((int)$request->from_store_id === (int)$stockTransfer->from_store_id) && ((int)$request->to_store_id === (int)$stockTransfer->to_store_id);

        try {
            DB::transaction(function () use ($request, $stockTransfer, $normalized, $oldByItem, $newByItem, $sameStores) {
                if ($sameStores) {
                    // Delta-based adjustments per item
                    $allItemIds = array_values(array_unique(array_merge(array_keys($oldByItem), array_keys($newByItem))));

                    // Pre-check availability per delta
                    $problems = [];
                    foreach ($allItemIds as $invId) {
                        $oldQty = (float) ($oldByItem[$invId] ?? 0);
                        $newQty = (float) ($newByItem[$invId] ?? 0);
                        $delta = $newQty - $oldQty; // >0 means move from source to dest; <0 means return from dest to source
                        if ($delta > 0) {
                            $sourceStock = \App\Models\InventoryStock::where('inventory_item_id', $invId)
                                ->where('store_id', $request->from_store_id)
                                ->lockForUpdate()
                                ->first();
                            $available = $sourceStock?->quantity ?? 0;
                            if ($available < $delta) {
                                $name = optional(InventoryItem::find($invId))->name ?? ('#'.$invId);
                                $problems[] = "$name: need additional $delta, available $available in source.";
                            }
                        } elseif ($delta < 0) {
                            $need = -$delta;
                            $destStock = \App\Models\InventoryStock::where('inventory_item_id', $invId)
                                ->where('store_id', $request->to_store_id)
                                ->lockForUpdate()
                                ->first();
                            $available = $destStock?->quantity ?? 0;
                            if ($available < $need) {
                                $name = optional(InventoryItem::find($invId))->name ?? ('#'.$invId);
                                $problems[] = "$name: can return $available at most; requested return $need from destination.";
                            }
                        }
                    }
                    if (!empty($problems)) {
                        throw new \RuntimeException('Insufficient stock for delta change. ' . implode(' ', $problems));
                    }

                    // Update header date/time only (stores unchanged)
                    $stockTransfer->update([
                        'transferred_at' => $request->transferred_at,
                    ]);

                    // Replace line items to reflect new quantities
                    StockTransferItem::where('stock_transfer_id', $stockTransfer->id)->delete();
                    foreach ($normalized as $n) {
                        StockTransferItem::create([
                            'stock_transfer_id' => $stockTransfer->id,
                            'inventory_item_id' => $n['inventory_item_id'],
                            'unit_id' => $n['unit_id'],
                            'entered_unit_id' => $n['unit_id'],
                            'entered_quantity' => $n['entered_quantity'] ?? $n['quantity'],
                            'canonical_quantity' => $n['quantity'],
                            'quantity' => $n['quantity'],
                        ]);
                    }

                    // Apply stock deltas
                    foreach ($allItemIds as $invId) {
                        $oldQty = (float) ($oldByItem[$invId] ?? 0);
                        $newQty = (float) ($newByItem[$invId] ?? 0);
                        $delta = $newQty - $oldQty;
                        if ($delta === 0.0) continue;
                        if ($delta > 0) {
                            // move delta from source to dest
                            $sourceStock = \App\Models\InventoryStock::where('inventory_item_id', $invId)
                                ->where('store_id', $request->from_store_id)
                                ->lockForUpdate()
                                ->first();
                            $sourceStock->decrement('quantity', $delta);

                            $destStock = \App\Models\InventoryStock::where('inventory_item_id', $invId)
                                ->where('store_id', $request->to_store_id)
                                ->lockForUpdate()
                                ->first();
                            if ($destStock) {
                                $destStock->increment('quantity', $delta);
                            } else {
                                \App\Models\InventoryStock::create([
                                    'inventory_item_id' => $invId,
                                    'store_id' => $request->to_store_id,
                                    'quantity' => $delta,
                                ]);
                            }
                        } else { // delta < 0
                            $need = -$delta;
                            // move need from dest to source
                            $destStock = \App\Models\InventoryStock::where('inventory_item_id', $invId)
                                ->where('store_id', $request->to_store_id)
                                ->lockForUpdate()
                                ->first();
                            $destStock->decrement('quantity', $need);

                            $sourceStock = \App\Models\InventoryStock::where('inventory_item_id', $invId)
                                ->where('store_id', $request->from_store_id)
                                ->lockForUpdate()
                                ->first();
                            if ($sourceStock) {
                                $sourceStock->increment('quantity', $need);
                            } else {
                                \App\Models\InventoryStock::create([
                                    'inventory_item_id' => $invId,
                                    'store_id' => $request->from_store_id,
                                    'quantity' => $need,
                                ]);
                            }
                        }
                    }
                } else {
                    // Store pair changed: perform full reverse then apply

                    // Check destination has enough to reverse ALL old quantities
                    foreach ($oldByItem as $invId => $qtyOld) {
                        $destStock = \App\Models\InventoryStock::where('inventory_item_id', $invId)
                            ->where('store_id', $stockTransfer->to_store_id)
                            ->lockForUpdate()
                            ->first();
                        $available = $destStock?->quantity ?? 0;
                        if ($available < $qtyOld) {
                            $name = optional(InventoryItem::find($invId))->name ?? ('#'.$invId);
                            throw new \RuntimeException("Cannot edit transfer. $name: need $qtyOld, available $available at destination to reverse previous transfer.");
                        }
                    }
                    // Check source has enough for all new quantities
                    foreach ($newByItem as $invId => $qtyNew) {
                        $sourceStock = \App\Models\InventoryStock::where('inventory_item_id', $invId)
                            ->where('store_id', $request->from_store_id)
                            ->lockForUpdate()
                            ->first();
                        $available = $sourceStock?->quantity ?? 0;
                        if ($available < $qtyNew) {
                            $name = optional(InventoryItem::find($invId))->name ?? ('#'.$invId);
                            throw new \RuntimeException("Insufficient stock in source store. $name: need $qtyNew, available $available.");
                        }
                    }

                    // Reverse old
                    foreach ($oldByItem as $invId => $qtyOld) {
                        $src = \App\Models\InventoryStock::where('inventory_item_id', $invId)
                            ->where('store_id', $stockTransfer->from_store_id)
                            ->lockForUpdate()
                            ->first();
                        if ($src) { $src->increment('quantity', $qtyOld); } else {
                            \App\Models\InventoryStock::create(['inventory_item_id'=>$invId,'store_id'=>$stockTransfer->from_store_id,'quantity'=>$qtyOld]);
                        }
                        $dst = \App\Models\InventoryStock::where('inventory_item_id', $invId)
                            ->where('store_id', $stockTransfer->to_store_id)
                            ->lockForUpdate()
                            ->first();
                        $dst->decrement('quantity', $qtyOld);
                    }

                    // Update header
                    $stockTransfer->update([
                        'from_store_id' => $request->from_store_id,
                        'to_store_id' => $request->to_store_id,
                        'transferred_at' => $request->transferred_at,
                    ]);

                    // Replace items
                    StockTransferItem::where('stock_transfer_id', $stockTransfer->id)->delete();
                    foreach ($normalized as $n) {
                        StockTransferItem::create([
                            'stock_transfer_id' => $stockTransfer->id,
                            'inventory_item_id' => $n['inventory_item_id'],
                            'unit_id' => $n['unit_id'],
                                'entered_unit_id' => $n['entered_unit_id'] ?? $n['unit_id'],
                                'entered_quantity' => $n['entered_quantity'] ?? $n['quantity'],
                                'canonical_quantity' => $n['quantity'],
                                'quantity' => $n['quantity'],
                            ]);

                        $sourceStock = \App\Models\InventoryStock::where('inventory_item_id', $n['inventory_item_id'])
                            ->where('store_id', $request->from_store_id)
                            ->lockForUpdate()
                            ->first();
                        $sourceStock->decrement('quantity', $n['quantity']);

                        $destStock = \App\Models\InventoryStock::where('inventory_item_id', $n['inventory_item_id'])
                            ->where('store_id', $request->to_store_id)
                            ->lockForUpdate()
                            ->first();
                        if ($destStock) { $destStock->increment('quantity', $n['quantity']); } else {
                            \App\Models\InventoryStock::create(['inventory_item_id'=>$n['inventory_item_id'],'store_id'=>$request->to_store_id,'quantity'=>$n['quantity']]);
                        }
                    }
                }
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['items' => $e->getMessage()])->withInput();
        }

        return redirect()->route('stock-transfers.show', $stockTransfer)
            ->with('success', 'Stock transfer updated successfully.');
    }

    public function destroy(StockTransfer $stockTransfer)
    {
        DB::transaction(function () use ($stockTransfer) {
            $stockTransfer->load('stockTransferItems');

            // Reverse stock movements before deleting
            foreach ($stockTransfer->stockTransferItems as $it) {
                $sourceStock = \App\Models\InventoryStock::where('inventory_item_id', $it->inventory_item_id)
                    ->where('store_id', $stockTransfer->from_store_id)
                    ->lockForUpdate()
                    ->first();
                if ($sourceStock) {
                    $sourceStock->increment('quantity', $it->quantity);
                } else {
                    \App\Models\InventoryStock::create([
                        'inventory_item_id' => $it->inventory_item_id,
                        'store_id' => $stockTransfer->from_store_id,
                        'quantity' => $it->quantity,
                    ]);
                }

                $destStock = \App\Models\InventoryStock::where('inventory_item_id', $it->inventory_item_id)
                    ->where('store_id', $stockTransfer->to_store_id)
                    ->lockForUpdate()
                    ->first();
                if (!$destStock || $destStock->quantity < $it->quantity) {
                    throw new \Exception('Cannot delete transfer: destination stock would go negative for item ID ' . $it->inventory_item_id);
                }
                $destStock->decrement('quantity', $it->quantity);
            }

            // Delete items and header
            StockTransferItem::where('stock_transfer_id', $stockTransfer->id)->delete();
            $stockTransfer->delete();
        });

        return redirect()->route('stock-transfers.index')
            ->with('success', 'Stock transfer deleted successfully.');
    }

    public function createReturn(Request $request, StockTransfer $stockTransfer)
    {
        $this->middleware('permission:create_stock_transfers');

        $stockTransfer->load('stockTransferItems');

        try {
            DB::transaction(function () use ($stockTransfer) {
                // Pre-check: destination (original from_store) must have enough to receive return? Not needed; receiving side has no cap.
                // Check: current source (original to_store) must have the quantities to send back.
                foreach ($stockTransfer->stockTransferItems as $it) {
                    $destStock = \App\Models\InventoryStock::where('inventory_item_id', $it->inventory_item_id)
                        ->where('store_id', $stockTransfer->to_store_id)
                        ->lockForUpdate()
                        ->first();
                    $available = $destStock?->quantity ?? 0;
                    if ($available < $it->quantity) {
                        $name = optional(InventoryItem::find($it->inventory_item_id))->name ?? ('#'.$it->inventory_item_id);
                        throw new \RuntimeException("Cannot return. $name: need {$it->quantity}, available $available in {$stockTransfer->toStore->name}.");
                    }
                }

                // Create reverse header
                $reverse = StockTransfer::create([
                    'from_store_id' => $stockTransfer->to_store_id,
                    'to_store_id' => $stockTransfer->from_store_id,
                    'transferred_at' => now(),
                            'created_by' => Auth::id(),
                ]);

                // Copy items and move stock back
                foreach ($stockTransfer->stockTransferItems as $it) {
                    StockTransferItem::create([
                        'stock_transfer_id' => $reverse->id,
                        'inventory_item_id' => $it->inventory_item_id,
                        'unit_id' => $it->unit_id,
                            'entered_unit_id' => $it->entered_unit_id ?? $it->unit_id,
                            'entered_quantity' => $it->entered_quantity ?? $it->quantity,
                            'canonical_quantity' => $it->quantity,
                            'quantity' => $it->quantity,
                        ]);

                    // Move physically
                    $fromStock = \App\Models\InventoryStock::where('inventory_item_id', $it->inventory_item_id)
                        ->where('store_id', $reverse->from_store_id)
                        ->lockForUpdate()
                        ->first();
                    $fromStock->decrement('quantity', $it->quantity);

                    $toStock = \App\Models\InventoryStock::where('inventory_item_id', $it->inventory_item_id)
                        ->where('store_id', $reverse->to_store_id)
                        ->lockForUpdate()
                        ->first();
                    if ($toStock) { $toStock->increment('quantity', $it->quantity); } else {
                        \App\Models\InventoryStock::create([
                            'inventory_item_id' => $it->inventory_item_id,
                            'store_id' => $reverse->to_store_id,
                            'quantity' => $it->quantity,
                        ]);
                    }
                }
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['items' => $e->getMessage()])->withInput();
        }

        return redirect()->route('stock-transfers.index')
            ->with('success', 'Return transfer created successfully.');
    }
}