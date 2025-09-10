<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Receptionist Dashboard</h2>
            @can('create_orders')
                <x-create-button :href="route('orders.create')" label="Create Order" />
            @endcan
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-3">Quick Actions</h3>
                <div class="flex gap-3 flex-wrap">
                    <a href="{{ route('orders.index') }}" class="px-4 py-2 bg-gray-100 text-gray-800 rounded">Orders</a>
                    <a href="{{ route('customers.index') }}" class="px-4 py-2 bg-gray-100 text-gray-800 rounded">Customers</a>
                </div>
            </div>

            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-3">Stats</h3>
                <div class="grid md:grid-cols-3 gap-4">
                    <div class="p-4 rounded border">
                        <div class="text-gray-500 text-sm">Open Orders</div>
                        <div class="text-2xl font-bold">{{ $stats['open_orders'] ?? '-' }}</div>
                    </div>
                    <div class="p-4 rounded border">
                        <div class="text-gray-500 text-sm">Ready for Pickup</div>
                        <div class="text-2xl font-bold">{{ $stats['ready_orders'] ?? '-' }}</div>
                    </div>
                    <div class="p-4 rounded border">
                        <div class="text-gray-500 text-sm">Customers</div>
                        <div class="text-2xl font-bold">{{ $stats['customers'] ?? '-' }}</div>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-3">Recent Orders</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-600">
                                <th class="py-2">#</th>
                                <th class="py-2">Customer</th>
                                <th class="py-2">Date</th>
                                <th class="py-2">Status</th>
                                <th class="py-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentOrders as $o)
                                <tr class="border-t">
                                    <td class="py-2">{{ $o->id }}</td>
                                    <td class="py-2">{{ $o->customer->name ?? '-' }}</td>
                                    <td class="py-2">{{ $o->created_at->format('Y-m-d H:i') }}</td>
                                    <td class="py-2">{{ ucfirst($o->status) }}</td>
                                    <td class="py-2 text-right">
                                        <a href="{{ route('orders.show', $o) }}" class="text-blue-600">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-4 text-center text-gray-500">No recent orders.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
