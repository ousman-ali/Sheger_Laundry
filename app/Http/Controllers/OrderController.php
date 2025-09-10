<?php

   namespace App\Http\Controllers;

   use App\Models\ClothItem;
   use App\Models\Customer;
   use App\Models\Order;
   use App\Models\OrderItem;
   use App\Models\OrderItemService;
   use App\Models\PricingTier;
   use App\Models\Service;
   use App\Models\Unit;
   use App\Models\UrgencyTier;
   use App\Services\OrderService;
   use App\Services\NotificationService;
   use App\Http\Requests\OrderStoreRequest;
   use App\Http\Requests\OrderUpdateRequest;
   use Illuminate\Http\Request;
   use Illuminate\Support\Facades\DB;
   use Illuminate\Support\Facades\Log;
   use Illuminate\Support\Str;
   use Illuminate\Validation\Rule;

   class OrderController extends Controller
   {
       protected $orderService;
       protected $notificationService;

    public function __construct(OrderService $orderService, NotificationService $notificationService)
    {
        $this->orderService = $orderService;
        $this->notificationService = $notificationService;
        // Protect actions with authorization middleware (Spatie permissions)
        $this->middleware('permission:view_orders')->only(['index', 'show']);
        $this->middleware('permission:create_orders')->only(['create', 'store']);
        $this->middleware('permission:edit_orders')->only(['edit', 'update']);
        $this->middleware('permission:update_order_status')->only('updateStatus');
        $this->middleware('permission:delete_orders')->only('destroy');
    }

    public function index(Request $request)
       {
           $query = Order::with(['customer','orderItems.remarkPresets','orderItems.orderItemServices.employee','orderItems.orderItemServices.assignments.employee','remarkPresets'])
               ->latest();

           // Validate all filters together
           $validated = $request->validate([
               'q' => 'nullable|string|max:255',
               'status' => 'nullable|in:received,processing,washing,drying_steaming,ironing,packaging,ready_for_pickup,delivered,cancelled',
               'from_date' => 'nullable|date',
               'to_date' => 'nullable|date',
               'customer_id' => 'nullable|integer|exists:customers,id',
               'operator_id' => 'nullable|integer|exists:users,id',
               'per_page' => 'nullable|integer|in:10,25,50,100',
               'export' => 'nullable|in:csv,xlsx,pdf',
           ]);
           // Free-text search (order id, customer name, assigned employee)
           if (!empty($validated['q'] ?? null)) {
               $term = $validated['q'];
               $query->where(function ($w) use ($term) {
                   $w->where('order_id', 'like', "%{$term}%")
                     ->orWhereHas('customer', function ($q) use ($term) {
                         $q->where('name', 'like', "%{$term}%")->orWhere('code','like', "%{$term}%");
                     })
                     ->orWhereHas('orderItems.orderItemServices.employee', function ($q) use ($term) {
                         $q->where('name', 'like', "%{$term}%");
                     });
               });
           }

           // Status filter
           if (!empty($validated['status'] ?? null)) {
               $query->where('status', $validated['status']);
           }

           // Date range filters
           if (!empty($validated['from_date'] ?? null)) {
               $query->whereDate('created_at', '>=', $validated['from_date']);
           }
           if (!empty($validated['to_date'] ?? null)) {
               $query->whereDate('created_at', '<=', $validated['to_date']);
           }

           // Customer filter
           if (!empty($validated['customer_id'] ?? null)) {
               $query->where('customer_id', (int) $validated['customer_id']);
           }

           // Operator filter (orders with any service assigned to operator)
           if (!empty($validated['operator_id'] ?? null)) {
               $operatorId = (int) $validated['operator_id'];
                   $query->where(function($w) use ($operatorId){
                       $w->whereHas('orderItems.orderItemServices', function ($q) use ($operatorId) {
                           $q->where('employee_id', $operatorId);
                       })->orWhereHas('orderItems.orderItemServices.assignments', function($q) use ($operatorId) {
                           $q->where('employee_id', $operatorId);
                       });
                   });
           }

           // Operator visibility: if user is an Operator and lacks broad view permission, restrict to their assignments
           if (\Illuminate\Support\Facades\Auth::check()) {
               /** @var \App\Models\User $u */
               $u = \Illuminate\Support\Facades\Auth::user();
               if ($u->hasRole('Operator') && !$u->can('view_all_orders')) {
                   $query->where(function($w) use ($u){
                       $w->whereHas('orderItems.orderItemServices', function ($q) use ($u) {
                           $q->where('employee_id', $u->id);
                       })->orWhereHas('orderItems.orderItemServices.assignments', function($q) use ($u) {
                           $q->where('employee_id', $u->id);
                       });
                   });
               }
           }

           // Determine per-page from request or session (sticky preference)
           if (!empty($validated['per_page'] ?? null)) {
               $perPage = (int) $validated['per_page'];
               $request->session()->put('orders.per_page', $perPage);
           } else {
               $perPage = (int) $request->session()->get('orders.per_page', 10);
           }

           // CSV/XLSX export of current filtered view (unpaginated)
           if (!empty($validated['export'] ?? null)) {
               abort_unless(\Illuminate\Support\Facades\Gate::allows('export_orders'), 403);
           }
           if (($validated['export'] ?? null) === 'csv') {
               $filename = 'orders_export_' . now()->format('Ymd_His') . '.csv';
               $ordersForExport = (clone $query)->get();
               return response()->streamDownload(function () use ($ordersForExport) {
                   $out = fopen('php://output', 'w');
                   fputcsv($out, ['Order ID', 'Customer', 'Customer Code', 'VIP', 'Status', 'Total Cost', 'Assigned To', 'Remark Presets', 'Remarks', 'Created At']);
                   foreach ($ordersForExport as $o) {
                       // Build per-operator quantity breakdown across all services in the order
                       $breakdown = [];
                       foreach ($o->orderItems as $it) {
                           foreach ($it->orderItemServices as $svc) {
                               $assigns = $svc->relationLoaded('assignments') ? $svc->assignments : $svc->assignments()->with('employee')->get();
                               if ($assigns instanceof \Illuminate\Support\Collection ? $assigns->isNotEmpty() : (bool)count($assigns)) {
                                   foreach ($assigns as $a) {
                                       $name = optional($a->employee)->name;
                                       if (!$name) { continue; }
                                       $breakdown[$name] = ($breakdown[$name] ?? 0) + (float)$a->quantity;
                                   }
                               } elseif ($svc->employee) {
                                   // Legacy fallback if no assignment rows exist
                                   $name = $svc->employee->name;
                                   $breakdown[$name] = ($breakdown[$name] ?? 0) + (float)$svc->quantity;
                               }
                           }
                       }
                       $assignees = [];
                       foreach ($breakdown as $name => $qty) {
                           $assignees[] = $name . ' × ' . number_format((float)$qty, 2, '.', '');
                       }
                       $presetLabels = $o->remarkPresets->pluck('label')->filter()->values()->all();
                       fputcsv($out, [
                           $o->order_id,
                           optional($o->customer)->name,
                           optional($o->customer)->code,
                           optional($o->customer)->is_vip ? 'YES' : '',
                           $o->status,
                           number_format($o->total_cost, 2, '.', ''),
                           implode(', ', $assignees),
                           implode(', ', $presetLabels),
                           (string)($o->remarks ?? ''),
                           optional($o->created_at)->toDateTimeString(),
                       ]);
                   }
                   fclose($out);
               }, $filename, ['Content-Type' => 'text/csv']);
           }
           if (($validated['export'] ?? null) === 'xlsx') {
               $ordersForExport = (clone $query)->get();
               $rows = $ordersForExport->map(function ($o) {
                   // Build per-operator quantity breakdown across all services in the order
                   $breakdown = [];
                   foreach ($o->orderItems as $it) {
                       foreach ($it->orderItemServices as $svc) {
                           $assigns = $svc->relationLoaded('assignments') ? $svc->assignments : $svc->assignments()->with('employee')->get();
                           if ($assigns instanceof \Illuminate\Support\Collection ? $assigns->isNotEmpty() : (bool)count($assigns)) {
                               foreach ($assigns as $a) {
                                   $name = optional($a->employee)->name;
                                   if (!$name) { continue; }
                                   $breakdown[$name] = ($breakdown[$name] ?? 0) + (float)$a->quantity;
                               }
                           } elseif ($svc->employee) {
                               // Legacy fallback if no assignment rows exist
                               $name = $svc->employee->name;
                               $breakdown[$name] = ($breakdown[$name] ?? 0) + (float)$svc->quantity;
                           }
                       }
                   }
                   $assignees = [];
                   foreach ($breakdown as $name => $qty) {
                       $assignees[] = $name . ' × ' . number_format((float)$qty, 2, '.', '');
                   }
                   $presetLabels = $o->remarkPresets->pluck('label')->filter()->values()->all();
                   return [
                       $o->order_id,
                       optional($o->customer)->name,
                       optional($o->customer)->code,
                       optional($o->customer)->is_vip ? 'YES' : '',
                       $o->status,
                       number_format($o->total_cost, 2, '.', ''),
                       implode(', ', $assignees),
                       implode(', ', $presetLabels),
                       (string)($o->remarks ?? ''),
                       optional($o->created_at)->toDateTimeString(),
                   ];
               });
               return \App\Services\ExcelExportService::streamSimpleXlsx(
                   'orders_'.now()->format('Ymd_His').'.xlsx',
                   ['Order ID','Customer','Customer Code','VIP','Status','Total Cost','Assigned To','Remark Presets','Remarks','Created At'],
                   $rows
               );
           }

           // PDF direct download using TCPDF
           if (($validated['export'] ?? null) === 'pdf') {
               $rows = (clone $query)->get()->map(function ($o) {
                   // Build per-operator quantity breakdown across all services in the order
                   $breakdown = [];
                   foreach ($o->orderItems as $it) {
                       foreach ($it->orderItemServices as $svc) {
                           $assigns = $svc->relationLoaded('assignments') ? $svc->assignments : $svc->assignments()->with('employee')->get();
                           if ($assigns instanceof \Illuminate\Support\Collection ? $assigns->isNotEmpty() : (bool)count($assigns)) {
                               foreach ($assigns as $a) {
                                   $name = optional($a->employee)->name;
                                   if (!$name) { continue; }
                                   $breakdown[$name] = ($breakdown[$name] ?? 0) + (float)$a->quantity;
                               }
                           } elseif ($svc->employee) {
                               // Legacy fallback if no assignment rows exist
                               $name = $svc->employee->name;
                               $breakdown[$name] = ($breakdown[$name] ?? 0) + (float)$svc->quantity;
                           }
                       }
                   }
                   $assignees = [];
                   foreach ($breakdown as $name => $qty) {
                       $assignees[] = $name . ' × ' . number_format((float)$qty, 2, '.', '');
                   }
                   $presetLabels = $o->remarkPresets->pluck('label')->filter()->values()->all();
                   return [
                       'Order ID' => $o->order_id,
                       'Customer' => optional($o->customer)->name,
                       'Customer Code' => optional($o->customer)->code,
                       'VIP' => optional($o->customer)->is_vip ? 'YES' : '',
                       'Status' => $o->status,
                       'Total Cost' => number_format($o->total_cost, 2, '.', ''),
                       'Assigned To' => implode(', ', $assignees),
                       'Remark Presets' => implode(', ', $presetLabels),
                       'Remarks' => (string)($o->remarks ?? ''),
                       'Created At' => optional($o->created_at)->toDateTimeString(),
                   ];
               });
               return \App\Services\PdfExportService::streamSimpleTable(
                   'orders_'.now()->format('Ymd_His').'.pdf',
                   'Orders',
                   ['Order ID','Customer','Customer Code','VIP','Status','Total Cost','Assigned To','Remark Presets','Remarks','Created At'],
                   $rows
               );
           }

           // Print-friendly HTML view (optional separate action)
           if ($request->boolean('print')) {
               abort_unless(\Illuminate\Support\Facades\Gate::allows('print_orders'), 403);
               $rows = (clone $query)->get()->map(function ($o) {
                   // Build per-operator quantity breakdown across all services in the order
                   $breakdown = [];
                   foreach ($o->orderItems as $it) {
                       foreach ($it->orderItemServices as $svc) {
                           $assigns = $svc->relationLoaded('assignments') ? $svc->assignments : $svc->assignments()->with('employee')->get();
                           if ($assigns instanceof \Illuminate\Support\Collection ? $assigns->isNotEmpty() : (bool)count($assigns)) {
                               foreach ($assigns as $a) {
                                   $name = optional($a->employee)->name;
                                   if (!$name) { continue; }
                                   $breakdown[$name] = ($breakdown[$name] ?? 0) + (float)$a->quantity;
                               }
                           } elseif ($svc->employee) {
                               // Legacy fallback if no assignment rows exist
                               $name = $svc->employee->name;
                               $breakdown[$name] = ($breakdown[$name] ?? 0) + (float)$svc->quantity;
                           }
                       }
                   }
                   $assignees = [];
                   foreach ($breakdown as $name => $qty) {
                       $assignees[] = $name . ' × ' . number_format((float)$qty, 2, '.', '');
                   }
                   $presetLabels = $o->remarkPresets->pluck('label')->filter()->values()->all();
                   return [
                       'Order ID' => $o->order_id,
                       'Customer' => optional($o->customer)->name,
                       'Customer Code' => optional($o->customer)->code,
                       'VIP' => optional($o->customer)->is_vip ? 'YES' : '',
                       'Status' => $o->status,
                       'Total Cost' => number_format($o->total_cost, 2, '.', ''),
                       'Assigned To' => implode(', ', $assignees),
                       'Remark Presets' => implode(', ', $presetLabels),
                       'Remarks' => (string)($o->remarks ?? ''),
                       'Created At' => optional($o->created_at)->toDateTimeString(),
                   ];
               });
               return view('exports.simple_table', [
                   'title' => 'Orders',
                   'columns' => ['Order ID','Customer','Customer Code','VIP','Status','Total Cost','Assigned To','Remark Presets','Remarks','Created At'],
                   'rows' => $rows,
               ]);
           }

           $orders = $query
               ->paginate($perPage)
               ->appends(array_merge(
                   $request->only(['status','from_date','to_date','customer_id','operator_id']),
                   ['per_page' => $perPage]
               ));

           // Retrieve operators without relying on Role::findByName to exist in fresh DBs
           $operators = \App\Models\User::query()
               ->whereHas('roles', function ($q) {
                   $q->where('name', 'Operator');
               })
               ->orderBy('name')
               ->get();

           $customers = Customer::orderBy('name')->get();

           return view('orders.index', compact('orders','operators','customers'));
       }

       public function create()
       {
        $customers = Customer::all();
           // Only show cloth items that have at least one pricing tier to avoid error-prone selections
           $clothItems = ClothItem::with('unit')
               ->whereHas('pricingTiers')
               ->orderBy('name')
               ->get();
           $services = Service::all();
           $urgencyTiers = UrgencyTier::all();
           $units = Unit::all();
           return view('orders.create', compact('customers', 'clothItems', 'services', 'urgencyTiers', 'units'));
       }

       public function store(OrderStoreRequest $request)
       {
           $validated = $request->validated();
           try {
               $order = $this->orderService->createOrder($validated); // removed extra auth user param
               // Sync common remark presets selections
               if (!empty($validated['remark_preset_ids']) && is_array($validated['remark_preset_ids'])) {
                   $ids = array_values(array_unique(array_map('intval', $validated['remark_preset_ids'])));
                   $order->remarkPresets()->sync($ids);
               }
               return redirect()->route('orders.show', $order)->with('success', 'Order created successfully.');
           } catch (\Throwable $e) {
               Log::error('Order creation failed', [
                   'error' => $e->getMessage(),
                   'trace' => $e->getTraceAsString(),
                   'input' => $request->all(),
               ]);
               return back()
                   ->withInput()
                   ->withErrors(['general' => 'Order creation failed: ' . $e->getMessage()]);
           }
       }

         public function show(Order $order)
       {
    $order->load(['customer', 'createdBy', 'orderItems.clothItem.unit', 'orderItems.remarkPresets', 'orderItems.orderItemServices.service', 'orderItems.orderItemServices.employee', 'orderItems.orderItemServices.urgencyTier', 'orderItems.orderItemServices.assignments.employee', 'remarkPresets']);
    $operators = \App\Models\User::query()
        ->whereHas('roles', function ($q) {
            $q->where('name', 'Operator');
        })
        ->orderBy('name')
        ->get();
       return view('orders.show', compact('order', 'operators'));
       }

    public function invoice(Order $order)
    {
        abort_unless(\Illuminate\Support\Facades\Gate::allows('print_orders'), 403);
    $order->load(['customer', 'createdBy', 'orderItems.clothItem.unit', 'orderItems.remarkPresets', 'orderItems.orderItemServices.service', 'remarkPresets']);
        $company = [
            'name' => \App\Models\SystemSetting::getValue('company_name', config('app.name')),
            'address' => \App\Models\SystemSetting::getValue('company_address', ''),
            'phone' => \App\Models\SystemSetting::getValue('company_phone', ''),
            'email' => \App\Models\SystemSetting::getValue('company_email', ''),
            'tin' => \App\Models\SystemSetting::getValue('company_tin', ''),
            'company_vat_no' => \App\Models\SystemSetting::getValue('company_vat_no', ''),
            'logo_url' => \App\Models\SystemSetting::getValue('company_logo_url', ''),
            'footer' => \App\Models\SystemSetting::getValue('invoice_footer', ''),
            'stamp_url' => \App\Models\SystemSetting::getValue('company_stamp_url', ''),
        ];
        return view('orders.invoice', compact('order', 'company'));
    }

    public function invoicePdf(Order $order)
    {
        abort_unless(\Illuminate\Support\Facades\Gate::allows('export_orders'), 403);
    $order->load(['customer', 'createdBy', 'orderItems.clothItem.unit', 'orderItems.remarkPresets', 'orderItems.orderItemServices.service', 'payments','remarkPresets']);
        $company = [
            'name' => \App\Models\SystemSetting::getValue('company_name', config('app.name')),
            'address' => \App\Models\SystemSetting::getValue('company_address', ''),
            'phone' => \App\Models\SystemSetting::getValue('company_phone', ''),
            'email' => \App\Models\SystemSetting::getValue('company_email', ''),
            'tin' => \App\Models\SystemSetting::getValue('company_tin', ''),
            'company_vat_no' => \App\Models\SystemSetting::getValue('company_vat_no', ''),
            'logo_url' => \App\Models\SystemSetting::getValue('company_logo_url', ''),
            'footer' => \App\Models\SystemSetting::getValue('invoice_footer', ''),
            'stamp_url' => \App\Models\SystemSetting::getValue('company_stamp_url', ''),
        ];
        return \App\Services\PdfExportService::streamInvoicePdf($order, $company);
    }

       public function edit(Order $order)
       {
           
        $customers = Customer::all();
           // Only show cloth items that have at least one pricing tier
           $clothItems = ClothItem::with('unit')
               ->whereHas('pricingTiers')
               ->orderBy('name')
               ->get();
           $services = Service::all();
           $urgencyTiers = UrgencyTier::all();
           $units = Unit::all();
           
           $order->load(['customer', 'orderItems.clothItem.unit', 'orderItems.remarkPresets', 'orderItems.orderItemServices.service', 'orderItems.orderItemServices.urgencyTier']);
           
           return view('orders.edit', compact('order', 'customers', 'clothItems', 'services', 'urgencyTiers', 'units'));
       }

       public function update(OrderUpdateRequest $request, Order $order)
       {
           $validated = $request->validated();
           try {
               $this->orderService->updateOrder($order, $validated);
               // Sync common remark presets selections
               $ids = array_values(array_unique(array_map('intval', (array)($validated['remark_preset_ids'] ?? []))));
               $order->remarkPresets()->sync($ids);
               return redirect()->route('orders.show', $order)->with('success', 'Order updated successfully.');
           } catch (\Exception $e) {
               return back()->withInput()->withErrors(['error' => 'Failed to update order: ' . $e->getMessage()]);
           }
       }

       public function updateStatus(Request $request, Order $order)
       {
           
        $request->validate([
               'status' => 'required|in:received,processing,washing,drying_steaming,ironing,packaging,ready_for_pickup,delivered,cancelled',
           ]);

           try {
               $oldStatus = $order->status;
               $this->orderService->updateOrderStatus($order, $request->status);
               
               // Create notification for status change
               $this->notificationService->createOrderStatusNotification($order, $oldStatus, $request->status);

               return redirect()->route('orders.show', $order)
                   ->with('success', 'Order status updated successfully.');
           } catch (\Exception $e) {
               return back()->withErrors(['error' => 'Failed to update order status: ' . $e->getMessage()]);
           }
       }

       public function destroy(Order $order)
       {
           
        // Implementation for deleting order
           // This would need to handle order items and services
           
           return redirect()->route('orders.index')
               ->with('success', 'Order deleted successfully.');
       }
   }