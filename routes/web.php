<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ClothItemController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\OrderServiceWorkflowController;
use App\Http\Controllers\OperatorDashboardController;
use App\Http\Controllers\ReceptionistDashboardController;
use App\Http\Controllers\ManagerDashboardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderPenaltyController;
use App\Http\Controllers\PaymentLedgerController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\RemarkPresetController;
use App\Http\Controllers\StockOutRequestController;

// Health check (no auth) for load balancers / uptime monitors
Route::get('/healthz', function () {
    return response()->json(['status' => 'ok']);
});

// Redirect root to role-based dashboards
Route::get('/', function () {
    if (!Auth::check()) {
        return redirect()->route('login');
    }
    /** @var \App\Models\User $user */
    $user = Auth::user();
    if ($user->hasRole('Operator')) {
        return redirect()->route('operator.my');
    }
    if ($user->hasRole('Manager')) {
        return redirect()->route('manager.index');
    }
    if ($user->hasRole('Receptionist')) {
        return redirect()->route('reception.index');
    }
    // Default to Admin dashboard
    return redirect()->route('dashboard');
});

// Main dashboard (Admin view; accessible to authenticated users)
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');

Route::get('/dashboard/analytics', [DashboardController::class, 'analytics'])
    ->middleware(['auth', 'verified','role:Admin'])
    ->name('dashboard.analytics');

// Operator shortcuts for Stock-out Requests
Route::middleware(['auth','role:Operator'])->group(function(){
    // My stock-out requests (index shows all but controller + perms will still constrain)
    Route::get('/operator/stock-out-requests', [StockOutRequestController::class, 'index'])->name('operator.stock_out_requests.index');
    // Create new stock-out request
    Route::get('/operator/stock-out-requests/create', [StockOutRequestController::class, 'create'])->name('operator.stock_out_requests.create');
});

// Reports (Admin)
Route::get('/reports', [\App\Http\Controllers\ReportsController::class, 'index'])
    ->middleware(['auth', 'verified','role:Admin','permission:view_reports'])
    ->name('reports.index');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // User Management
    Route::resource('users', UserController::class);

    // Customers
    Route::resource('customers', CustomerController::class);

    // AJAX search for customers used by the order form
    Route::get('api/customers/search', [\App\Http\Controllers\CustomerController::class, 'search']);

    // Orders
    Route::resource('orders', OrderController::class);
    Route::get('orders/{order}/invoice', [OrderController::class, 'invoice'])->middleware('permission:print_orders')->name('orders.invoice');
    Route::get('orders/{order}/invoice.pdf', [OrderController::class, 'invoicePdf'])->middleware('permission:export_orders')->name('orders.invoice.pdf');
    Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
    // Order itemized penalties
    Route::post('orders/{order}/penalties', [OrderPenaltyController::class, 'store'])->name('orders.penalties.store');
    Route::post('penalties/{penalty}/waive', [OrderPenaltyController::class, 'waive'])->name('penalties.waive');
    Route::post('penalties/{penalty}/approve', [OrderPenaltyController::class, 'approve'])->name('penalties.approve');
    Route::delete('penalties/{penalty}', [OrderPenaltyController::class, 'destroy'])->name('penalties.destroy');
    // Order item services workflow
    Route::post('order-services/assign', [OrderServiceWorkflowController::class, 'assign'])
        ->middleware('permission:assign_service')
        ->name('order-services.assign');
    // Status updates use controller middleware (Operator can update or users with update_service_status)
    Route::post('order-services/status', [OrderServiceWorkflowController::class, 'updateStatus'])
        ->name('order-services.status');
    // Assignment status updates (Operators update their own portions)
    Route::post('order-services/assignment-status', [OrderServiceWorkflowController::class, 'updateAssignmentStatus'])
        ->name('order-services.assignment-status');

    // Assignment helpers
    Route::post('order-services/assign/orders', [OrderServiceWorkflowController::class, 'assignByOrders'])
        ->middleware('permission:assign_service')
        ->name('order-services.assign-orders');
    Route::post('order-services/assign/items', [OrderServiceWorkflowController::class, 'assignByItems'])
        ->middleware('permission:assign_service')
        ->name('order-services.assign-items');
    Route::post('order-services/assign/customers', [OrderServiceWorkflowController::class, 'assignByCustomers'])
        ->middleware('permission:assign_service')
        ->name('order-services.assign-customers');

    // Inventory: place stock routes BEFORE resource to avoid matching resource {inventory} (e.g. 'stock')
    Route::get('inventory/stock', [InventoryController::class, 'stock'])->name('inventory.stock');
    Route::post('inventory/stock', [InventoryController::class, 'updateStock'])->name('inventory.update-stock');
    Route::resource('inventory', InventoryController::class)->except(['show']);

    // Purchases
    Route::resource('purchases', PurchaseController::class);

    // Stock Transfers
    Route::resource('stock-transfers', StockTransferController::class);
    Route::post('stock-transfers/{stockTransfer}/return', [StockTransferController::class, 'createReturn'])
        ->name('stock-transfers.return');

    // Stock Usage
    Route::resource('stock-usage', \App\Http\Controllers\StockUsageController::class)->only(['index','create','store','show']);

    // Payments
    Route::get('payments/pending', [PaymentLedgerController::class, 'pending'])->name('payments.pending');
    // Suggestion endpoint for auto-filling amount on create form
    Route::get('payments/suggest', [\App\Http\Controllers\PaymentController::class, 'suggest'])->name('payments.suggest');
    // Order search for large datasets (AJAX)
    Route::get('payments/order-search', [\App\Http\Controllers\PaymentController::class, 'orderSearch'])->name('payments.order-search');
    // Approve a pending payment (penalty waiver) — Admin only
    Route::post('payments/{payment}/approve', [\App\Http\Controllers\PaymentController::class, 'approve'])
        ->middleware('role:Admin')
        ->name('payments.approve');
    Route::post('payments/{payment}/refund', [\App\Http\Controllers\PaymentController::class, 'refund'])
        ->middleware('permission:edit_payments')
        ->name('payments.refund');
    Route::post('payments/{payment}/reverse', [\App\Http\Controllers\PaymentController::class, 'reverse'])
        ->middleware('role:Admin')
        ->name('payments.reverse');
    Route::resource('payments', \App\Http\Controllers\PaymentController::class)->only(['index','create','store','show','edit','update']);
    Route::get('ledgers', [PaymentLedgerController::class, 'index'])->name('ledgers.index');
    Route::get('ledgers/{ledger}/breakdown', [PaymentLedgerController::class, 'breakdown'])->name('ledgers.breakdown');

    // Invoices index
    Route::get('invoices', [\App\Http\Controllers\InvoiceController::class, 'index'])->name('invoices.index');

    // Notifications
    Route::get('notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/mark-all-read', [\App\Http\Controllers\NotificationController::class, 'markAllRead'])->name('notifications.markAllRead');
    Route::match(['GET','POST'], 'notifications/{notification}/read', [\App\Http\Controllers\NotificationController::class, 'markRead'])->name('notifications.markRead');
    Route::delete('notifications/{notification}', [\App\Http\Controllers\NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::delete('notifications', [\App\Http\Controllers\NotificationController::class, 'bulkDestroy'])->name('notifications.bulkDestroy');
    Route::delete('notifications/cleanup/read', [\App\Http\Controllers\NotificationController::class, 'destroyRead'])->middleware('role:Admin')->name('notifications.destroyRead');
    Route::delete('notifications/cleanup/older', [\App\Http\Controllers\NotificationController::class, 'destroyOlder'])->middleware('role:Admin')->name('notifications.destroyOlder');

    // Settings (Admin)
    Route::middleware(['role:Admin'])->group(function () {
        Route::get('settings/penalty', [\App\Http\Controllers\SettingsController::class, 'editPenalty'])->name('settings.penalty.edit');
        Route::post('settings/penalty', [\App\Http\Controllers\SettingsController::class, 'updatePenalty'])->name('settings.penalty.update');
    Route::get('settings/company', [\App\Http\Controllers\SettingsController::class, 'editCompany'])->name('settings.company.edit');
    Route::post('settings/company', [\App\Http\Controllers\SettingsController::class, 'updateCompany'])->name('settings.company.update');
    Route::get('settings/order-id', [\App\Http\Controllers\SettingsController::class, 'editOrderId'])->name('settings.orderid.edit');
    Route::post('settings/order-id', [\App\Http\Controllers\SettingsController::class, 'updateOrderId'])->name('settings.orderid.update');
    });

    // Roles & Permissions (Admin)
    Route::middleware(['role:Admin'])->group(function(){
        Route::resource('roles', \App\Http\Controllers\RoleController::class)->only(['index','create','store','edit','update']);
        Route::get('roles-permissions/matrix', [\App\Http\Controllers\RoleController::class, 'matrix'])->middleware(['role:Admin'])->name('roles.matrix');
        Route::post('roles-permissions/matrix', [\App\Http\Controllers\RoleController::class, 'syncMatrix'])->middleware(['role:Admin'])->name('roles.matrix.sync');
        Route::get('activity-logs', [\App\Http\Controllers\ActivityLogController::class, 'index'])->name('activity-logs.index');
    });

    // Services
    Route::resource('services', ServiceController::class);

    // Remark Presets (permission-gated)
    Route::resource('remark-presets', RemarkPresetController::class)->only(['index','store','update','destroy']);

    // Stores
    Route::resource('stores', \App\Http\Controllers\StoreController::class)->except(['show']);

    // Stock-out Requests
    Route::get('stock-out-requests', [StockOutRequestController::class, 'index'])->name('stock-out-requests.index');
    Route::get('stock-out-requests/create', [StockOutRequestController::class, 'create'])->name('stock-out-requests.create');
    Route::post('stock-out-requests', [StockOutRequestController::class, 'store'])->name('stock-out-requests.store');
    Route::get('stock-out-requests/{stock_out_request}', [StockOutRequestController::class, 'show'])->name('stock-out-requests.show');
    Route::post('stock-out-requests/{stock_out_request}/submit', [StockOutRequestController::class, 'submit'])->name('stock-out-requests.submit');
    Route::post('stock-out-requests/{stock_out_request}/cancel', [StockOutRequestController::class, 'cancel'])->name('stock-out-requests.cancel');
    Route::post('stock-out-requests/{stock_out_request}/approve', [StockOutRequestController::class, 'approve'])->name('stock-out-requests.approve');
    Route::post('stock-out-requests/{stock_out_request}/reject', [StockOutRequestController::class, 'reject'])->name('stock-out-requests.reject');

    // Banks (Admin)
    Route::resource('banks', \App\Http\Controllers\BankController::class)->middleware('role:Admin');

    // Cloth Items
    Route::resource('cloth-items', ClothItemController::class);

    // Units
    Route::resource('units', \App\Http\Controllers\UnitController::class);

    // Pricing
    Route::resource('pricing', PricingController::class);
    
    // Urgency Tiers
    Route::resource('urgency-tiers', \App\Http\Controllers\UrgencyTierController::class)->except(['show']);
    Route::post('pricing/bulk-update', [PricingController::class, 'bulkUpdate'])->name('pricing.bulk-update');

    // Manager dashboard
    Route::get('manager', [ManagerDashboardController::class, 'index'])
        ->middleware('role:Manager')
        ->name('manager.index');

    // Receptionist dashboard
    Route::get('reception', [ReceptionistDashboardController::class, 'index'])
        ->middleware('role:Receptionist')
        ->name('reception.index');

    // Operator dashboard
    Route::get('operator', [OperatorDashboardController::class, 'index'])->name('operator.index');
    Route::get('operator/my', [OperatorDashboardController::class, 'my'])->name('operator.my');
});

require __DIR__.'/auth.php';
