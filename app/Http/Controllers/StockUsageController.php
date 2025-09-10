<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\StockUsage;
use App\Models\Store;
use App\Models\Unit;
use App\Services\ExcelExportService;
use App\Services\PdfExportService;
use App\Services\StockUsageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StockUsageController extends Controller
{
    public function __construct(private StockUsageService $service)
    {
        $this->middleware('permission:view_stock_usage')->only(['index', 'show']);
        $this->middleware('permission:create_stock_usage')->only(['create', 'store']);
        $this->middleware('permission:edit_stock_usage')->only(['edit', 'update']);
        $this->middleware('permission:delete_stock_usage')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
            'store_id' => 'nullable|exists:stores,id',
            'inventory_item_id' => 'nullable|exists:inventory_items,id',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'per_page' => 'nullable|integer|in:10,25,50,100',
            'sort' => 'nullable|in:usage_date,created_at',
            'direction' => 'nullable|in:asc,desc',
            'export' => 'nullable|in:csv,xlsx,pdf',
            'print' => 'nullable|boolean',
        ]);

        $perPage = (int)($validated['per_page'] ?? $request->session()->get('stock_usage.per_page', 10));
        $request->session()->put('stock_usage.per_page', $perPage);

        $query = StockUsage::with(['inventoryItem','store','unit','createdBy'])
            ->when(($validated['q'] ?? null), function ($q) use ($validated) {
                $term = $validated['q'];
                $q->whereHas('inventoryItem', fn($w)=>$w->where('name','like', "%{$term}%"))
                  ->orWhereHas('store', fn($w)=>$w->where('name','like', "%{$term}%"))
                  ->orWhere('operation_type','like', "%{$term}%");
            })
            ->when(($validated['store_id'] ?? null), fn($q,$id)=>$q->where('store_id',$id))
            ->when(($validated['inventory_item_id'] ?? null), fn($q,$id)=>$q->where('inventory_item_id',$id))
            ->when(($validated['from_date'] ?? null), fn($q,$d)=>$q->whereDate('usage_date','>=',$d))
            ->when(($validated['to_date'] ?? null), fn($q,$d)=>$q->whereDate('usage_date','<=',$d));

        $sort = $validated['sort'] ?? 'usage_date';
        $direction = $validated['direction'] ?? 'desc';
        $query->orderBy($sort, $direction);

        if (!empty($validated['export'] ?? null)) {
            abort_unless(\Illuminate\Support\Facades\Gate::allows('export_stock_usage'), 403);
        }
        if (($validated['export'] ?? null) === 'csv') {
            $rows = (clone $query)->get();
            $filename = 'stock_usage_' . now()->format('Ymd_His') . '.csv';
            return response()->streamDownload(function () use ($rows) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Item','Store','Unit','Quantity Used','Operation','Date','By']);
                foreach ($rows as $r) {
                    fputcsv($out, [
                        optional($r->inventoryItem)->name,
                        optional($r->store)->name,
                        optional($r->unit)->name,
                        number_format($r->quantity_used, 2, '.', ''),
                        $r->operation_type,
                        $r->usage_date,
                        optional($r->createdBy)->name,
                    ]);
                }
                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv']);
        }
        if (($validated['export'] ?? null) === 'xlsx') {
            $rows = (clone $query)->get()->map(fn($r)=>[
                optional($r->inventoryItem)->name,
                optional($r->store)->name,
                optional($r->unit)->name,
                number_format($r->quantity_used, 2, '.', ''),
                $r->operation_type,
                $r->usage_date,
                optional($r->createdBy)->name,
            ]);
            return ExcelExportService::streamSimpleXlsx(
                'stock_usage_'.now()->format('Ymd_His').'.xlsx',
                ['Item','Store','Unit','Quantity Used','Operation','Date','By'],
                $rows
            );
        }
        if (($validated['export'] ?? null) === 'pdf') {
            $rows = (clone $query)->get()->map(fn($r)=>[
                'Item' => optional($r->inventoryItem)->name,
                'Store' => optional($r->store)->name,
                'Unit' => optional($r->unit)->name,
                'Quantity Used' => number_format($r->quantity_used, 2, '.', ''),
                'Operation' => $r->operation_type,
                'Date' => $r->usage_date,
                'By' => optional($r->createdBy)->name,
            ]);
            return PdfExportService::streamSimpleTable(
                'stock_usage_'.now()->format('Ymd_His').'.pdf',
                'Stock Usage',
                ['Item','Store','Unit','Quantity Used','Operation','Date','By'],
                $rows
            );
        }
        if ($request->boolean('print')) {
            abort_unless(\Illuminate\Support\Facades\Gate::allows('print_stock_usage'), 403);
            $rows = (clone $query)->get()->map(fn($r)=>[
                'Item' => optional($r->inventoryItem)->name,
                'Store' => optional($r->store)->name,
                'Unit' => optional($r->unit)->name,
                'Quantity Used' => number_format($r->quantity_used, 2, '.', ''),
                'Operation' => $r->operation_type,
                'Date' => $r->usage_date,
                'By' => optional($r->createdBy)->name,
            ]);
            return view('exports.simple_table', [
                'title' => 'Stock Usage',
                'columns' => ['Item','Store','Unit','Quantity Used','Operation','Date','By'],
                'rows' => $rows,
            ]);
        }

        $stockUsage = $query->paginate($perPage)
            ->appends(array_merge($request->only(['q','store_id','inventory_item_id','from_date','to_date','sort','direction']), ['per_page' => $perPage]));

        return view('stock-usage.index', compact('stockUsage', 'sort', 'direction'));
    }

    public function create()
    {
        $stores = Store::orderBy('name')->get();
        $inventoryItems = InventoryItem::with('unit')->get();
        $units = Unit::orderBy('name')->get();
        return view('stock-usage.create', compact('stores','inventoryItems','units'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'usage_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.inventory_item_id' => 'required|exists:inventory_items,id',
            'items.*.unit_id' => 'nullable|exists:units,id',
            'items.*.quantity_used' => 'required|numeric|min:0.01',
            'items.*.operation_type' => 'required|in:washing,drying,ironing,packaging,other',
        ]);

        try {
            $count = $this->service->recordBulkUsage([
                'store_id' => (int)$request->store_id,
                'usage_date' => $request->usage_date,
                'created_by' => Auth::id(),
                'items' => $request->items,
            ]);
        } catch (\Throwable $e) {
            return back()->withErrors(['items' => $e->getMessage()])->withInput();
        }

        return redirect()->route('stock-usage.index')->with('success', "$count usage rows recorded and stock updated.");
    }
}
