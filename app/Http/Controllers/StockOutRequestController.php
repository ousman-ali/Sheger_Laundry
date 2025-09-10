<?php

namespace App\Http\Controllers;

use App\Models\StockOutRequest;
use App\Models\StockOutRequestItem;
use App\Models\InventoryItem;
use App\Models\Store;
use App\Models\Unit;
use App\Services\ExcelExportService;
use App\Services\PdfExportService;
use App\Services\StockUsageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockOutRequestController extends Controller
{
    public function __construct(private StockUsageService $usageService)
    {
        $this->middleware('permission:view_stock_out_requests')->only(['index','show']);
        $this->middleware('permission:create_stock_out_requests')->only(['create','store']);
        $this->middleware('permission:edit_stock_out_requests')->only(['edit','update','submit','cancel']);
        $this->middleware('role:Admin')->only(['approve','reject']);
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
            'status' => 'nullable|in:draft,submitted,approved,rejected,cancelled',
            'store_id' => 'nullable|exists:stores,id',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'export' => 'nullable|in:csv,xlsx,pdf',
            'print' => 'nullable|boolean',
        ]);

        $query = StockOutRequest::with(['store','requestedBy','approvedBy'])
            ->when(($validated['q'] ?? null), function ($q) use ($validated) {
                $term = $validated['q'];
                $q->where('request_no','like', "%{$term}%")
                    ->orWhereHas('requestedBy', fn($w)=>$w->where('name','like', "%{$term}%"));
            })
            ->when(($validated['status'] ?? null), fn($q,$s)=>$q->where('status',$s))
            ->when(($validated['store_id'] ?? null), fn($q,$id)=>$q->where('store_id',$id))
            ->when(($validated['from_date'] ?? null), fn($q,$d)=>$q->whereDate('created_at','>=',$d))
            ->when(($validated['to_date'] ?? null), fn($q,$d)=>$q->whereDate('created_at','<=',$d))
            ->orderByDesc('id');

        // On operator routes, scope to the current user's own requests
        if ($request->routeIs('operator.stock_out_requests.*')) {
            $query->where('requested_by', Auth::id());
        }

        if (!empty($validated['export'])) {
            abort_unless(\Illuminate\Support\Facades\Gate::allows('export_stock_out_requests'), 403);
        }

        if (($validated['export'] ?? null) === 'csv') {
            $rows = (clone $query)->get();
            $filename = 'stock_out_requests_' . now()->format('Ymd_His') . '.csv';
            return response()->streamDownload(function () use ($rows) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Request#','Store','Status','Requested By','Approved By','Approved At','Created At']);
                foreach ($rows as $r) {
                    fputcsv($out, [
                        $r->request_no,
                        optional($r->store)->name,
                        $r->status,
                        optional($r->requestedBy)->name,
                        optional($r->approvedBy)->name,
                        optional($r->approved_at)?->toDateTimeString(),
                        optional($r->created_at)?->toDateTimeString(),
                    ]);
                }
                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv']);
        }
        if (($validated['export'] ?? null) === 'xlsx') {
            $rows = (clone $query)->get()->map(fn($r)=>[
                $r->request_no,
                optional($r->store)->name,
                $r->status,
                optional($r->requestedBy)->name,
                optional($r->approvedBy)->name,
                optional($r->approved_at)?->toDateTimeString(),
                optional($r->created_at)?->toDateTimeString(),
            ]);
            return ExcelExportService::streamSimpleXlsx(
                'stock_out_requests_'.now()->format('Ymd_His').'.xlsx',
                ['Request#','Store','Status','Requested By','Approved By','Approved At','Created At'],
                $rows
            );
        }
        if (($validated['export'] ?? null) === 'pdf') {
            $rows = (clone $query)->get()->map(fn($r)=>[
                'Request#' => $r->request_no,
                'Store' => optional($r->store)->name,
                'Status' => $r->status,
                'Requested By' => optional($r->requestedBy)->name,
                'Approved By' => optional($r->approvedBy)->name,
                'Approved At' => optional($r->approved_at)?->toDateTimeString(),
                'Created At' => optional($r->created_at)?->toDateTimeString(),
            ]);
            return PdfExportService::streamSimpleTable(
                'stock_out_requests_'.now()->format('Ymd_His').'.pdf',
                'Stock-out Requests',
                ['Request#','Store','Status','Requested By','Approved By','Approved At','Created At'],
                $rows
            );
        }
        if ($request->boolean('print')) {
            abort_unless(\Illuminate\Support\Facades\Gate::allows('print_stock_out_requests'), 403);
            $rows = (clone $query)->get()->map(fn($r)=>[
                'Request#' => $r->request_no,
                'Store' => optional($r->store)->name,
                'Status' => $r->status,
                'Requested By' => optional($r->requestedBy)->name,
                'Approved By' => optional($r->approvedBy)->name,
                'Approved At' => optional($r->approved_at)?->toDateTimeString(),
                'Created At' => optional($r->created_at)?->toDateTimeString(),
            ]);
            return view('exports.simple_table', [
                'title' => 'Stock-out Requests',
                'columns' => ['Request#','Store','Status','Requested By','Approved By','Approved At','Created At'],
                'rows' => $rows,
            ]);
        }

        $perPage = (int)($request->input('per_page', 10));
        $requests = $query->paginate($perPage)->appends($request->query());
        return view('stock-out-requests.index', compact('requests'));
    }

    public function create()
    {
        $stores = Store::orderBy('name')->get();
        $inventoryItems = InventoryItem::with('unit')->orderBy('name')->get();
        $units = Unit::orderBy('name')->get();
        return view('stock-out-requests.create', compact('stores','inventoryItems','units'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'remarks' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.inventory_item_id' => 'required|exists:inventory_items,id',
            'items.*.unit_id' => [
                'required',
                'exists:units,id',
                function($attribute, $value, $fail) use ($request) {
                    // Extract the index from items.X.unit_id
                    if (!preg_match('/items\\.(\d+)\\.unit_id/', $attribute, $m)) {
                        return;
                    }
                    $idx = (int)$m[1];
                    $itemId = $request->input("items.$idx.inventory_item_id");
                    if (!$itemId) return; // other rule will catch missing item id
                    $inv = \App\Models\InventoryItem::with('unit')->find($itemId);
                    $unit = \App\Models\Unit::find($value);
                    if ($inv && $inv->unit && $unit) {
                        try {
                            if (!$unit->isConvertibleTo($inv->unit)) {
                                $fail('Selected unit is not compatible with the item\'s base unit.');
                            }
                        } catch (\Throwable $e) {
                            $fail('Unit convertibility check failed.');
                        }
                    }
                }
            ],
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        $reqNo = 'SOR-'.now()->format('Ymd-His').'-'.substr((string)mt_rand(100,999), -3);
        DB::transaction(function () use ($data, $reqNo) {
            $r = StockOutRequest::create([
                'request_no' => $reqNo,
                'store_id' => (int)$data['store_id'],
                'requested_by' => Auth::id(),
                'status' => 'submitted',
                'remarks' => $data['remarks'] ?? null,
            ]);
            foreach ($data['items'] as $it) {
                StockOutRequestItem::create([
                    'stock_out_request_id' => $r->id,
                    'inventory_item_id' => (int)$it['inventory_item_id'],
                    'unit_id' => (int)$it['unit_id'],
                    'quantity' => (float)$it['quantity'],
                ]);
            }
        });

        return redirect()->route('stock-out-requests.index')->with('success', 'Request created and submitted.');
    }

    public function show(StockOutRequest $stock_out_request)
    {
        $stock_out_request->load(['store','requestedBy','approvedBy','items.inventoryItem.unit','items.unit']);
        return view('stock-out-requests.show', ['req' => $stock_out_request]);
    }

    public function submit(StockOutRequest $stock_out_request)
    {
        abort_unless(in_array($stock_out_request->status, ['draft','rejected'], true), 403);
        $stock_out_request->status = 'submitted';
        $stock_out_request->save();
        return back()->with('success', 'Request submitted for approval.');
    }

    public function cancel(StockOutRequest $stock_out_request)
    {
        abort_unless(in_array($stock_out_request->status, ['draft','submitted'], true), 403);
        $stock_out_request->status = 'cancelled';
        $stock_out_request->save();
        return back()->with('success', 'Request cancelled.');
    }

    public function approve(StockOutRequest $stock_out_request)
    {
        abort_unless($stock_out_request->status === 'submitted', 403);
        // Convert to stock usage atomically
        try {
            DB::transaction(function () use ($stock_out_request) {
                $payload = [
                    'store_id' => $stock_out_request->store_id,
                    'usage_date' => now(),
                    'created_by' => Auth::id(),
                    'items' => [],
                ];
                foreach ($stock_out_request->items as $it) {
                    $payload['items'][] = [
                        'inventory_item_id' => $it->inventory_item_id,
                        'unit_id' => $it->unit_id,
                        'quantity_used' => $it->quantity,
                        'operation_type' => 'other',
                    ];
                }
                $this->usageService->recordBulkUsage($payload);
                $stock_out_request->status = 'approved';
                $stock_out_request->approved_by = Auth::id();
                $stock_out_request->approved_at = now();
                $stock_out_request->save();
            });
        } catch (\Throwable $e) {
            $msg = $e->getMessage() ?: 'Unable to approve request due to a stock validation error.';
            return back()->with('error', $msg);
        }

        return back()->with('success', 'Request approved and usage recorded.');
    }

    public function reject(StockOutRequest $stock_out_request)
    {
        abort_unless($stock_out_request->status === 'submitted', 403);
        $stock_out_request->status = 'rejected';
        $stock_out_request->approved_by = null;
        $stock_out_request->approved_at = null;
        $stock_out_request->save();
        return back()->with('success', 'Request rejected.');
    }
}
