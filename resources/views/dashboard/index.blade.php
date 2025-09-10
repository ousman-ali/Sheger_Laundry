
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ \App\Models\SystemSetting::getValue('company_name', config('app.name', 'Sheger Automatic Laundry')) . ' Dashboard' }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 flex items-center">
                        <svg class="h-8 w-8 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Today's Orders</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ $stats['today_orders'] ?? 0 }}</p>
                            <a href="{{ route('orders.index', ['from_date' => now()->toDateString(), 'to_date' => now()->toDateString()]) }}" class="text-xs text-blue-600 hover:underline">View today's</a>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 flex items-center">
                        <svg class="h-8 w-8 text-yellow-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Pending Orders</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ $stats['pending_orders'] ?? 0 }}</p>
                            <a href="{{ route('orders.index') }}" class="text-xs text-blue-600 hover:underline">Open list</a>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 flex items-center">
                        <svg class="h-8 w-8 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Ready for Pickup</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ $stats['ready_for_pickup'] ?? 0 }}</p>
                            <a href="{{ route('orders.index', ['status' => 'ready_for_pickup']) }}" class="text-xs text-blue-600 hover:underline">Open list</a>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 flex items-center">
                        <svg class="h-8 w-8 text-purple-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                        </svg>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Today's Orders Value</p>
                            <p class="text-2xl font-semibold text-gray-900">ETB {{ number_format($stats['today_revenue'] ?? 0, 2) }}</p>
                            <a href="{{ route('orders.index', ['from_date' => now()->toDateString(), 'to_date' => now()->toDateString()]) }}" class="text-xs text-blue-600 hover:underline">View today's orders</a>
                        </div>
                    </div>
                </div>
                <!-- Payments Today (Receipts) -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 flex items-center">
                        <svg class="h-8 w-8 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-2m3-5h-6m0 0l2-2m-2 2l2 2" />
                        </svg>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Payments Today (Receipts)</p>
                            <p class="text-2xl font-semibold text-gray-900">ETB {{ number_format($paymentsToday ?? 0, 2) }}</p>
                            <a href="{{ route('payments.index', ['status' => 'completed', 'from_date' => now()->toDateString(), 'to_date' => now()->toDateString()]) }}" class="text-xs text-blue-600 hover:underline">View payments</a>
                        </div>
                    </div>
                </div>
                <!-- Receivables Outstanding -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 flex items-center">
                        <svg class="h-8 w-8 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-2.21 0-4 1.343-4 3s1.79 3 4 3 4 1.343 4 3m-4-9V6m0 12v0m0 0v0" />
                        </svg>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Receivables</p>
                            <p class="text-2xl font-semibold text-gray-900">ETB {{ number_format($receivables ?? 0, 2) }}</p>
                            <a href="{{ route('ledgers.index') }}" class="text-xs text-blue-600 hover:underline">Open ledgers</a>
                        </div>
                    </div>
                </div>

                <!-- Purchases (This Month) -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 flex items-center">
                        <svg class="h-8 w-8 text-indigo-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18" />
                        </svg>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Purchases (This Month)</p>
                            <p class="text-2xl font-semibold text-gray-900">ETB {{ number_format($purchasesThisMonth ?? 0, 2) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Refunds (30d) -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 flex items-center">
                        <svg class="h-8 w-8 text-orange-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v6h6M20 20v-6h-6M4 10a8 8 0 0013.657 5.657L20 18M10 4a8 8 0 0113.657 5.657L20 6" />
                        </svg>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Refunds (30d)</p>
                            <p class="text-2xl font-semibold text-gray-900">ETB {{ number_format(($refundsSummary['sum'] ?? 0), 2) }} <span class="text-sm text-gray-500">({{ $refundsSummary['count'] ?? 0 }})</span></p>
                        </div>
                    </div>
                </div>

                <!-- Pending Approvals -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 flex items-center">
                        <svg class="h-8 w-8 text-yellow-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3" />
                        </svg>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Pending Approvals</p>
                            <p class="text-sm text-gray-600">Payments: {{ $pendingApprovals['payments']['count'] ?? 0 }} (ETB {{ number_format($pendingApprovals['payments']['sum'] ?? 0, 2) }})</p>
                            <p class="text-sm text-gray-600">Penalties: {{ $pendingApprovals['penalties']['count'] ?? 0 }} (ETB {{ number_format($pendingApprovals['penalties']['sum'] ?? 0, 2) }})</p>
                        </div>
                    </div>
                </div>

                <!-- Services In Progress -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 flex items-center">
                        <svg class="h-8 w-8 text-sky-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3" />
                        </svg>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Services In Progress</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ $serviceStatusCounts['in_progress'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monthly Revenue -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Monthly Revenue</h3>
                    <p class="text-3xl font-bold text-green-600">ETB {{ number_format($stats['monthly_revenue'] ?? 0, 2) }}</p>
                    <a href="{{ route('reports.index') }}" class="text-sm text-blue-600 hover:underline">Open reports</a>
                </div>
            </div>

            <!-- Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Revenue (Last 14 days)</h3>
                        <canvas id="revenueTrendChart" height="160"></canvas>
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Orders by Status</h3>
                        <canvas id="ordersByStatusChart" height="160"></canvas>
                    </div>
                </div>
            </div>

            <!-- Operational Insights -->
            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-8 mb-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Service Status</h3>
                        <canvas id="serviceStatusChart" height="160"></canvas>
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Payment Methods (30d)</h3>
                        <canvas id="payMethodChart" height="160"></canvas>
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Inventory Usage (14d)</h3>
                        <canvas id="invUsageChart" height="160"></canvas>
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Stock-out Requests</h3>
                            <a href="{{ route('stock-out-requests.index') }}" class="text-sm text-blue-600 hover:underline">Open</a>
                        </div>
                        <canvas id="stockOutStatusChart" height="160"></canvas>
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Receivables Aging</h3>
                        <canvas id="receivablesAgingChart" height="160"></canvas>
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Urgency Mix</h3>
                        <canvas id="urgencyMixChart" height="160"></canvas>
                    </div>
                </div>
            </div>

            <!-- Productivity -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Operator Productivity (7d)</h3>
                        <canvas id="operatorProductivityChart" height="200"></canvas>
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-lg font-medium text-gray-900">Customer Analysis</h3>
                            <a href="{{ route('customers.index') }}" class="text-sm text-blue-600 hover:underline">View customers</a>
                        </div>
                        <div class="grid grid-cols-3 gap-4 mb-4">
                            <div>
                                <p class="text-sm text-gray-500">Total Customers</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ $customerStats['total'] ?? 0 }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">New (30d)</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ $customerStats['new_30d'] ?? 0 }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Returning (30d)</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ $customerStats['returning_30d'] ?? 0 }}</p>
                            </div>
                        </div>
                        <h4 class="text-md font-medium text-gray-900 mb-2">Top Customers (30d)</h4>
                        @if(($topCustomers30d ?? null) && count($topCustomers30d))
                            <ul class="divide-y divide-gray-100">
                                @foreach($topCustomers30d as $tc)
                                    <li class="flex items-center justify-between py-2">
                                        <a href="{{ route('customers.show', $tc->customer_id ?? $tc->id ?? null) }}" class="text-blue-600 hover:underline">{{ $tc->name }}</a>
                                        <span class="text-gray-700">ETB {{ number_format($tc->revenue, 2) }} <span class="text-xs text-gray-500">({{ $tc->orders }} orders)</span></span>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-gray-500">No customer data</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Team Overview -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Users by Role</h3>
                        <canvas id="usersByRoleChart" height="180"></canvas>
                    </div>
                </div>
            </div>

            <!-- Recent Orders & Low Stock -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <!-- Recent Orders -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Orders</h3>
                        @if($recentOrders->count())
                            <div class="space-y-4">
                                @foreach($recentOrders as $order)
                                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                        <div>
                                            <a href="{{ route('orders.show', $order) }}" class="font-medium text-blue-600 hover:underline">{{ $order->order_id }}</a>
                                            <p class="text-sm text-gray-500">{{ $order->customer->name }}</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-medium text-gray-900">ETB {{ number_format($order->total_cost, 2) }}</p>
                                            <p class="text-sm text-gray-500 capitalize">{{ str_replace('_', ' ', $order->status) }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-500">No recent orders</p>
                        @endif
                    </div>
                </div>

                <!-- Low Stock Alerts -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Low Stock Alerts</h3>
                        @if($lowStockItems->count())
                            <div class="space-y-4">
                                @foreach($lowStockItems as $item)
                                    <div class="flex items-center justify-between p-4 bg-red-50 rounded-lg">
                                        <div>
                                            <p class="font-medium text-red-900">{{ $item->name }}</p>
                                            <p class="text-sm text-red-500">{{ $item->store_name }}</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-medium text-red-900">{{ $item->quantity }}</p>
                                            <p class="text-sm text-red-500">Min: {{ $item->minimum_stock }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-500">No low stock items</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Top Services and Upcoming Pickups -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Top Services</h3>
                            <a href="{{ route('services.index') }}" class="text-sm text-blue-600 hover:underline">Manage</a>
                        </div>
                        @if(($topServices ?? null) && count($topServices))
                            <ul class="divide-y divide-gray-100">
                                @foreach($topServices as $svc)
                                    <li class="flex items-center justify-between py-3">
                                        <div>
                                            <a href="{{ route('services.index', ['q' => $svc->name]) }}" class="font-medium text-blue-600 hover:underline">{{ $svc->name }}</a>
                                            <p class="text-sm text-gray-500">Usages: {{ number_format($svc->usages) }}</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-medium text-gray-900">ETB {{ number_format($svc->revenue, 2) }}</p>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-gray-500">No service data</p>
                        @endif
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Upcoming Pickups (7 days)</h3>
                            <a href="{{ route('orders.index') }}" class="text-sm text-blue-600 hover:underline">Open orders</a>
                        </div>
                        @if(($upcomingPickups ?? null) && $upcomingPickups->count())
                            <ul class="divide-y divide-gray-100">
                                @foreach($upcomingPickups as $o)
                                    <li class="flex items-center justify-between py-3">
                                        <div>
                                            <a href="{{ route('orders.show', $o) }}" class="font-medium text-blue-600 hover:underline">{{ $o->order_id }}</a>
                                            <p class="text-sm text-gray-500">{{ optional($o->customer)->name }}</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-medium text-gray-900">{{ optional($o->pickup_date)->format('M d, Y H:i') }}</p>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-gray-500">No scheduled pickups</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Notifications -->
            @if($notifications->count())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Notifications</h3>
                        <div class="space-y-4">
                            @foreach($notifications as $notification)
                                <div class="flex items-center justify-between p-4 bg-blue-50 rounded-lg">
                                    <div>
                                        <p class="font-medium text-blue-900">{{ ucfirst(str_replace('_', ' ', $notification->type)) }}</p>
                                        <p class="text-sm text-blue-500">{{ $notification->message }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm text-blue-500">{{ $notification->created_at->diffForHumans() }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>
<script>
    document.addEventListener('DOMContentLoaded', function(){
        if (!window.Chart) return;
        // Revenue Trend
        const revEl = document.getElementById('revenueTrendChart');
        if (revEl) {
            const revData = @json($revenueTrend ?? ['labels'=>[], 'values'=>[]]);
            new Chart(revEl.getContext('2d'), {
                type: 'line',
                data: {
                    labels: revData.labels,
                    datasets: [{
                        label: 'Revenue (ETB)',
                        data: revData.values,
                        borderColor: 'rgb(34,197,94)',
                        backgroundColor: 'rgba(34,197,94,0.15)',
                        tension: 0.25,
                        fill: true,
                    }]
                },
                options: { scales: { y: { beginAtZero: true } }, plugins: { legend: { display: true } } }
            });
        }

        // Orders by Status
        const statusEl = document.getElementById('ordersByStatusChart');
        if (statusEl) {
            const statusMap = @json($ordersByStatus ?? []);
            const labels = Object.keys(statusMap).map(k => k.replaceAll('_',' '));
            const values = Object.values(statusMap);
            const ordersChart = new Chart(statusEl.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels,
                    datasets: [{
                        data: values,
                        backgroundColor: [
                            '#60a5fa','#f59e0b','#34d399','#ef4444','#a78bfa','#f472b6','#22d3ee','#f97316'
                        ]
                    }]
                },
                options: { plugins: { legend: { position: 'bottom' } } }
            });
            statusEl.onclick = (evt) => {
                const points = ordersChart.getElementsAtEventForMode(evt, 'nearest', { intersect: true }, true);
                if (!points.length) return;
                const idx = points[0].index;
                const raw = Object.keys(statusMap)[idx];
                if (raw) {
                    const url = new URL("{{ route('orders.index') }}", window.location.origin);
                    url.searchParams.set('status', raw);
                    window.location.href = url.toString();
                }
            };
        }

        // Service Status
        const svcEl = document.getElementById('serviceStatusChart');
        if (svcEl) {
            const map = @json($serviceStatusCounts ?? []);
            const labels = Object.keys(map).map(k => k.replaceAll('_',' '));
            const values = Object.values(map);
            new Chart(svcEl.getContext('2d'), {
                type: 'bar',
                data: { labels, datasets: [{ label: 'Services', data: values, backgroundColor: '#93c5fd' }]},
                options: { scales: { y: { beginAtZero: true } }, plugins: { legend: { display: false } } }
            });
        }

        // Payment Methods
        const pmEl = document.getElementById('payMethodChart');
        if (pmEl) {
            const map = @json($paymentMethodBreakdown ?? []);
            const labels = Object.keys(map);
            const values = Object.values(map);
            const payChart = new Chart(pmEl.getContext('2d'), {
                type: 'doughnut',
                data: { labels, datasets: [{ data: values, backgroundColor: ['#4ade80','#60a5fa','#f59e0b','#ef4444','#a78bfa'] }]},
                options: { plugins: { legend: { position: 'bottom' } } }
            });
            pmEl.onclick = (evt) => {
                const pts = payChart.getElementsAtEventForMode(evt, 'nearest', { intersect: true }, true);
                if (!pts.length) return;
                const idx = pts[0].index;
                const method = labels[idx];
                const url = new URL("{{ route('payments.index') }}", window.location.origin);
                url.searchParams.set('status', 'completed');
                url.searchParams.set('from_date', '{{ now()->subDays(30)->toDateString() }}');
                url.searchParams.set('to_date', '{{ now()->toDateString() }}');
                if (method) url.searchParams.set('method', method);
                window.location.href = url.toString();
            };
        }

        // Inventory Usage Trend
        const invEl = document.getElementById('invUsageChart');
        if (invEl) {
            const data = @json($inventoryUsageTrend ?? ['labels'=>[], 'values'=>[]]);
            new Chart(invEl.getContext('2d'), {
                type: 'line',
                data: { labels: data.labels, datasets: [{ label: 'Used (canonical)', data: data.values, borderColor: '#0ea5e9', backgroundColor: 'rgba(14,165,233,0.15)', tension: 0.25, fill: true }]},
                options: { scales: { y: { beginAtZero: true } } }
            });
        }

        // Stock-out by Status
        const soEl = document.getElementById('stockOutStatusChart');
        if (soEl) {
            const map = @json($stockOutByStatus ?? []);
            const labels = Object.keys(map).map(k=>k.replaceAll('_',' '));
            const values = Object.values(map);
            const soChart = new Chart(soEl.getContext('2d'), {
                type: 'bar', data: { labels, datasets: [{ label: 'Requests', data: values, backgroundColor: '#fbbf24' }]},
                options: { scales: { y: { beginAtZero: true } }, plugins: { legend: { display: false } } }
            });
            soEl.onclick = (evt) => {
                const pts = soChart.getElementsAtEventForMode(evt, 'nearest', { intersect: true }, true);
                if (!pts.length) return;
                const idx = pts[0].index;
                const raw = Object.keys(map)[idx];
                if (raw) {
                    const url = new URL("{{ route('stock-out-requests.index') }}", window.location.origin);
                    url.searchParams.set('status', raw);
                    window.location.href = url.toString();
                }
            };
        }

        // Receivables Aging
        const raEl = document.getElementById('receivablesAgingChart');
        if (raEl) {
            const map = @json($receivablesAging ?? []);
            const labels = Object.keys(map);
            const values = Object.values(map);
            new Chart(raEl.getContext('2d'), {
                type: 'bar', data: { labels, datasets: [{ label: 'ETB', data: values, backgroundColor: '#f87171' }]},
                options: { scales: { y: { beginAtZero: true } } }
            });
        }

        // Urgency Mix
        const umEl = document.getElementById('urgencyMixChart');
        if (umEl) {
            const map = @json($urgencyMix ?? []);
            const labels = Object.keys(map);
            const values = Object.values(map);
            new Chart(umEl.getContext('2d'), {
                type: 'pie', data: { labels, datasets: [{ data: values, backgroundColor: ['#34d399','#60a5fa','#f59e0b','#f472b6','#a78bfa'] }]},
                options: { plugins: { legend: { position: 'bottom' } } }
            });
        }

        // Operator Productivity
        const opEl = document.getElementById('operatorProductivityChart');
        if (opEl) {
            const rows = @json($operatorProductivity ?? []);
            const labels = rows.map(r => r.name);
            const values = rows.map(r => Number(r.qty));
            new Chart(opEl.getContext('2d'), {
                type: 'bar',
                data: { labels, datasets: [{ label: 'Qty Completed', data: values, backgroundColor: '#86efac' }]},
                options: { indexAxis: 'y', scales: { x: { beginAtZero: true } }, plugins: { legend: { display: false } } }
            });
        }

        // Users by Role
        const roleEl = document.getElementById('usersByRoleChart');
        if (roleEl) {
            const map = @json($userRoleCounts ?? []);
            const labels = Object.keys(map);
            const values = Object.values(map);
            new Chart(roleEl.getContext('2d'), {
                type: 'bar',
                data: { labels, datasets: [{ label: 'Users', data: values, backgroundColor: '#c4b5fd' }]},
                options: { scales: { y: { beginAtZero: true } }, plugins: { legend: { display: false } } }
            });
        }
    });
</script>