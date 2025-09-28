<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Utils\EthiopianCalendar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    public function __construct()
    {
        // Invoices index should be visible only to users who can view payments
        $this->middleware('permission:view_payments')->only('index');
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
            'customer_id' => 'nullable|exists:customers,id',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'status' => 'nullable|in:paid,partial,unpaid',
            'per_page' => 'nullable|integer|in:10,25,50,100',
            'sort' => 'nullable|in:order_id,created_at,total_cost,paid_amount,due',
            'direction' => 'nullable|in:asc,desc',
            'export' => 'nullable|in:csv,xlsx,pdf',
        ]);

        $perPage = (int)($validated['per_page'] ?? 10);
        $sort = $validated['sort'] ?? 'created_at';
        $direction = $validated['direction'] ?? 'desc';

        $query = Order::query()
            ->with([
                'customer',
                'orderItems.clothItem.pricingTiers.service', // brings unit prices for services
                'orderItems.orderItemServices.service',
                'orderItems.orderItemServices.urgencyTier',
            ])
            ->withSum(['payments as paid_amount' => function ($q) {
                $q->where('status', 'completed');
            }], 'amount')
            ->when(($validated['q'] ?? null), function ($q) use ($validated) {
                $term = $validated['q'];
                $q->where(function ($w) use ($term) {
                    $w->where('order_id', 'like', "%{$term}%")
                      ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%{$term}%")->orWhere('code','like',"%{$term}%"));
                });
            })
            ->when(($validated['customer_id'] ?? null), fn($q, $id) => $q->where('customer_id', $id))
            ->when(($validated['from_date'] ?? null), fn($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when(($validated['to_date'] ?? null), fn($q, $d) => $q->whereDate('created_at', '<=', $d));

        // Status filter uses computed paid_amount vs total_cost
        if (!empty($validated['status'])) {
            $status = $validated['status'];
            // Use havingRaw as paid_amount is a select alias
            if ($status === 'unpaid') {
                $query->havingRaw('(COALESCE(paid_amount,0)) <= 0');
            } elseif ($status === 'paid') {
                $query->havingRaw('(COALESCE(paid_amount,0)) >= (COALESCE(total_cost,0)) - 0.01');
            } else { // partial
                $query->havingRaw('(COALESCE(paid_amount,0)) > 0 AND (COALESCE(paid_amount,0)) < (COALESCE(total_cost,0)) - 0.01');
            }
        }

        // Sorting; for due we sort by (total_cost - paid_amount)
        if ($sort === 'due') {
            $query->orderByRaw('(COALESCE(total_cost,0) - COALESCE(paid_amount,0)) '.($direction === 'asc' ? 'asc' : 'desc'));
        } else {
            $query->orderBy($sort, $direction);
        }

        // Exports
            if (!empty($validated['export'] ?? null)) {
                abort_unless(\Illuminate\Support\Facades\Gate::allows('export_invoices'), 403);
            }
            if (($validated['export'] ?? null) === 'csv') {
            $orders = (clone $query)->get();
            $filename = 'invoices_'.now()->format('Ymd_His').'.csv';
            return response()->streamDownload(function () use ($orders) {
                $out = fopen('php://output', 'w');
        fputcsv($out, ['Order ID','Customer','Customer Code','VIP','Total','Paid','Due','Created']);
                foreach ($orders as $order) {
                        $completedPaid = (float)$order->payments()->where('status','completed')->sum('amount');
                        $refundedAmt = (float)$order->payments()->where('status','refunded')->sum('amount');
                        $paid = max(0.0, $completedPaid - $refundedAmt);
                        $due = max(0, (float)($order->total_cost ?? 0) - $paid);
                    fputcsv($out, [
                        $order->order_id,
            optional($order->customer)->name,
            optional($order->customer)->code,
            optional($order->customer)->is_vip ? 'YES' : '',
                        number_format((float)($order->total_cost ?? 0),2,'.',''),
                        number_format($paid,2,'.',''),
                        number_format($due,2,'.',''),
                        optional($order->created_at)->toDateTimeString(),
                    ]);
                }
                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv']);
        }

        if (($validated['export'] ?? null) === 'xlsx') {
            $rows = [];
            foreach ((clone $query)->get() as $order) {
                    $completedPaid = (float)$order->payments()->where('status','completed')->sum('amount');
                    $refundedAmt = (float)$order->payments()->where('status','refunded')->sum('amount');
                    $paid = max(0.0, $completedPaid - $refundedAmt);
                    $due = max(0, (float)($order->total_cost ?? 0) - $paid);
                $rows[] = [
                    $order->order_id,
                    optional($order->customer)->name,
                    optional($order->customer)->code,
                    optional($order->customer)->is_vip ? 'YES' : '',
                    number_format((float)($order->total_cost ?? 0),2,'.',''),
                    number_format($paid,2,'.',''),
                    number_format($due,2,'.',''),
                    optional($order->created_at)->toDateTimeString(),
                ];
            }
            return \App\Services\ExcelExportService::streamSimpleXlsx(
                'invoices_'.now()->format('Ymd_His').'.xlsx',
                ['Order ID','Customer','Customer Code','VIP','Total','Paid','Due','Created'],
                $rows
            );
        }

        if (($validated['export'] ?? null) === 'pdf') {
            $rows = [];
            foreach ((clone $query)->get() as $order) {
                    $completedPaid = (float)$order->payments()->where('status','completed')->sum('amount');
                    $refundedAmt = (float)$order->payments()->where('status','refunded')->sum('amount');
                    $paid = max(0.0, $completedPaid - $refundedAmt);
                    $due = max(0, (float)($order->total_cost ?? 0) - $paid);
                $rows[] = [
                    'Order ID' => $order->order_id,
                    'Customer' => optional($order->customer)->name,
                    'Customer Code' => optional($order->customer)->code,
                    'VIP' => optional($order->customer)->is_vip ? 'YES' : '',
                    'Total' => number_format((float)($order->total_cost ?? 0),2,'.',''),
                    'Paid' => number_format($paid,2,'.',''),
                    'Due' => number_format($due,2,'.',''),
                    'Created' => optional($order->created_at)->toDateTimeString(),
                ];
            }
            return \App\Services\PdfExportService::streamSimpleTable(
                'invoices_'.now()->format('Ymd_His').'.pdf',
                'Invoices',
                ['Order ID','Customer','Customer Code','VIP','Total','Paid','Due','Created'],
                $rows
            );
        }

        if ($request->boolean('print')) {
                abort_unless(\Illuminate\Support\Facades\Gate::allows('print_invoices'), 403);
            $rows = [];
            foreach ((clone $query)->get() as $order) {
                    $completedPaid = (float)$order->payments()->where('status','completed')->sum('amount');
                    $refundedAmt = (float)$order->payments()->where('status','refunded')->sum('amount');
                    $paid = max(0.0, $completedPaid - $refundedAmt);
                    $due = max(0, (float)($order->total_cost ?? 0) - $paid);
                $rows[] = [
                    'Order ID' => $order->order_id,
                    'Customer' => optional($order->customer)->name,
                    'Customer Code' => optional($order->customer)->code,
                    'VIP' => optional($order->customer)->is_vip ? 'YES' : '',
                    'Total' => number_format((float)($order->total_cost ?? 0),2,'.',''),
                    'Paid' => number_format($paid,2,'.',''),
                    'Due' => number_format($due,2,'.',''),
                    'Created' => optional($order->created_at)->toDateTimeString(),
                ];
            }
            return view('exports.simple_table', [
                'title' => 'Invoices',
                'columns' => ['Order ID','Customer','Customer Code','VIP','Total','Paid','Due','Created'],
                'rows' => $rows,
            ]);
        }

        $orders = $query->paginate($perPage)->appends($request->query());
        foreach ($orders as $order) {
            $vatRate = (float)($order->vat_percentage ?? 0);

            // Subtotal (total without VAT)
            $subtotal = $order->total_cost / (1 + ($vatRate / 100));

            // VAT amount
            $vatAmount = $order->total_cost - $subtotal;

            // Attach to the model instance (available in Blade)
            $order->subtotal = round($subtotal, 2);
            $order->vat_amount = round($vatAmount, 2);
            $order->grand_total = round($order->total_cost, 2);
            
            // ✅ Convert appointment & pickup date if date_type = EC
            if ($order->date_type === 'EC') {
                $order->appointment_date_display = EthiopianCalendar::toEthiopian($order->appointment_date);
                $order->pickup_date_display = EthiopianCalendar::toEthiopian($order->pickup_date);
            } else {
                $order->appointment_date_display = optional($order->appointment_date)->toDateTimeString();
                $order->pickup_date_display = optional($order->pickup_date)->toDateTimeString();
            }
        }
        return view('invoices.index', compact('orders'));
    }
}
