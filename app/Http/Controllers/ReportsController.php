<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItemService;
use App\Services\ExcelExportService;
use App\Services\PdfExportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ReportsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified', 'role:Admin']);
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'type' => 'nullable|in:revenue,orders,top_services,low_stock',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'export' => 'nullable|in:csv,xlsx,pdf',
            'print' => 'nullable|in:1,true',
        ]);

        $type = $validated['type'] ?? 'revenue';
        $start = Carbon::parse($validated['start_date'] ?? now()->subMonth()->format('Y-m-d'))->startOfDay();
        $end = Carbon::parse($validated['end_date'] ?? now()->format('Y-m-d'))->endOfDay();

        // Prepare data based on report type
        switch ($type) {
            case 'orders':
                $rows = $this->ordersByStatus($start, $end);
                $columns = ['Status', 'Orders', 'Total (ETB)'];
                $title = 'Orders by Status ('.$start->toDateString().' to '.$end->toDateString().')';
                break;
            case 'top_services':
                $rows = $this->topServices($start, $end, 20);
                $columns = ['Service', 'Qty', 'Revenue (ETB)'];
                $title = 'Top Services ('.$start->toDateString().' to '.$end->toDateString().')';
                break;
            case 'low_stock':
                $rows = $this->lowStock();
                $columns = ['Item', 'Store', 'Min Stock', 'Qty'];
                $title = 'Low Stock Items';
                break;
            case 'revenue':
            default:
                $rows = $this->revenueDaily($start, $end);
                $columns = ['Date', 'Orders', 'Revenue (ETB)', 'Paid (ETB)', 'Due (ETB)'];
                $title = 'Revenue (Daily) ('.$start->toDateString().' to '.$end->toDateString().')';
                break;
        }

        // Handle exports/print (server-side RBAC: Admin only, but require export/print permissions when defined)
        if (($validated['export'] ?? null) === 'csv') {
            $this->authorizeExport('reports');
            $filename = 'report_'.$type.'_'.$start->format('Ymd').'_to_'.$end->format('Ymd').'.csv';
            return response()->streamDownload(function () use ($columns, $rows) {
                $out = fopen('php://output', 'w');
                fputcsv($out, $columns);
                foreach ($rows as $r) {
                    fputcsv($out, $r);
                }
                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv']);
        }
        if (($validated['export'] ?? null) === 'xlsx') {
            $this->authorizeExport('reports');
            $filename = 'report_'.$type.'_'.$start->format('Ymd').'_to_'.$end->format('Ymd').'.xlsx';
            return ExcelExportService::streamSimpleXlsx($filename, $columns, $rows);
        }
        if (($validated['export'] ?? null) === 'pdf') {
            $this->authorizeExport('reports');
            $filename = 'report_'.$type.'_'.$start->format('Ymd').'_to_'.$end->format('Ymd').'.pdf';
            return PdfExportService::streamSimpleTable($filename, $title, $columns, $rows);
        }
        if (!empty($validated['print'])) {
            $this->authorizePrint('reports');
            return response()->view('reports.print', compact('columns', 'rows', 'title'));
        }

        return view('reports.index', [
            'type' => $type,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'columns' => $columns,
            'rows' => $rows,
            'title' => $title,
        ]);
    }

    private function authorizeExport(string $base): void
    {
        if (!\Illuminate\Support\Facades\Gate::allows('export_'.$base)) {
            abort(403);
        }
    }

    private function authorizePrint(string $base): void
    {
        if (!\Illuminate\Support\Facades\Gate::allows('print_'.$base)) {
            abort(403);
        }
    }

    private function revenueDaily(Carbon $start, Carbon $end): array
    {
        $orders = Order::with(['payments' => function ($q) {
            $q->select('id','order_id','status','amount');
        }])
            ->whereBetween('created_at', [$start, $end])
            ->where('status', '!=', 'cancelled')
            ->get();

        $byDate = [];
        foreach ($orders as $o) {
            $date = $o->created_at->toDateString();
            $completed = (float)$o->payments->where('status','completed')->sum('amount');
            $refunded = (float)$o->payments->where('status','refunded')->sum('amount');
            $paid = max(0.0, $completed - $refunded);
            if (!isset($byDate[$date])) {
                $byDate[$date] = ['orders' => 0, 'revenue' => 0.0, 'paid' => 0.0, 'due' => 0.0];
            }
            $byDate[$date]['orders'] += 1;
            $byDate[$date]['revenue'] += (float)$o->total_cost;
            $byDate[$date]['paid'] += $paid;
            $byDate[$date]['due'] += max(0.0, (float)$o->total_cost - $paid);
        }

        ksort($byDate);
        $rows = [];
        foreach ($byDate as $date => $agg) {
            $rows[] = [
                $date,
                (int)$agg['orders'],
                number_format($agg['revenue'], 2, '.', ''),
                number_format($agg['paid'], 2, '.', ''),
                number_format($agg['due'], 2, '.', ''),
            ];
        }
        return $rows;
    }

    private function ordersByStatus(Carbon $start, Carbon $end): array
    {
        $orders = Order::whereBetween('created_at', [$start, $end])
            ->where('status', '!=', 'cancelled')
            ->get(['id','status','total_cost']);

        $byStatus = [];
        foreach ($orders as $o) {
            $s = (string)$o->status;
            if (!isset($byStatus[$s])) {
                $byStatus[$s] = ['count' => 0, 'total' => 0.0];
            }
            $byStatus[$s]['count'] += 1;
            $byStatus[$s]['total'] += (float)$o->total_cost;
        }
        ksort($byStatus);
        $rows = [];
        foreach ($byStatus as $status => $agg) {
            $rows[] = [$status, (int)$agg['count'], number_format($agg['total'], 2, '.', '')];
        }
        return $rows;
    }

    private function topServices(Carbon $start, Carbon $end, int $limit = 20): array
    {
        $q = OrderItemService::query()
            ->selectRaw('services.name as service_name, SUM(order_item_services.quantity) as qty, SUM(order_item_services.quantity * order_item_services.price_applied) as revenue')
            ->join('order_items', 'order_item_services.order_item_id', '=', 'order_items.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('services', 'order_item_services.service_id', '=', 'services.id')
            ->whereBetween('orders.created_at', [$start, $end])
            ->where('orders.status', '!=', 'cancelled')
            ->groupBy('services.id', 'services.name')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get();

        $rows = [];
        foreach ($q as $row) {
            $rows[] = [
                $row->service_name,
                number_format((float)$row->qty, 2, '.', ''),
                number_format((float)$row->revenue, 2, '.', ''),
            ];
        }
        return $rows;
    }

    private function lowStock(): array
    {
        // Minimal duplication to avoid a hard dependency; we reuse ReportingService->getLowStockItems() format.
        $items = app(\App\Services\ReportingService::class)->getLowStockItems();
        $rows = [];
        foreach ($items as $i) {
            $rows[] = [
                (string)$i->name,
                (string)$i->store_name,
                (string)$i->minimum_stock,
                (string)$i->quantity,
            ];
        }
        return $rows;
    }
}
