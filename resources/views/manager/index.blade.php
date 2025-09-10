<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Manager Dashboard</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow sm:rounded-lg p-6">
                <form method="get" class="flex flex-wrap gap-3 items-end">
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Start Date</label>
                        <input type="date" name="start_date" value="{{ $start }}" class="border rounded px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">End Date</label>
                        <input type="date" name="end_date" value="{{ $end }}" class="border rounded px-3 py-2">
                    </div>
                    <button class="px-4 py-2 bg-blue-600 text-white rounded">Apply</button>
                </form>
            </div>

            <div class="grid md:grid-cols-3 gap-4">
                <div class="bg-white shadow sm:rounded-lg p-6">
                    <div class="text-gray-500 text-sm">Revenue</div>
                    <div class="text-3xl font-bold">ETB {{ number_format($revenue, 2) }}</div>
                </div>
                <div class="bg-white shadow sm:rounded-lg p-6">
                    <div class="text-gray-500 text-sm">Orders</div>
                    <div class="text-3xl font-bold">{{ $ordersCount }}</div>
                </div>
                <div class="bg-white shadow sm:rounded-lg p-6">
                    <div class="text-gray-500 text-sm">Avg. Order Value</div>
                    <div class="text-3xl font-bold">ETB {{ number_format($avgOrder, 2) }}</div>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-6">
                <div class="bg-white shadow sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-3">Operator Productivity</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-600">
                                    <th class="py-2">Operator</th>
                                    <th class="py-2">Completed</th>
                                    <th class="py-2">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($operatorPerf as $row)
                                    <tr class="border-t">
                                        <td class="py-2">{{ $row->employee->name ?? 'Unassigned' }}</td>
                                        <td class="py-2">{{ $row->completed_services }}</td>
                                        <td class="py-2">{{ $row->total_services }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="py-4 text-center text-gray-500">No data.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white shadow sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-3">Service Mix</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-600">
                                    <th class="py-2">Service</th>
                                    <th class="py-2">Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($serviceMix as $row)
                                    <tr class="border-t">
                                        <td class="py-2">{{ $row->service->name ?? 'Unknown' }}</td>
                                        <td class="py-2">{{ $row->count }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="2" class="py-4 text-center text-gray-500">No data.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-3">Orders Pending by Status</h3>
                <div class="flex flex-wrap gap-4">
                    @forelse($pendingByStatus as $row)
                        <div class="p-4 border rounded flex-1 min-w-[140px]">
                            <div class="text-gray-500 text-sm">{{ str_replace('_',' ', $row->status) }}</div>
                            <div class="text-2xl font-bold">{{ $row->count }}</div>
                        </div>
                    @empty
                        <div class="text-gray-500">No pending orders.</div>
                    @endforelse
                </div>
            </div>

            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-3">Operator Backlog</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-600">
                                <th class="py-2">Operator</th>
                                <th class="py-2">Backlog</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($inProgress as $row)
                                <tr class="border-t">
                                    <td class="py-2">{{ $row->employee->name ?? 'Unassigned' }}</td>
                                    <td class="py-2">{{ $row->backlog }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="py-4 text-center text-gray-500">No data.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
