<?php

namespace App\Http\Controllers;

use App\Services\ReportingService;
use App\Models\Order;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected $reportingService;
    protected $notificationService;

    public function __construct(ReportingService $reportingService, NotificationService $notificationService)
    {
        $this->reportingService = $reportingService;
        $this->notificationService = $notificationService;
    }

    public function index()
    {
        $stats = $this->reportingService->getDashboardStats();
        // Recent orders for dashboard
        $recentOrders = Order::with('customer')->orderBy('created_at', 'desc')->take(5)->get();
        // Top customers (unused in view but available)
        $topCustomers = $this->reportingService->getTopCustomers(5);
        $lowStockItems = $this->reportingService->getLowStockItems();
        $notifications = $this->notificationService->getUnreadNotifications(Auth::id(), 5);
        $notificationStats = $this->notificationService->getNotificationStats(Auth::id());

        // Extended analytics for dashboard widgets
    $revenueTrend = $this->reportingService->getRevenueTrend(14);
    $ordersByStatus = $this->reportingService->getOrdersByStatus();
    $paymentsToday = $this->reportingService->getPaymentsToday();
    $receivables = $this->reportingService->getReceivablesOutstanding();
    $topServices = $this->reportingService->getTopServices(5);
    $upcomingPickups = $this->reportingService->getUpcomingPickups(7);
    $serviceStatusCounts = $this->reportingService->getServiceStatusCounts();
    $operatorProductivity = $this->reportingService->getOperatorProductivity(7, 5);
    $urgencyMix = $this->reportingService->getUrgencyMix();
    $pendingApprovals = $this->reportingService->getPendingApprovals();
    $paymentMethodBreakdown = $this->reportingService->getPaymentMethodBreakdown(30);
    $refundsSummary = $this->reportingService->getRefundsSummary(30);
    $inventoryUsageTrend = $this->reportingService->getInventoryUsageTrend(14);
    $stockOutByStatus = $this->reportingService->getStockOutRequestsByStatus();
    $purchasesThisMonth = $this->reportingService->getPurchasesThisMonth();
    $receivablesAging = $this->reportingService->getReceivablesAgingBuckets();
    $customerStats = $this->reportingService->getCustomerStats();
    $topCustomers30d = $this->reportingService->getTopCustomersByRevenue(30, 5);
    $userRoleCounts = $this->reportingService->getUserRoleCounts();

        return view('dashboard.index', compact(
            'stats', 'recentOrders', 'topCustomers', 'lowStockItems', 'notifications', 'notificationStats',
            'revenueTrend', 'ordersByStatus', 'paymentsToday', 'receivables', 'topServices', 'upcomingPickups',
            'serviceStatusCounts','operatorProductivity','urgencyMix','pendingApprovals','paymentMethodBreakdown',
            'refundsSummary','inventoryUsageTrend','stockOutByStatus','purchasesThisMonth','receivablesAging',
            'customerStats','topCustomers30d','userRoleCounts'
        ));
    }

    public function analytics(Request $request)
    {
        $period = $request->get('period', 'monthly');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $data = [
            'period' => $period,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];

        return view('dashboard.analytics', compact('data'));
    }

    // Reports moved to ReportsController
}