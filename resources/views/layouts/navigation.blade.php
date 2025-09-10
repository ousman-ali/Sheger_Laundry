<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ auth()->user()->hasRole('Operator')
                        ? route('operator.my')
                        : (auth()->user()->hasRole('Receptionist')
                            ? route('reception.index')
                            : (auth()->user()->hasRole('Manager')
                                ? route('manager.index')
                                : route('dashboard'))) }}">
                        <img src="{{ \App\Models\SystemSetting::getValue('company_logo_url', asset('logo.png')) }}" alt="{{ \App\Models\SystemSetting::getValue('company_name', config('app.name', 'Sheger Automatic Laundry')) }}" class="block h-16 w-auto" />
                    </a>
                </div>
                @php $isOperator = auth()->check() && auth()->user()->hasRole('Operator'); @endphp
                <!-- Navigation Links (only for Operator, others use sidebar) -->
                @if($isOperator)
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    @role('Admin')
                        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                            {{ __('Dashboard') }}
                        </x-nav-link>
                    @endrole
                    @role('Receptionist')
                        <x-nav-link :href="route('reception.index')" :active="request()->routeIs('reception.*')">
                            {{ __('Reception') }}
                        </x-nav-link>
                    @endrole
                    @role('Manager')
                        <x-nav-link :href="route('manager.index')" :active="request()->routeIs('manager.*')">
                            {{ __('Manager') }}
                        </x-nav-link>
                    @endrole
                    @role('Operator')
                        <x-nav-link :href="route('operator.my')" :active="request()->routeIs('operator.*')">
                            {{ __('My Tasks') }}
                        </x-nav-link>
                        @can('view_stock_out_requests')
                        <x-nav-link :href="route('operator.stock_out_requests.index')" :active="request()->routeIs('operator.stock_out_requests.*')">
                            {{ __('My Stock-outs') }}
                        </x-nav-link>
                        @endcan
                    @endrole
                    
                    @can('view_orders')
                    <x-nav-link :href="route('orders.index')" :active="request()->routeIs('orders.*')">
                        {{ __('Orders') }}
                    </x-nav-link>
                    @endcan
                    
                    @can('view_customers')
                    <x-nav-link :href="route('customers.index')" :active="request()->routeIs('customers.*')">
                        {{ __('Customers') }}
                    </x-nav-link>
                    @endcan
                    
                    @can('view_inventory')
                    <x-nav-link :href="route('inventory.index')" :active="request()->routeIs('inventory.*')">
                        {{ __('Inventory') }}
                    </x-nav-link>
                    <x-nav-link :href="route('inventory.stock')" :active="request()->routeIs('inventory.stock')">
                        {{ __('Inventory Stock') }}
                    </x-nav-link>
                    @endcan

                    
                    
                    @can('view_purchases')
                    <x-nav-link :href="route('purchases.index')" :active="request()->routeIs('purchases.*')">
                        {{ __('Purchases') }}
                    </x-nav-link>
                    @endcan
                    
                    @can('view_stock_transfers')
                    <x-nav-link :href="route('stock-transfers.index')" :active="request()->routeIs('stock-transfers.*')">
                        {{ __('Stock Transfers') }}
                    </x-nav-link>
                    @endcan
                    
                    @can('view_services')
                    <x-nav-link :href="route('services.index')" :active="request()->routeIs('services.*')">
                        {{ __('Services') }}
                    </x-nav-link>
                    @endcan
                    
                    @can('view_cloth_items')
                    <x-nav-link :href="route('cloth-items.index')" :active="request()->routeIs('cloth-items.*')">
                        {{ __('Cloth Items') }}
                    </x-nav-link>
                    @endcan
                    
                    @can('view_pricing')
                    <x-nav-link :href="route('pricing.index')" :active="request()->routeIs('pricing.*')">
                        {{ __('Pricing') }}
                    </x-nav-link>
                    @endcan
                    @can('view_units')
                    @if (Route::has('units.index'))
                    <x-nav-link :href="route('units.index')" :active="request()->routeIs('units.*')">
                        {{ __('Units') }}
                    </x-nav-link>
                    @endif
                    @endcan
                    
                    @can('view_users')
                    <x-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                        {{ __('Users') }}
                    </x-nav-link>
                    @endcan
                </div>
                @endif
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6 gap-4">
                <!-- Notifications Dropdown -->
                @php
                    $initialUnreadModels = Auth::user()->notifications()->where('is_read', false)->latest()->limit(8)->get();
                    $initialCount = $initialUnreadModels->count();
                    $initialItems = $initialUnreadModels->map(function($n){
                        return [
                            'id' => $n->id,
                            'message' => $n->message,
                            'created_at' => optional($n->created_at)->toIso8601String(),
                            'created_at_human' => optional($n->created_at)->diffForHumans(),
                        ];
                    })->toArray();
                @endphp
                <div x-data="notificationsDropdown()" x-init="init()" class="relative">
                    <button @click="open=!open" class="relative inline-flex items-center justify-center w-10 h-10 rounded-full hover:bg-gray-100" title="Notifications">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600" viewBox="0 0 24 24" fill="currentColor"><path d="M12 22a2 2 0 002-2H10a2 2 0 002 2zm6-6V10a6 6 0 10-12 0v6l-2 2v1h16v-1l-2-2z"/></svg>
                        <template x-if="count > 0">
                            <span class="absolute -top-0.5 -right-0.5 bg-red-600 text-white text-xs rounded-full px-1.5" x-text="count"></span>
                        </template>
                    </button>
                    <div x-show="open" @click.outside="open=false" x-cloak class="absolute right-0 mt-2 w-80 bg-white border border-gray-200 rounded-md shadow-lg z-50">
                        <div class="p-2 border-b font-medium">Notifications</div>
                        <div class="max-h-72 overflow-auto">
                            <template x-if="items.length === 0">
                                <div class="p-3 text-sm text-gray-500">No new notifications.</div>
                            </template>
                            <template x-for="n in items" :key="n.id">
                                <a :href="n.url ? ('{{ url('notifications') }}/' + n.id + '/read') : '#'" class="block px-3 py-2 text-sm border-b last:border-b-0 hover:bg-gray-50">
                                    <div class="text-gray-800" x-text="n.message"></div>
                                    <div class="text-xs text-gray-500" x-text="n.created_at_human"></div>
                                </a>
                            </template>
                        </div>
                        <div class="p-2 flex items-center justify-between">
                            <form method="POST" action="{{ route('notifications.markAllRead') }}">
                                @csrf
                                <button class="text-sm text-blue-600 hover:underline">Mark all as read</button>
                            </form>
                        </div>
                    </div>
                </div>
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            @role('Admin')
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            @endrole
            @role('Receptionist')
            <x-responsive-nav-link :href="route('reception.index')" :active="request()->routeIs('reception.*')">
                {{ __('Reception') }}
            </x-responsive-nav-link>
            @endrole
            @role('Manager')
            <x-responsive-nav-link :href="route('manager.index')" :active="request()->routeIs('manager.*')">
                {{ __('Manager') }}
            </x-responsive-nav-link>
            @endrole
            @role('Operator')
            <x-responsive-nav-link :href="route('operator.my')" :active="request()->routeIs('operator.*')">
                {{ __('My Tasks') }}
            </x-responsive-nav-link>
            @can('view_stock_out_requests')
            <x-responsive-nav-link :href="route('operator.stock_out_requests.index')" :active="request()->routeIs('operator.stock_out_requests.*')">
                {{ __('My Stock-outs') }}
            </x-responsive-nav-link>
            @endcan
            @endrole
            
            @can('view_orders')
            <x-responsive-nav-link :href="route('orders.index')" :active="request()->routeIs('orders.*')">
                {{ __('Orders') }}
            </x-responsive-nav-link>
            @endcan
            
            @can('view_customers')
            <x-responsive-nav-link :href="route('customers.index')" :active="request()->routeIs('customers.*')">
                {{ __('Customers') }}
            </x-responsive-nav-link>
            @endcan
            
            @can('view_inventory')
            <x-responsive-nav-link :href="route('inventory.index')" :active="request()->routeIs('inventory.*')">
                {{ __('Inventory') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('inventory.stock')" :active="request()->routeIs('inventory.stock')">
                {{ __('Inventory Stock') }}
            </x-responsive-nav-link>
            @endcan

            @unlessrole('Operator')
                @can('view_stock_out_requests')
                <x-responsive-nav-link :href="route('stock-out-requests.index')" :active="request()->routeIs('stock-out-requests.*')">
                    {{ __('Stock-out Requests') }}
                </x-responsive-nav-link>
                @endcan
            @endunless
            
            @can('view_purchases')
            <x-responsive-nav-link :href="route('purchases.index')" :active="request()->routeIs('purchases.*')">
                {{ __('Purchases') }}
            </x-responsive-nav-link>
            @endcan
            
            @can('view_stock_transfers')
            <x-responsive-nav-link :href="route('stock-transfers.index')" :active="request()->routeIs('stock-transfers.*')">
                {{ __('Stock Transfers') }}
            </x-responsive-nav-link>
            @endcan
            
            @can('view_services')
            <x-responsive-nav-link :href="route('services.index')" :active="request()->routeIs('services.*')">
                {{ __('Services') }}
            </x-responsive-nav-link>
            @endcan
            
            @can('view_cloth_items')
            <x-responsive-nav-link :href="route('cloth-items.index')" :active="request()->routeIs('cloth-items.*')">
                {{ __('Cloth Items') }}
            </x-responsive-nav-link>
            @endcan
            
            @can('view_pricing')
            <x-responsive-nav-link :href="route('pricing.index')" :active="request()->routeIs('pricing.*')">
                {{ __('Pricing') }}
            </x-responsive-nav-link>
            @endcan
            @can('view_units')
            @if (Route::has('units.index'))
            <x-responsive-nav-link :href="route('units.index')" :active="request()->routeIs('units.*')">
                {{ __('Units') }}
            </x-responsive-nav-link>
            @endif
            @endcan
            
            @can('view_users')
            <x-responsive-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                {{ __('Users') }}
            </x-responsive-nav-link>
            @endcan

            @can('view_payments')
            <x-responsive-nav-link :href="route('payments.index')" :active="request()->routeIs('payments.*')">
                {{ __('Payments') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('ledgers.index')" :active="request()->routeIs('ledgers.*')">
                {{ __('Ledgers') }}
            </x-responsive-nav-link>
            @endcan
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                @can('manage_remarks_presets')
                <x-responsive-nav-link :href="route('remark-presets.index')">
                    {{ __('Remark Presets') }}
                </x-responsive-nav-link>
                @endcan

                @can('view_users')
                <x-responsive-nav-link :href="route('users.index')">
                    {{ __('User Management') }}
                </x-responsive-nav-link>
                @endcan

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>

<script>
    function notificationsDropdown() {
        return {
            open: false,
            count: {{ (int) $initialCount }},
            items: @json($initialItems),
            init() {
                if (window.Echo && window.APP_USER_ID) {
                    window.Echo.private('notifications.' + window.APP_USER_ID)
                        .listen('.notification.created', (e) => {
                            const now = new Date(e.created_at || Date.now());
                            const item = {
                                id: e.id,
                                message: e.message,
                                url: e.url || null,
                                created_at: now.toISOString(),
                                created_at_human: 'just now',
                            };
                            this.items.unshift(item);
                            this.items = this.items.slice(0, 8);
                            this.count += 1;
                        })
                        .listen('.notifications.marked_read', () => {
                            this.count = 0;
                            this.items = [];
                        });
                }
            }
        }
    }
</script>
