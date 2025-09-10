<?php

   namespace App\Http\Controllers;

    use App\Models\Customer;
    use App\Services\CustomerService;
   use Illuminate\Http\Request;

   class CustomerController extends Controller
   {
       public function __construct()
       {
           $this->middleware('permission:view_customers')->only(['index', 'show']);
           $this->middleware('permission:create_customers')->only(['create', 'store']);
           $this->middleware('permission:edit_customers')->only(['edit', 'update']);
           $this->middleware('permission:delete_customers')->only(['destroy']);
       }
    

       public function index(Request $request)
       {
           $validated = $request->validate([
               'per_page' => 'nullable|integer|in:10,25,50,100',
               'q' => 'nullable|string|max:255',
               'sort' => 'nullable|in:name,code,phone,created_at',
               'direction' => 'nullable|in:asc,desc',
               'export' => 'nullable|in:csv,xlsx,pdf',
           ]);

           // Sticky per_page
           if (!empty($validated['per_page'] ?? null)) {
               $perPage = (int) $validated['per_page'];
               $request->session()->put('customers.per_page', $perPage);
           } else {
               $perPage = (int) $request->session()->get('customers.per_page', 10);
           }

           $query = Customer::query();
           if (!empty($validated['q'] ?? null)) {
               $q = $validated['q'];
               $query->where(function ($sub) use ($q) {
                   $sub->where('name', 'like', "%{$q}%")
                       ->orWhere('code', 'like', "%{$q}%")
                       ->orWhere('phone', 'like', "%{$q}%")
                       ->orWhere('address', 'like', "%{$q}%");
               });
           }

           $sort = $validated['sort'] ?? 'name';
           $direction = $validated['direction'] ?? 'asc';
           $query->orderBy($sort, $direction);

           // Exports
           if (!empty($validated['export'] ?? null)) {
               abort_unless(\Illuminate\Support\Facades\Gate::allows('export_customers'), 403);
           }
           if (($validated['export'] ?? null) === 'csv') {
               $filename = 'customers_' . now()->format('Ymd_His') . '.csv';
               $rows = (clone $query)->get();
               return response()->streamDownload(function () use ($rows) {
                   $out = fopen('php://output', 'w');
                   fputcsv($out, ['Name','Code','VIP','Phone','Address','Created At']);
                   foreach ($rows as $r) {
                       fputcsv($out, [
                           $r->name,
                           $r->code,
                           $r->is_vip ? 'YES' : '',
                           $r->phone,
                           $r->address,
                           optional($r->created_at)->toDateTimeString(),
                       ]);
                   }
                   fclose($out);
               }, $filename, ['Content-Type' => 'text/csv']);
           }
           if (($validated['export'] ?? null) === 'xlsx') {
               $rows = (clone $query)->get()->map(fn($r) => [
                   $r->name,
                   $r->code,
                   $r->is_vip ? 'YES' : '',
                   $r->phone,
                   $r->email,
                   $r->address,
                   optional($r->created_at)->toDateTimeString(),
               ]);
               return \App\Services\ExcelExportService::streamSimpleXlsx(
                   'customers_'.now()->format('Ymd_His').'.xlsx',
                   ['Name','Code','VIP','Phone','Email','Address','Created At'],
                   $rows
               );
           }
           if (($validated['export'] ?? null) === 'pdf') {
               $rows = (clone $query)->get()->map(fn($r) => [
                   'Name' => $r->name,
                   'Code' => $r->code,
                   'VIP' => $r->is_vip ? 'YES' : '',
                   'Phone' => $r->phone,
                   'Address' => $r->address,
                   'Created At' => optional($r->created_at)->toDateTimeString(),
               ]);
               return \App\Services\PdfExportService::streamSimpleTable(
                   'customers_'.now()->format('Ymd_His').'.pdf',
                   'Customers',
                   ['Name','Code','VIP','Phone','Address','Created At'],
                   $rows
               );
           }

           if ($request->boolean('print')) {
               abort_unless(\Illuminate\Support\Facades\Gate::allows('print_customers'), 403);
               $rows = (clone $query)->get()->map(fn($r) => [
                   'Name' => $r->name,
                   'Code' => $r->code,
                   'VIP' => $r->is_vip ? 'YES' : '',
                   'Phone' => $r->phone,
                   'Address' => $r->address,
                   'Created At' => optional($r->created_at)->toDateTimeString(),
               ]);
               return view('exports.simple_table', [
                   'title' => 'Customers',
                   'columns' => ['Name','Code','VIP','Phone','Address','Created At'],
                   'rows' => $rows,
               ]);
           }

           $customers = $query->paginate($perPage)
               ->appends(array_merge(
                   $request->only(['q','sort','direction']),
                   ['per_page' => $perPage]
               ));

           return view('customers.index', compact('customers', 'sort', 'direction'));
       }

       public function create()
       {
           return view('customers.create');
       }

    /**
     * AJAX search for customers used by the order form (returns small payload)
     */
    public function search(Request $request)
    {
        $q = $request->query('q', '');
        $rows = Customer::query()
            ->when($q, fn($qbuilder) => $qbuilder->where('name', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%"))
            ->limit(20)
            ->get(['id','name','phone']);

        return response()->json($rows);
    }

       public function store(Request $request)
       {
           $validated = $request->validate([
               'name' => 'required|string|max:255',
               'phone' => 'required|string|max:20',
               'address' => 'nullable|string',
               'is_vip' => 'sometimes|boolean',
               'code' => 'nullable|string|max:50|unique:customers,code',
           ]);

        $isVip = (bool)($validated['is_vip'] ?? false);
        $code = $validated['code'] ?? null;
        if (!$code && config('shebar.auto_generate_customer_code', true)) {
            $code = app(CustomerService::class)->generateCustomerCode($validated['name'] ?? null, $isVip);
        }

        $customer = Customer::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'address' => $validated['address'] ?? null,
            'is_vip' => $isVip,
            'code' => $code,
        ]);

        // If AJAX request, return JSON so client can insert without redirect
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'id' => $customer->id,
                'label' => $customer->name . ' (' . $customer->phone . ')'
            ], 201);
        }

        return redirect()->route('customers.index')->with('success', 'Customer created successfully.');
       }

       /**
        * Show the form for editing the specified resource.
        */
       public function edit(Customer $customer)
       {
           return view('customers.edit', compact('customer'));
       }

       /**
        * Update the specified resource in storage.
        */
       public function update(Request $request, Customer $customer)
       {
           $validated = $request->validate([
               'name' => 'required|string|max:255',
               'phone' => 'required|string|max:20',
               'address' => 'nullable|string',
               'is_vip' => 'sometimes|boolean',
               'code' => 'nullable|string|max:50|unique:customers,code,'.$customer->id,
           ]);

           $payload = [
               'name' => $validated['name'],
               'phone' => $validated['phone'],
               'address' => $validated['address'] ?? null,
               'is_vip' => (bool)($validated['is_vip'] ?? $customer->is_vip),
           ];
           if (array_key_exists('code', $validated)) {
               $payload['code'] = $validated['code'];
           }

           $customer->update($payload);

           return redirect()->route('customers.index')->with('success', 'Customer updated successfully.');
       }
   }