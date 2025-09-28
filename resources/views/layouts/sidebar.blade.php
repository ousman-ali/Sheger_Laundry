<aside class="hidden md:flex bg-white border-r border-gray-200 sticky top-0 h-screen flex-col" :class="sidebarCollapsed ? 'w-16' : 'w-64'">
    <div class="h-16 border-b flex items-center px-2 md:px-4 justify-between shrink-0">
        <span class="text-sm font-semibold text-gray-400" x-show="!sidebarCollapsed">Navigation</span>
        <button @click="toggleSidebar()" class="p-2 rounded hover:bg-blue-100" title="Toggle sidebar">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M3 5h14a1 1 0 010 2H3a1 1 0 110-2zm0 4h10a1 1 0 010 2H3a1 1 0 110-2zm0 4h14a1 1 0 010 2H3a1 1 0 110-2z" clip-rule="evenodd" />
            </svg>
        </button>
    </div>
    <nav class="flex-1 overflow-y-auto p-2 space-y-4" x-data="{ 
            openStart: true,
            openOps: true,
            openBilling: true,
            openCatalog: true,
            openStock: true,
            openAdmin: true,
            openSettings: true,
        }"
        x-init="
            openOps = {{ request()->routeIs('orders.*') || request()->routeIs('customers.*') || request()->routeIs('services.*') ? 'true' : 'false' }};
            openBilling = {{ request()->routeIs('payments.*') || request()->routeIs('ledgers.*') || request()->routeIs('invoices.*') ? 'true' : 'false' }};
            openCatalog = {{ request()->routeIs('cloth-items.*') || request()->routeIs('units.*') || request()->routeIs('pricing.*') || request()->routeIs('urgency-tiers.*') ? 'true' : 'false' }};
            openStock = {{ request()->routeIs('inventory.*') || request()->routeIs('purchases.*') || request()->routeIs('stock-transfers.*') || request()->routeIs('stock-usage.*') || request()->routeIs('stock-out-requests.*') || request()->routeIs('stores.*') ? 'true' : 'false' }};
            openAdmin = {{ request()->routeIs('users.*') || request()->routeIs('roles.*') || request()->routeIs('activity-logs.*') || request()->routeIs('notifications.*') || request()->routeIs('banks.*') || request()->routeIs('reports.*') ? 'true' : 'false' }};
            openSettings = {{ request()->routeIs('settings.*') || request()->routeIs('remark-presets.*') ? 'true' : 'false' }};
        "
        <!-- Home / Role start links -->
        <div>
            <button type="button" class="w-full flex items-center px-3 py-2 rounded hover:bg-blue-50" @click="openStart = !openStart">
                <div class="flex items-center gap-3" :class="sidebarCollapsed ? 'justify-center w-full' : ''">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 1.293a1 1 0 00-1.414 0l-8 8A1 1 0 002 11h1v7a1 1 0 001 1h4v-5h4v5h4a1 1 0 001-1v-7h1a1 1 0 00.707-1.707l-8-8z"/></svg>
                    <span class="text-xs uppercase text-gray-500" x-show="!sidebarCollapsed">Start</span>
                </div>
                <svg x-show="!sidebarCollapsed" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-auto transform transition" :class="openStart ? 'rotate-90' : ''" viewBox="0 0 20 20" fill="currentColor"><path d="M7 5l6 5-6 5V5z"/></svg>
            </button>
            <div class="mt-1 space-y-1" x-show="openStart" x-collapse>
            @role('Admin')
                <a href="{{ route('dashboard') }}" title="Dashboard" aria-current="{{ request()->routeIs('dashboard') ? 'page' : 'false' }}" class="flex items-center px-5 py-1.5 rounded text-sm {{ request()->routeIs('dashboard') ? 'bg-blue-100 text-blue-900 font-semibold' : 'hover:bg-blue-50' }}" :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 1.293a1 1 0 00-1.414 0l-8 8A1 1 0 002 11h1v7a1 1 0 001 1h4v-5h4v5h4a1 1 0 001-1v-7h1a1 1 0 00.707-1.707l-8-8z"/></svg>
                    <span x-show="!sidebarCollapsed">Dashboard</span>
                </a>
            @endrole
            @role('Receptionist')
                <a href="{{ route('reception.index') }}" title="Reception" aria-current="{{ request()->routeIs('reception.*') ? 'page' : 'false' }}" class="flex items-center px-5 py-1.5 rounded text-sm {{ request()->routeIs('reception.*') ? 'bg-blue-100 text-blue-900 font-semibold' : 'hover:bg-blue-50' }}" :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M4 4h16v12H4z"/><path d="M2 18h20v2H2z"/></svg>
                    <span x-show="!sidebarCollapsed">Reception</span>
                </a>
            @endrole
            @role('Manager')
                <a href="{{ route('manager.index') }}" title="Manager" aria-current="{{ request()->routeIs('manager.*') ? 'page' : 'false' }}" class="flex items-center px-5 py-1.5 rounded text-sm {{ request()->routeIs('manager.*') ? 'bg-blue-100 text-blue-900 font-semibold' : 'hover:bg-blue-50' }}" :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M3 3h18v2H3zM3 7h18v2H3zM3 11h18v2H3zM3 15h18v2H3z"/></svg>
                    <span x-show="!sidebarCollapsed">Manager</span>
                </a>
            @endrole
            </div>
        </div>

        <!-- Operations -->
        <div>
            <button type="button" class="w-full flex items-center px-3 py-2 rounded hover:bg-blue-50" @click="openOps = !openOps">
                <div class="flex items-center gap-3" :class="sidebarCollapsed ? 'justify-center w-full' : ''">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M7 4h10l1 2h3v2H3V6h3l1-2z"/><path d="M5 8h14l-1 12H6L5 8z"/></svg>
                    <span class="text-xs uppercase text-gray-500" x-show="!sidebarCollapsed">Operations</span>
                </div>
                <svg x-show="!sidebarCollapsed" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-auto transform transition" :class="openOps ? 'rotate-90' : ''" viewBox="0 0 20 20" fill="currentColor"><path d="M7 5l6 5-6 5V5z"/></svg>
            </button>
            <div class="mt-1 space-y-1" x-show="openOps" x-collapse>
            @can('view_orders')
                <a href="{{ route('orders.index') }}" title="Orders" aria-current="{{ request()->routeIs('orders.*') ? 'page' : 'false' }}" class="flex items-center px-5 py-1.5 rounded text-sm {{ request()->routeIs('orders.*') ? 'bg-blue-100 text-blue-900 font-semibold' : 'hover:bg-blue-50' }}" :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M7 4h10l1 2h3v2H3V6h3l1-2z"/><path d="M5 8h14l-1 12H6L5 8z"/></svg>
                    <span x-show="!sidebarCollapsed">Orders</span>
                </a>
            @endcan
            @role('Operator')
                @can('create_stock_out_requests')
                <a href="{{ route('operator.stock_out_requests.create') }}" title="New Stock-out" aria-current="{{ request()->routeIs('operator.stock_out_requests.create') ? 'page' : 'false' }}" class="flex items-center px-5 py-1.5 rounded text-sm {{ request()->routeIs('operator.stock_out_requests.create') ? 'bg-blue-100 text-blue-900 font-semibold' : 'hover:bg-blue-50' }}" :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M12 4v16M4 12h16"/></svg>
                    <span x-show="!sidebarCollapsed">New Stock-out</span>
                </a>
                @endcan
                @can('view_stock_out_requests')
                <a href="{{ route('operator.stock_out_requests.index') }}" title="My Stock-outs" aria-current="{{ request()->routeIs('operator.stock_out_requests.*') ? 'page' : 'false' }}" class="flex items-center px-5 py-1.5 rounded text-sm {{ request()->routeIs('operator.stock_out_requests.*') ? 'bg-blue-100 text-blue-900 font-semibold' : 'hover:bg-blue-50' }}" :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M7 4h10l1 2h3v2H3V6h3l1-2z"/><path d="M5 8h14l-1 12H6L5 8z"/></svg>
                    <span x-show="!sidebarCollapsed">My Stock-outs</span>
                </a>
                @endcan
            @endrole
            @can('view_customers')
                <a href="{{ route('customers.index') }}" title="Customers" aria-current="{{ request()->routeIs('customers.*') ? 'page' : 'false' }}" class="flex items-center px-5 py-1.5 rounded text-sm {{ request()->routeIs('customers.*') ? 'bg-blue-100 text-blue-900 font-semibold' : 'hover:bg-blue-50' }}" :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12a5 5 0 100-10 5 5 0 000 10z"/><path d="M4 22a8 8 0 1120 0z"/></svg>
                    <span x-show="!sidebarCollapsed">Customers</span>
                </a>
            @endcan
            @can('view_services')
                <a href="{{ route('services.index') }}" title="Services" aria-current="{{ request()->routeIs('services.*') ? 'page' : 'false' }}" class="flex items-center px-5 py-1.5 rounded text-sm {{ request()->routeIs('services.*') ? 'bg-blue-100 text-blue-900 font-semibold' : 'hover:bg-blue-50' }}" :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3 7h7l-5.5 4 2 7-6.5-4.5L5.5 20l2-7L2 9h7z"/></svg>
                    <span x-show="!sidebarCollapsed">Services</span>
                </a>
            @endcan
            </div>
        </div>

        <!-- Billing -->
        @can('view_payments')
        <div>
            <button type="button" class="w-full flex items-center px-3 py-2 rounded hover:bg-blue-50" @click="openBilling = !openBilling">
                <div class="flex items-center gap-3" :class="sidebarCollapsed ? 'justify-center w-full' : ''">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M3 6h18v12H3z"/><path d="M3 10h18v2H3z"/></svg>
                    <span class="text-xs uppercase text-gray-500" x-show="!sidebarCollapsed">Billing</span>
                </div>
                <svg x-show="!sidebarCollapsed" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-auto transform transition" :class="openBilling ? 'rotate-90' : ''" viewBox="0 0 20 20" fill="currentColor"><path d="M7 5l6 5-6 5V5z"/></svg>
            </button>
            <div class="mt-1 space-y-1" x-show="openBilling" x-collapse>
            <a href="{{ route('payments.pending') }}" title="Pending Payments" aria-current="{{ request()->routeIs('payments.pending') ? 'page' : 'false' }}" class="flex items-center px-5 py-1.5 rounded text-sm {{ request()->routeIs('payments.pending') ? 'bg-blue-100 text-blue-900 font-semibold' : 'hover:bg-blue-50' }}" :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M3 6h18v12H3z"/><path d="M3 10h18v2H3z"/></svg>
                <span x-show="!sidebarCollapsed">Pending Payments</span>
            </a>
            <a href="{{ route('invoices.index') }}" title="Invoices" aria-current="{{ request()->routeIs('invoices.*') ? 'page' : 'false' }}" class="flex items-center px-5 py-1.5 rounded text-sm {{ request()->routeIs('invoices.*') ? 'bg-blue-100 text-blue-900 font-semibold' : 'hover:bg-blue-50' }} mt-1" :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M6 2h9l5 5v13a2 2 0 01-2 2H6a2 2 0 01-2-2V4a2 2 0 012-2zm9 1.5V7h3.5L15 3.5zM8 9h8v2H8V9zm0 4h8v2H8v-2z"/></svg>
                <span x-show="!sidebarCollapsed">Invoices</span>
            </a>
            <a href="{{ route('payments.index') }}" title="Payments" aria-current="{{ request()->routeIs('payments.*') ? 'page' : 'false' }}" class="flex items-center px-5 py-1.5 rounded text-sm {{ request()->routeIs('payments.*') ? 'bg-blue-100 text-blue-900 font-semibold' : 'hover:bg-blue-50' }} mt-1" :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M3 6h18v12H3z"/><path d="M3 10h18v2H3z"/></svg>
                <span x-show="!sidebarCollapsed">Payments</span>
            </a>
            <a href="{{ route('ledgers.index') }}" title="Ledgers" aria-current="{{ request()->routeIs('ledgers.*') ? 'page' : 'false' }}" class="flex items-center px-5 py-1.5 rounded text-sm {{ request()->routeIs('ledgers.*') ? 'bg-blue-100 text-blue-900 font-semibold' : 'hover:bg-blue-50' }} mt-1" :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M4 4h16v4H4zM4 10h16v4H4zM4 16h16v4H4z"/></svg>
                <span x-show="!sidebarCollapsed">Ledgers</span>
            </a>
            </div>
        </div>
        @endcan

        <!-- Catalog -->
        <div>
            <button type="button" class="w-full flex items-center px-3 py-2 rounded hover:bg-blue-50" @click="openCatalog = !openCatalog">
                <div class="flex items-center gap-3" :class="sidebarCollapsed ? 'justify-center w-full' : ''">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M16 3l5 5-9 13-9-13 5-5z"/></svg>
                    <span class="text-xs uppercase text-gray-500" x-show="!sidebarCollapsed">Catalog & Pricing</span>
                </div>
                <svg x-show="!sidebarCollapsed" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-auto transform transition" :class="openCatalog ? 'rotate-90' : ''" viewBox="0 0 20 20" fill="currentColor"><path d="M7 5l6 5-6 5V5z"/></svg>
            </button>
            <div class="mt-1 space-y-1" x-show="openCatalog" x-collapse>
            @can('view_clothing_groups')
                <a href="{{ route('clothing-groups.index') }}" 
                    title="Clothing Groups" 
                    aria-current="{{ request()->routeIs('clothing-groups.*') ? 'page' : 'false' }}" 
                    class="flex items-center px-5 py-1.5 rounded text-sm {{ request()->routeIs('clothing-groups.*') ? 'bg-blue-100 text-blue-900 font-semibold' : 'hover:bg-blue-50' }}" 
                    :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M3 3h18v2H3zM3 7h18v2H3zM3 11h18v2H3zM3 15h18v2H3z"/></svg>
                    <span x-show="!sidebarCollapsed">Cloth Groups</span>
                </a>
            @endcan
            @can('view_cloth_items')
                <a href="{{ route('cloth-items.index') }}" title="Cloth Items" aria-current="{{ request()->routeIs('cloth-items.*') ? 'page' : 'false' }}" class="flex items-center px-5 py-1.5 rounded text-sm {{ request()->routeIs('cloth-items.*') ? 'bg-blue-100 text-blue-900 font-semibold' : 'hover:bg-blue-50' }}" :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M16 3l5 5-9 13-9-13 5-5z"/></svg>
                    <span x-show="!sidebarCollapsed">Cloth Items</span>
                </a>
            @endcan
            @if (Route::has('units.index'))
                @role('Admin')
                    <a href="{{ route('units.index') }}" title="Units" aria-current="{{ request()->routeIs('units.*') ? 'page' : 'false' }}" class="flex items-center px-5 py-1.5 rounded text-sm {{ request()->routeIs('units.*') ? 'bg-blue-100 text-blue-900 font-semibold' : 'hover:bg-blue-50' }}" :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M3 3h8v8H3zM13 3h8v8h-8zM3 13h8v8H3zM13 13h8v8h-8z"/></svg>
                        <span x-show="!sidebarCollapsed">Units</span>
                    </a>
                @else
                    @can('view_units')
                        <a href="{{ route('units.index') }}" title="Units" aria-current="{{ request()->routeIs('units.*') ? 'page' : 'false' }}" class="flex items-center px-5 py-1.5 rounded text-sm {{ request()->routeIs('units.*') ? 'bg-blue-100 text-blue-900 font-semibold' : 'hover:bg-blue-50' }}" :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M3 3h8v8H3zM13 3h8v8h-8zM3 13h8v8H3zM13 13h8v8h-8z"/></svg>
                            <span x-show="!sidebarCollapsed">Units</span>
                        </a>
                    @endcan
                @endrole
            @endif
            @can('view_pricing')
                <a href="{{ route('pricing.index') }}" title="Pricing" aria-current="{{ request()->routeIs('pricing.*') ? 'page' : 'false' }}" class="flex items-center px-5 py-1.5 rounded text-sm {{ request()->routeIs('pricing.*') ? 'bg-blue-100 text-blue-900 font-semibold' : 'hover:bg-blue-50' }}" :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M3 6h18v4H3zM3 12h18v6H3z"/></svg>
                    <span x-show="!sidebarCollapsed">Pricing</span>
                </a>
            @endcan
            @can('view_urgency_tiers')
                <a href="{{ route('urgency-tiers.index') }}" title="Urgency Tiers" aria-current="{{ request()->routeIs('urgency-tiers.*') ? 'page' : 'false' }}" class="flex items-center px-5 py-1.5 rounded text-sm {{ request()->routeIs('urgency-tiers.*') ? 'bg-blue-100 text-blue-900 font-semibold' : 'hover:bg-blue-50' }}" :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3 7h7l-5.5 4 2 7-6.5-4.5L5.5 20l2-7L2 9h7z"/></svg>
                    <span x-show="!sidebarCollapsed">Urgency Tiers</span>
                </a>
            @endcan
            </div>
        </div>

        <!-- Stock -->
        <div>
            <button type="button" class="w-full flex items-center px-3 py-2 rounded hover:bg-blue-50" @click="openStock = !openStock">
                <div class="flex items-center gap-3" :class="sidebarCollapsed ? 'justify-center w-full' : ''">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M3 5h18v4H3V5zm0 6h18v8H3v-8z"/></svg>
                    <span class="text-xs uppercase text-gray-500" x-show="!sidebarCollapsed">Inventory & Stock</span>
                </div>
                <svg x-show="!sidebarCollapsed" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-auto transform transition" :class="openStock ? 'rotate-90' : ''" viewBox="0 0 20 20" fill="currentColor"><path d="M7 5l6 5-6 5V5z"/></svg>
            </button>
            <div class="mt-1 space-y-1" x-show="openStock" x-collapse>
            @can('view_inventory')
                <a href="{{ route('inventory.index') }}" title="Inventory" aria-current="{{ request()->routeIs('inventory.index') ? 'page' : 'false' }}" class="flex items-center px-5 py-1.5 rounded text-sm {{ request()->routeIs('inventory.index') ? 'bg-blue-100 text-blue-900 font-semibold' : 'hover:bg-blue-50' }}" :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M3 5h18v4H3V5zm0 6h18v8H3v-8z"/></svg>
                    <span x-show="!sidebarCollapsed">Inventory</span>
                </a>
                <a href="{{ route('inventory.stock') }}" title="Inventory Stock" aria-current="{{ request()->routeIs('inventory.stock') ? 'page' : 'false' }}" class="flex items-center px-5 py-1.5 rounded text-sm {{ request()->routeIs('inventory.stock') ? 'bg-blue-100 text-blue-900 font-semibold' : 'hover:bg-blue-50' }} mt-1" :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M4 4h16v4H4zM4 10h16v4H4zM4 16h16v4H4z"/></svg>
                    <span x-show="!sidebarCollapsed">Inventory Stock</span>
                </a>
            @endcan
            @can('view_stores')
                <a href="{{ route('stores.index') }}" title="Stores" aria-current="{{ request()->routeIs('stores.*') ? 'page' : 'false' }}" class="flex items-center px-5 py-1.5 rounded text-sm {{ request()->routeIs('stores.*') ? 'bg-blue-100 text-blue-900 font-semibold' : 'hover:bg-blue-50' }} mt-1" :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M3 10l9-7 9 7v10a2 2 0 01-2 2h-4v-6H9v6H5a2 2 0 01-2-2V10z"/></svg>
                    <span x-show="!sidebarCollapsed">Stores</span>
                </a>
            @endcan
            @unlessrole('Operator')
                @can('view_stock_out_requests')
                    <a href="{{ route('stock-out-requests.index') }}" title="Stock-out Requests" aria-current="{{ request()->routeIs('stock-out-requests.*') ? 'page' : 'false' }}" class="flex items-center px-5 py-1.5 rounded text-sm {{ request()->routeIs('stock-out-requests.*') ? 'bg-blue-100 text-blue-900 font-semibold' : 'hover:bg-blue-50' }} mt-1" :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M7 4h10l1 2h4v2H2V6h4l1-2z"/><path d="M5 8h14l-1 12H6L5 8z"/></svg>
                        <span x-show="!sidebarCollapsed">Stock-out Requests</span>
                    </a>
                @endcan
            @endunless
            @can('view_purchases')
                <a href="{{ route('purchases.index') }}" title="Purchases" aria-current="{{ request()->routeIs('purchases.*') ? 'page' : 'false' }}" class="flex items-center px-5 py-1.5 rounded text-sm {{ request()->routeIs('purchases.*') ? 'bg-blue-100 text-blue-900 font-semibold' : 'hover:bg-blue-50' }}" :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M7 4h10l1 2h4v2H2V6h4l1-2z"/><path d="M5 8h14l-1 12H6L5 8z"/></svg>
                    <span x-show="!sidebarCollapsed">Purchases</span>
                </a>
            @endcan
            @can('view_stock_transfers')
                <a href="{{ route('stock-transfers.index') }}" title="Stock Transfers" aria-current="{{ request()->routeIs('stock-transfers.*') ? 'page' : 'false' }}" class="flex items-center px-5 py-1.5 rounded text-sm {{ request()->routeIs('stock-transfers.*') ? 'bg-blue-100 text-blue-900 font-semibold' : 'hover:bg-blue-50' }}" :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M7 7h10v2H7zM7 11h7v2H7zM7 15h4v2H7z"/></svg>
                    <span x-show="!sidebarCollapsed">Stock Transfers</span>
                </a>
            @endcan
            @can('view_stock_usage')
                <a href="{{ route('stock-usage.index') }}" title="Stock Usage" aria-current="{{ request()->routeIs('stock-usage.*') ? 'page' : 'false' }}" class="flex items-center px-5 py-1.5 rounded text-sm {{ request()->routeIs('stock-usage.*') ? 'bg-blue-100 text-blue-900 font-semibold' : 'hover:bg-blue-50' }}" :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M4 4h16v4H4zM4 10h16v4H4zM4 16h16v4H4z"/></svg>
                    <span x-show="!sidebarCollapsed">Stock Usage</span>
                </a>
            @endcan
            @role('Operator')
                @can('view_stock_out_requests')
                    <a href="{{ route('operator.stock_out_requests.index') }}" title="My Stock-outs" aria-current="{{ request()->routeIs('operator.stock_out_requests.*') ? 'page' : 'false' }}" class="flex items-center px-5 py-1.5 rounded text-sm {{ request()->routeIs('operator.stock_out_requests.*') ? 'bg-blue-100 text-blue-900 font-semibold' : 'hover:bg-blue-50' }} mt-1" :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M7 4h10l1 2h3v2H3V6h3l1-2z"/><path d="M5 8h14l-1 12H6L5 8z"/></svg>
                        <span x-show="!sidebarCollapsed">My Stock-outs</span>
                    </a>
                @endcan
            @endrole
            </div>
        </div>

        <!-- Settings (Top-level) -->
        @role('Admin')
        <div>
            <button type="button" class="w-full flex items-center px-3 py-2 rounded hover:bg-blue-50" @click="openSettings = !openSettings">
                <div class="flex items-center gap-3" :class="sidebarCollapsed ? 'justify-center w-full' : ''">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M19.14 12.936a7.966 7.966 0 000-1.872l2.036-1.58a1 1 0 00.24-1.31l-1.928-3.338a1 1 0 00-1.2-.453l-2.396.96a7.994 7.994 0 00-1.62-.942l-.36-2.54A1 1 0 0012 0h-4a1 1 0 00-.992.874l-.36 2.54a7.994 7.994 0 00-1.62.942l-2.396-.96a1 1 0 00-1.2.453L.104 7.237a1 1 0 00.24 1.31l2.036 1.58a7.966 7.966 0 000 1.872l-2.036 1.58a1 1 0 00-.24 1.31l1.928 3.338a1 1 0 001.2.453l2.396-.96c.504.39 1.05.718 1.62.942l.36 2.54A1 1 0 008 24h4a1 1 0 00.992-.874l.36-2.54c.57-.224 1.116-.552 1.62-.942l2.396.96a1 1 0 001.2-.453l1.928-3.338a1 1 0 00-.24-1.31l-2.036-1.58zM10 16a4 4 0 110-8 4 4 0 010 8z"/></svg>
                    <span class="text-xs uppercase text-gray-500" x-show="!sidebarCollapsed">Settings</span>
                </div>
                <svg x-show="!sidebarCollapsed" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-auto transform transition" :class="openSettings ? 'rotate-90' : ''" viewBox="0 0 20 20" fill="currentColor"><path d="M7 5l6 5-6 5V5z"/></svg>
            </button>
            <div class="mt-1 space-y-1" x-show="openSettings" x-collapse>
                <a href="{{ route('settings.company.edit') }}" title="Company & Invoice" aria-current="{{ request()->routeIs('settings.company.*') ? 'page' : 'false' }}" class="flex items-center px-5 py-1.5 rounded text-sm {{ request()->routeIs('settings.company.*') ? 'bg-blue-100 text-blue-900 font-semibold' : 'hover:bg-blue-50' }}" :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M6 2h9l5 5v13a2 2 0 01-2 2H6a2 2 0 01-2-2V4a2 2 0 012-2zm9 1.5V7h3.5L15 3.5zM8 9h8v2H8V9zm0 4h8v2H8v-2z"/></svg>
                    <span x-show="!sidebarCollapsed">Company & Invoice</span>
                </a>
                <a href="{{ route('settings.penalty.edit') }}" title="Penalty Settings" aria-current="{{ request()->routeIs('settings.penalty.*') ? 'page' : 'false' }}" class="flex items-center px-5 py-1.5 rounded text-sm {{ request()->routeIs('settings.penalty.*') ? 'bg-blue-100 text-blue-900 font-semibold' : 'hover:bg-blue-50' }}" :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M3 4h18v2H3zM3 8h18v2H3zM3 12h12v2H3zM3 16h12v2H3z"/></svg>
                    <span x-show="!sidebarCollapsed">Penalty Settings</span>
                </a>
                <a href="{{ route('settings.orderid.edit') }}" title="Order ID Settings" aria-current="{{ request()->routeIs('settings.orderid.*') ? 'page' : 'false' }}" class="flex items-center px-5 py-1.5 rounded text-sm {{ request()->routeIs('settings.orderid.*') ? 'bg-blue-100 text-blue-900 font-semibold' : 'hover:bg-blue-50' }}" :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M3 6h18v2H3zM3 10h18v2H3zM3 14h12v2H3zM3 18h12v2H3z"/></svg>
                    <span x-show="!sidebarCollapsed">Order ID Settings</span>
                </a>
                @can('manage_remarks_presets')
                <a href="{{ route('remark-presets.index') }}" title="Remark Presets" aria-current="{{ request()->routeIs('remark-presets.*') ? 'page' : 'false' }}" class="flex items-center px-5 py-1.5 rounded text-sm {{ request()->routeIs('remark-presets.*') ? 'bg-blue-100 text-blue-900 font-semibold' : 'hover:bg-blue-50' }}" :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M3 5h18v14H3z"/><path d="M7 9h10v2H7zM7 13h6v2H7z"/></svg>
                    <span x-show="!sidebarCollapsed">Remark Presets</span>
                </a>
                @endcan
            </div>
        </div>
        @endrole

        <!-- Administration -->
        <div>
            <button type="button" class="w-full flex items-center px-3 py-2 rounded hover:bg-blue-50" @click="openAdmin = !openAdmin">
                <div class="flex items-center gap-3" :class="sidebarCollapsed ? 'justify-center w-full' : ''">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12a5 5 0 100-10 5 5 0 000 10z"/><path d="M2 22a10 10 0 1120 0z"/></svg>
                    <span class="text-xs uppercase text-gray-500" x-show="!sidebarCollapsed">Administration</span>
                </div>
                <svg x-show="!sidebarCollapsed" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-auto transform transition" :class="openAdmin ? 'rotate-90' : ''" viewBox="0 0 20 20" fill="currentColor"><path d="M7 5l6 5-6 5V5z"/></svg>
            </button>
            <div class="mt-1 space-y-1" x-show="openAdmin" x-collapse>
            @can('view_users')
                <a href="{{ route('users.index') }}" title="Users" aria-current="{{ request()->routeIs('users.*') ? 'page' : 'false' }}" class="flex items-center px-5 py-1.5 rounded text-sm {{ request()->routeIs('users.*') ? 'bg-blue-100 text-blue-900 font-semibold' : 'hover:bg-blue-50' }}" :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12a5 5 0 100-10 5 5 0 000 10z"/><path d="M2 22a10 10 0 1120 0z"/></svg>
                    <span x-show="!sidebarCollapsed">Users</span>
                </a>
            @endcan
            @role('Admin')
                <a href="{{ route('reports.index') }}" title="Reports" aria-current="{{ request()->routeIs('reports.*') ? 'page' : 'false' }}" class="flex items-center px-5 py-1.5 rounded text-sm {{ request()->routeIs('reports.*') ? 'bg-blue-100 text-blue-900 font-semibold' : 'hover:bg-blue-50' }} mt-1" :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M3 3h18v2H3zM3 7h14v2H3zM3 11h10v2H3zM3 15h6v2H3z"/></svg>
                    <span x-show="!sidebarCollapsed">Reports</span>
                </a>
            @endrole
            @role('Admin')
                <a href="{{ route('roles.matrix') }}" title="Permissions Matrix" aria-current="{{ request()->routeIs('roles.matrix') ? 'page' : 'false' }}" class="flex items-center px-5 py-1.5 rounded text-sm {{ request()->routeIs('roles.matrix') ? 'bg-blue-100 text-blue-900 font-semibold' : 'hover:bg-blue-50' }} mt-1" :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M4 4h16v2H4zM4 9h16v2H4zM4 14h16v2H4zM4 19h16v2H4z"/></svg>
                    <span x-show="!sidebarCollapsed">Permissions Matrix</span>
                </a>
            @endrole
            
            @role('Admin')
                <a href="{{ route('notifications.index') }}" title="Notifications" aria-current="{{ request()->routeIs('notifications.*') ? 'page' : 'false' }}" class="flex items-center px-5 py-1.5 rounded text-sm {{ request()->routeIs('notifications.*') ? 'bg-blue-100 text-blue-900 font-semibold' : 'hover:bg-blue-50' }} mt-1" :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a6 6 0 00-6 6v3.586l-1.707 1.707A1 1 0 005 15h14a1 1 0 00.707-1.707L18 11.586V8a6 6 0 00-6-6zm0 20a3 3 0 01-3-3h6a3 3 0 01-3 3z"/></svg>
                    <span x-show="!sidebarCollapsed">Notifications</span>
                </a>
                <a href="{{ route('banks.index') }}" title="Banks" aria-current="{{ request()->routeIs('banks.*') ? 'page' : 'false' }}" class="flex items-center px-5 py-1.5 rounded text-sm {{ request()->routeIs('banks.*') ? 'bg-blue-100 text-blue-900 font-semibold' : 'hover:bg-blue-50' }} mt-1" :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                    <span x-show="!sidebarCollapsed">Banks</span>
                </a>
                <a href="{{ route('roles.index') }}" title="Roles & Permissions" aria-current="{{ request()->routeIs('roles.*') ? 'page' : 'false' }}" class="flex items-center px-5 py-1.5 rounded text-sm {{ request()->routeIs('roles.*') ? 'bg-blue-100 text-blue-900 font-semibold' : 'hover:bg-blue-50' }} mt-1" :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12a5 5 0 100-10 5 5 0 000 10z"/><path d="M2 22a10 10 0 1120 0z"/></svg>
                    <span x-show="!sidebarCollapsed">Roles & Permissions</span>
                </a>
                <a href="{{ route('activity-logs.index') }}" title="Activity Logs" aria-current="{{ request()->routeIs('activity-logs.*') ? 'page' : 'false' }}" class="flex items-center px-5 py-1.5 rounded text-sm {{ request()->routeIs('activity-logs.*') ? 'bg-blue-100 text-blue-900 font-semibold' : 'hover:bg-blue-50' }} mt-1" :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M3 3h18v2H3zM3 7h18v2H3zM3 11h18v2H3zM3 15h18v2H3z"/></svg>
                    <span x-show="!sidebarCollapsed">Activity Logs</span>
                </a>
            @endrole
            </div>
        </div>
    </nav>
</aside>
