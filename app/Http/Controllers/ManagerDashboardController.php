<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItemService;
use App\Models\User;
use App\Services\ReportingService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ManagerDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:Manager');
    }

    public function index(Request $request, ReportingService $reporting)
    {
        $stats = $reporting->getDashboardStats();

        $start = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $end = $request->get('end_date', Carbon::now()->toDateString());

        // Basic KPIs for managers
        $revenue = Order::whereBetween('created_at', [$start, $end])
            ->whereNotIn('status', ['cancelled'])
            ->sum('total_cost');
        $ordersCount = Order::whereBetween('created_at', [$start, $end])->count();
        $avgOrder = $ordersCount ? round($revenue / $ordersCount, 2) : 0;

        // Operator productivity (completed services)
        $operatorPerf = OrderItemService::select('employee_id')
            ->selectRaw('COUNT(*) as total_services')
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_services")
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('employee_id')
            ->with('employee')
            ->orderByDesc('completed_services')
            ->limit(10)
            ->get();

        // Services mix
        $serviceMix = OrderItemService::select('service_id')
            ->selectRaw('COUNT(*) as count')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('service_id')
            ->with('service')
            ->orderByDesc('count')
            ->get();

        // Aging: orders pending by status
        $pendingByStatus = Order::select('status')
            ->selectRaw('COUNT(*) as count')
            ->whereIn('status', ['received','processing','washing','drying_steaming','ironing','packaging'])
            ->groupBy('status')
            ->orderBy('status')
            ->get();

        // Low performing operators (assigned but not completed)
        $inProgress = OrderItemService::select('employee_id')
            ->selectRaw("SUM(CASE WHEN status IN ('assigned','in_progress') THEN 1 ELSE 0 END) as backlog")
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('employee_id')
            ->with('employee')
            ->orderByDesc('backlog')
            ->limit(10)
            ->get();

        return view('manager.index', compact(
            'stats', 'start', 'end', 'revenue', 'ordersCount', 'avgOrder', 'operatorPerf', 'serviceMix', 'pendingByStatus', 'inProgress'
        ));
    }
}
