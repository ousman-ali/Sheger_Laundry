<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Customer;
use App\Models\InventoryStock;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportingService
{
    public function getDashboardStats(): array
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();

        return [
            'today_orders' => Order::whereDate('created_at', $today)->count(),
            'today_revenue' => Order::whereDate('created_at', $today)->sum('total_cost'),
            'pending_orders' => Order::whereIn('status', ['received', 'processing', 'washing', 'drying_steaming', 'ironing', 'packaging'])->count(),
            'ready_for_pickup' => Order::where('status', 'ready_for_pickup')->count(),
            'monthly_revenue' => Order::whereBetween('created_at', [$thisMonth, Carbon::now()])->sum('total_cost'),
            'monthly_orders' => Order::whereBetween('created_at', [$thisMonth, Carbon::now()])->count(),
            'low_stock_items' => $this->getLowStockItems()->count(),
        ];
    }

    /** Service status distribution across all services. */
    public function getServiceStatusCounts(): array
    {
        $rows = \App\Models\OrderItemService::select('status', DB::raw('COUNT(*) as c'))
            ->groupBy('status')
            ->get();
        $out = [];
        foreach ($rows as $r) { $out[(string)$r->status] = (int)$r->c; }
        return $out;
    }

    /** Operator productivity: top operators by completed assignment quantity in window. */
    public function getOperatorProductivity(int $days = 7, int $limit = 5)
    {
        $end = Carbon::now();
        $start = (clone $end)->subDays($days);
        return DB::table('order_service_assignments as a')
            ->join('users as u', 'u.id', '=', 'a.employee_id')
            ->select('u.id as user_id', 'u.name', DB::raw('SUM(a.quantity) as qty'))
            ->where('a.status', 'completed')
            ->whereBetween('a.updated_at', [$start, $end])
            ->groupBy('u.id', 'u.name')
            ->orderByDesc('qty')
            ->limit($limit)
            ->get();
    }

    /** Urgency mix: count services by urgency tier label. */
    public function getUrgencyMix(): array
    {
        $rows = DB::table('order_item_services as ois')
            ->leftJoin('urgency_tiers as ut', 'ut.id', '=', 'ois.urgency_tier_id')
            ->select(DB::raw("COALESCE(ut.label,'Standard') as label"), DB::raw('COUNT(ois.id) as c'))
            ->groupBy('label')
            ->orderByDesc('c')
            ->get();
        $out = [];
        foreach ($rows as $r) { $out[$r->label] = (int)$r->c; }
        return $out;
    }

    /** Pending approvals summary for payments and penalties. */
    public function getPendingApprovals(): array
    {
        $payment = \App\Models\Payment::where('requires_approval', true)->where('status','pending');
        $pendingPaymentsCount = (int) $payment->count();
        $pendingPaymentsSum = (float) $payment->sum('amount');

        $penalties = \App\Models\OrderItemPenalty::where('requires_approval', true)->whereNull('approved_at');
        $pendingPenaltiesCount = (int) $penalties->count();
        $pendingPenaltiesSum = (float) $penalties->sum('amount');

        return [
            'payments' => ['count' => $pendingPaymentsCount, 'sum' => $pendingPaymentsSum],
            'penalties' => ['count' => $pendingPenaltiesCount, 'sum' => $pendingPenaltiesSum],
        ];
    }

    /** Payment method breakdown over recent window. */
    public function getPaymentMethodBreakdown(int $days = 30): array
    {
        $end = Carbon::now();
        $start = (clone $end)->subDays($days);
        $rows = \App\Models\Payment::select('method', DB::raw('SUM(amount) as amount'))
            ->where('status','completed')
            ->whereBetween('paid_at', [$start, $end])
            ->groupBy('method')
            ->orderByDesc('amount')
            ->get();
        $out = [];
        foreach ($rows as $r) { $out[(string)($r->method ?? 'unknown')] = (float)$r->amount; }
        return $out;
    }

    /** Refunds in recent window. */
    public function getRefundsSummary(int $days = 30): array
    {
        $end = Carbon::now();
        $start = (clone $end)->subDays($days);
        $q = \App\Models\Payment::where('status','refunded')->whereBetween('updated_at', [$start, $end]);
        return [ 'count' => (int)$q->count(), 'sum' => (float)$q->sum('amount') ];
    }

    /** Inventory usage trend by day. */
    public function getInventoryUsageTrend(int $days = 14): array
    {
        $end = Carbon::today();
        $start = (clone $end)->subDays($days - 1);
        $rows = \App\Models\StockUsage::select(DB::raw('DATE(usage_date) as d'), DB::raw('SUM(canonical_quantity) as used'))
            ->whereBetween('usage_date', [$start->startOfDay(), $end->endOfDay()])
            ->groupBy(DB::raw('DATE(usage_date)'))
            ->orderBy('d')
            ->get()->keyBy('d');
        $labels = [];$values=[]; $cursor=(clone $start);
        while ($cursor->lte($end)) { $k=$cursor->toDateString(); $labels[]=$k; $values[]=(float)($rows[$k]->used ?? 0); $cursor->addDay(); }
        return ['labels'=>$labels,'values'=>$values];
    }

    /** Stock-out requests by status. */
    public function getStockOutRequestsByStatus(): array
    {
        $rows = \App\Models\StockOutRequest::select('status', DB::raw('COUNT(*) as c'))
            ->groupBy('status')->get();
        $out = [];
        foreach ($rows as $r) { $out[(string)$r->status] = (int)$r->c; }
        return $out;
    }

    /** Purchases in current month (sum). */
    public function getPurchasesThisMonth(): float
    {
        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now();
        return (float) \App\Models\Purchase::whereBetween('purchase_date', [$start, $end])->sum('total_price');
    }

    /** Aging buckets for receivables (ledger-based only). */
    public function getReceivablesAgingBuckets(): array
    {
        // If ledgers exist, compute buckets by order age
        $hasLedgers = \App\Models\PaymentLedger::query()->exists();
        if (!$hasLedgers) { return ['0-7'=>0,'8-14'=>0,'15-30'=>0,'30+'=>0]; }
        $rows = DB::table('order_payment_ledgers as l')
            ->join('orders as o', 'o.id', '=', 'l.order_id')
            ->select(
                DB::raw("CASE 
                    WHEN DATEDIFF(CURDATE(), o.created_at) <= 7 THEN '0-7'
                    WHEN DATEDIFF(CURDATE(), o.created_at) <= 14 THEN '8-14'
                    WHEN DATEDIFF(CURDATE(), o.created_at) <= 30 THEN '15-30'
                    ELSE '30+'
                END as bucket"),
                DB::raw('SUM(GREATEST(l.total_amount - l.amount_received,0)) as due')
            )
            ->whereRaw('(l.total_amount - l.amount_received) > 0')
            ->groupBy('bucket')
            ->get();
        $out = ['0-7'=>0.0,'8-14'=>0.0,'15-30'=>0.0,'30+'=>0.0];
        foreach ($rows as $r) { $out[$r->bucket] = (float)$r->due; }
        return $out;
    }

    /** Customer KPIs: total, new in last 30d, returning in last 30d. */
    public function getCustomerStats(): array
    {
        $total = (int) Customer::count();
        $since = Carbon::now()->subDays(30);
        $newIds = Customer::where('created_at', '>=', $since)->pluck('id')->all();
        $new = count($newIds);
        // Returning in last 30d = customers with an order in last 30d AND a prior order before that window
        $recentOrderCustomerIds = Order::where('created_at', '>=', $since)->distinct()->pluck('customer_id');
        $returning = (int) Order::whereIn('customer_id', $recentOrderCustomerIds)
            ->where('created_at', '<', $since)
            ->distinct('customer_id')
            ->count('customer_id');
        return ['total' => $total, 'new_30d' => $new, 'returning_30d' => $returning];
    }

    /** Top customers by revenue within the last N days. */
    public function getTopCustomersByRevenue(int $days = 30, int $limit = 5)
    {
        $since = Carbon::now()->subDays($days);
        return DB::table('orders as o')
            ->join('customers as c', 'c.id', '=', 'o.customer_id')
            ->select('c.id as customer_id','c.name', DB::raw('SUM(o.total_cost) as revenue'), DB::raw('COUNT(o.id) as orders'))
            ->where('o.status', '!=', 'cancelled')
            ->where('o.created_at', '>=', $since)
            ->groupBy('c.id','c.name')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get();
    }

    /** User role counts (Spatie). */
    public function getUserRoleCounts(): array
    {
        $rows = DB::table('roles as r')
            ->leftJoin('model_has_roles as m', function($j){
                $j->on('m.role_id','=','r.id')->where('m.model_type','=', 'App\\Models\\User');
            })
            ->select('r.name', DB::raw('COUNT(m.model_id) as c'))
            ->groupBy('r.name')
            ->orderBy('r.name')
            ->get();
        $out = [];
        foreach ($rows as $r) { $out[$r->name] = (int)$r->c; }
        return $out;
    }
    /**
     * Daily revenue trend for the last N days (inclusive of today).
     * Returns ['labels'=>[Y-m-d...], 'values'=>[float...]]
     */
    public function getRevenueTrend(int $days = 14): array
    {
        $end = Carbon::today();
        $start = (clone $end)->subDays($days - 1);

        $rows = Order::select(
                DB::raw('DATE(created_at) as d'),
                DB::raw('SUM(total_cost) as revenue')
            )
            ->whereBetween('created_at', [$start->startOfDay(), $end->endOfDay()])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('d')
            ->get()
            ->keyBy('d');

        $labels = [];
        $values = [];
        $cursor = (clone $start);
        while ($cursor->lte($end)) {
            $key = $cursor->toDateString();
            $labels[] = $key;
            $values[] = (float)($rows[$key]->revenue ?? 0);
            $cursor->addDay();
        }
        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Orders distribution by status (current counts)
     */
    public function getOrdersByStatus(): array
    {
        $rows = Order::select('status', DB::raw('COUNT(*) as c'))
            ->groupBy('status')
            ->get();
        $result = [];
        foreach ($rows as $r) {
            $result[(string)$r->status] = (int)$r->c;
        }
        return $result;
    }

    /**
     * Sum of completed payments today.
     */
    public function getPaymentsToday(): float
    {
        $today = Carbon::today();
        return (float) \App\Models\Payment::where('status', 'completed')
            ->whereBetween('paid_at', [$today->startOfDay(), $today->endOfDay()])
            ->sum('amount');
    }

    /**
     * Total receivables outstanding across orders (ledger-based if available).
     */
    public function getReceivablesOutstanding(): float
    {
        // Prefer ledgers when present; fallback to orders/payments otherwise
        $fromLedgers = \App\Models\PaymentLedger::query()
            ->where('status', '!=', 'paid')
            ->select(DB::raw('SUM(GREATEST(total_amount - amount_received, 0)) as due'))
            ->value('due');
        if ($fromLedgers !== null) {
            return (float)$fromLedgers;
        }
        // Fallback: compute from orders and completed/refunded payments
        $total = (float) Order::sum('total_cost');
        $completed = (float) \App\Models\Payment::where('status', 'completed')->sum('amount');
        $refunded = (float) \App\Models\Payment::where('status', 'refunded')->sum('amount');
        $received = max(0.0, $completed - $refunded);
        return max(0.0, $total - $received);
    }

    /**
     * Top services by usage and revenue.
     * Returns collection with fields: service_id, name, usages, revenue
     */
    public function getTopServices(int $limit = 5)
    {
        return DB::table('order_item_services as ois')
            ->join('services as s', 's.id', '=', 'ois.service_id')
            ->select(
                's.id as service_id', 's.name',
                DB::raw('COUNT(ois.id) as usages'),
                DB::raw('SUM(COALESCE(ois.quantity,1) * COALESCE(ois.price_applied,0)) as revenue')
            )
            ->groupBy('s.id', 's.name')
            ->orderByDesc('usages')
            ->limit($limit)
            ->get();
    }

    /**
     * Upcoming pickups within the next N days.
     */
    public function getUpcomingPickups(int $days = 7)
    {
        $now = Carbon::now();
        $until = (clone $now)->addDays($days);
        return Order::with('customer')
            ->whereNotIn('status', ['cancelled'])
            ->whereNotNull('pickup_date')
            ->whereBetween('pickup_date', [$now, $until])
            ->orderBy('pickup_date')
            ->limit(6)
            ->get();
    }

  public function getTopCustomers(int $limit = 5): array
    {
        return Customer::select(
            'customers.id',
            'customers.code',
            'customers.name',
            'customers.phone',
            'customers.address',
            'customers.is_vip',
            'customers.created_at',
            'customers.updated_at',
            DB::raw('COUNT(orders.id) as total_orders'),
            DB::raw('SUM(orders.total_cost) as total_revenue')
        )
        ->leftJoin('orders', 'customers.id', '=', 'orders.customer_id')
        ->where('orders.status', '!=', 'cancelled')
        ->groupBy(
            'customers.id',
            'customers.code',
            'customers.name',
            'customers.phone',
            'customers.address',
            'customers.is_vip',
            'customers.created_at',
            'customers.updated_at'
        )
        ->orderBy('total_revenue', 'desc')
        ->limit($limit)
        ->get()
        ->toArray();
    }

    public function getLowStockItems()
    {
        $threshold = config('shebar.low_stock_threshold', 10);

        return InventoryStock::select(
            'inventory_items.name',
            'inventory_items.minimum_stock',
            'inventory_stock.quantity',
            'stores.name as store_name'
        )
        ->join('inventory_items', 'inventory_stock.inventory_item_id', '=', 'inventory_items.id')
        ->join('stores', 'inventory_stock.store_id', '=', 'stores.id')
        ->where('inventory_stock.quantity', '<=', $threshold)
        ->get();
    }
} 
