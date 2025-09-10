<?php

namespace App\Http\Controllers;

use App\Models\PaymentLedger;
use Illuminate\Http\Request;

class PaymentLedgerController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_payments')->only(['index','breakdown','pending']);
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'status' => 'nullable|in:pending,partial,paid',
            'q' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|in:10,25,50,100',
        ]);
        $perPage = (int)($validated['per_page'] ?? 25);
        $query = PaymentLedger::with(['order.customer'])
            ->when(($validated['status'] ?? null), fn($q,$s)=>$q->where('status',$s))
            ->when(($validated['q'] ?? null), function ($q) use ($validated) {
                $term = $validated['q'];
                $q->whereHas('order', fn($w)=>$w->where('order_id','like',"%{$term}%"))
                  ->orWhereHas('order.customer', fn($w)=>$w->where('name','like',"%{$term}%"));
            })
            ->orderByDesc('id');
        $ledgers = $query->paginate($perPage)->appends($request->query());
        return view('ledgers.index', compact('ledgers'));
    }

    public function breakdown(\App\Models\PaymentLedger $ledger)
    {
    // Refresh ledger totals to reflect latest payments
    try { $ledger->recalc(); } catch (\Throwable $e) { /* ignore */ }
    $ledger->load(['order.customer','payments' => function($q){ $q->orderByDesc('paid_at'); }]);
    // compute totals (include penalty via PaymentService)
    $order = $ledger->order()->with(['orderItems.clothItem.unit','orderItems.orderItemServices.service'])->first();
    $suggest = app(\App\Services\PaymentService::class)->suggestedAmountForOrder($order->id);
    $subtotal = (float)($suggest['base'] ?? (float)($order->total_cost ?? 0));
    $penalty = (float)($suggest['penalty'] ?? 0);
    $total = (float)($suggest['total'] ?? (float)($order->total_cost ?? 0));
    // Use payments sum to be robust if amount_received wasn't persisted yet
    $completed = (float)$ledger->payments()->where('status','completed')->sum('amount');
    $refunded = (float)$ledger->payments()->where('status','refunded')->sum('amount');
    $paid = max(0.0, $completed - $refunded);
    $due = max(0, $total - $paid);
    return view('ledgers._breakdown', compact('ledger','order','subtotal','penalty','total','paid','due'));
    }

    public function pending(Request $request)
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
            'customer_id' => 'nullable|exists:customers,id',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'per_page' => 'nullable|integer|in:10,25,50,100',
            'export' => 'nullable|in:csv,xlsx,pdf',
        ]);
            $perPage = (int)($validated['per_page'] ?? 10);
            if (!empty($validated['export'] ?? null)) {
                abort_unless(\Illuminate\Support\Facades\Gate::allows('export_payments'), 403);
            }
        $query = \App\Models\Order::with(['customer','paymentLedger','payments'])
            ->when(($validated['q'] ?? null), function ($q) use ($validated) {
                $term = $validated['q'];
                $q->where('order_id','like',"%{$term}%")
                  ->orWhereHas('customer', fn($w)=>$w->where('name','like', "%{$term}%"));
            })
            ->when(($validated['customer_id'] ?? null), fn($q,$id)=>$q->where('customer_id',$id))
            ->when(($validated['from_date'] ?? null), fn($q,$d)=>$q->whereDate('created_at','>=',$d))
            ->when(($validated['to_date'] ?? null), fn($q,$d)=>$q->whereDate('created_at','<=',$d))
            ->orderByDesc('id');

        // Only orders with due > 0 (exclude fully paid ledgers; compare net paid = completed - refunded vs total)
        $query->where(function ($w) {
            $w->whereDoesntHave('paymentLedger', function ($q) {
                $q->where('status', 'paid');
            })
            ->whereRaw(
                '(
                    COALESCE((SELECT SUM(amount) FROM payments WHERE payments.order_id = orders.id AND status = ?), 0)
                    - COALESCE((SELECT SUM(amount) FROM payments WHERE payments.order_id = orders.id AND status = ?), 0)
                ) < COALESCE(orders.total_cost, 0)',
                ['completed','refunded']
            );
        });

        // Exports
        if (($validated['export'] ?? null) === 'csv') {
            $rows = (clone $query)->get();
            $filename = 'pending_payments_'.now()->format('Ymd_His').'.csv';
            return response()->streamDownload(function () use ($rows) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Order ID','Customer','Penalty','Total','Paid','Due','Created']);
                foreach ($rows as $o) {
                    $suggest = app(\App\Services\PaymentService::class)->suggestedAmountForOrder($o->id);
                    $penalty = (float)($suggest['penalty'] ?? 0);
                    $total = (float)($suggest['total'] ?? (float)($o->total_cost ?? 0));
                    $completed = (float)$o->payments()->where('status','completed')->sum('amount');
                    $refunded = (float)$o->payments()->where('status','refunded')->sum('amount');
                    $paid = max(0.0, $completed - $refunded);
                    $due = max(0, $total - $paid);
                    fputcsv($out, [
                        $o->order_id,
                        optional($o->customer)->name,
                        number_format($penalty,2,'.',''),
                        number_format($total,2,'.',''),
                        number_format($paid,2,'.',''),
                        number_format($due,2,'.',''),
                        optional($o->created_at)->toDateTimeString(),
                    ]);
                }
                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv']);
        }
        if (($validated['export'] ?? null) === 'xlsx') {
            $rows = (clone $query)->get()->map(function ($o) {
                $suggest = app(\App\Services\PaymentService::class)->suggestedAmountForOrder($o->id);
                $penalty = (float)($suggest['penalty'] ?? 0);
                $total = (float)($suggest['total'] ?? (float)($o->total_cost ?? 0));
                $completed = (float)$o->payments()->where('status','completed')->sum('amount');
                $refunded = (float)$o->payments()->where('status','refunded')->sum('amount');
                $paid = max(0.0, $completed - $refunded);
                $due = max(0, $total - $paid);
                return [
                    $o->order_id,
                    optional($o->customer)->name,
                    number_format($penalty,2,'.',''),
                    number_format($total,2,'.',''),
                    number_format($paid,2,'.',''),
                    number_format($due,2,'.',''),
                    optional($o->created_at)->toDateTimeString(),
                ];
            });
            return \App\Services\ExcelExportService::streamSimpleXlsx(
                'pending_payments_'.now()->format('Ymd_His').'.xlsx',
                ['Order ID','Customer','Penalty','Total','Paid','Due','Created'],
                $rows
            );
        }
        if (($validated['export'] ?? null) === 'pdf') {
            $rows = (clone $query)->get()->map(function ($o) {
                $suggest = app(\App\Services\PaymentService::class)->suggestedAmountForOrder($o->id);
                $penalty = (float)($suggest['penalty'] ?? 0);
                $total = (float)($suggest['total'] ?? (float)($o->total_cost ?? 0));
                $completed = (float)$o->payments()->where('status','completed')->sum('amount');
                $refunded = (float)$o->payments()->where('status','refunded')->sum('amount');
                $paid = max(0.0, $completed - $refunded);
                $due = max(0, $total - $paid);
                return [
                    'Order ID' => $o->order_id,
                    'Customer' => optional($o->customer)->name,
                    'Penalty' => number_format($penalty,2,'.',''),
                    'Total' => number_format($total,2,'.',''),
                    'Paid' => number_format($paid,2,'.',''),
                    'Due' => number_format($due,2,'.',''),
                    'Created' => optional($o->created_at)->toDateTimeString(),
                ];
            });
            return \App\Services\PdfExportService::streamSimpleTable(
                'pending_payments_'.now()->format('Ymd_His').'.pdf',
                'Pending Payments',
                ['Order ID','Customer','Penalty','Total','Paid','Due','Created'],
                $rows
            );
        }
        if ($request->boolean('print')) {
            $rows = (clone $query)->get()->map(function ($o) {
                $suggest = app(\App\Services\PaymentService::class)->suggestedAmountForOrder($o->id);
                $penalty = (float)($suggest['penalty'] ?? 0);
                $total = (float)($suggest['total'] ?? (float)($o->total_cost ?? 0));
                $completed = (float)$o->payments()->where('status','completed')->sum('amount');
                $refunded = (float)$o->payments()->where('status','refunded')->sum('amount');
                $paid = max(0.0, $completed - $refunded);
                $due = max(0, $total - $paid);
                return [
                    'Order ID' => $o->order_id,
                    'Customer' => optional($o->customer)->name,
                    'Penalty' => number_format($penalty,2,'.',''),
                    'Total' => number_format($total,2,'.',''),
                    'Paid' => number_format($paid,2,'.',''),
                    'Due' => number_format($due,2,'.',''),
                    'Created' => optional($o->created_at)->toDateTimeString(),
                ];
            });
                abort_unless(\Illuminate\Support\Facades\Gate::allows('print_payments'), 403);
            return view('exports.simple_table', [
                'title' => 'Pending Payments',
                'columns' => ['Order ID','Customer','Penalty','Total','Paid','Due','Created'],
                'rows' => $rows,
            ]);
        }

        $orders = $query->paginate($perPage)->appends($request->query());
        return view('payments.pending', compact('orders'));
    }
}
