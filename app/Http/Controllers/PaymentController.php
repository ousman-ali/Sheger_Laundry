<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Order;
use App\Services\PaymentService;
use App\Services\ExcelExportService;
use App\Services\PdfExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $payments)
    {
        $this->middleware('permission:view_payments')->only(['index', 'show']);
        $this->middleware('permission:create_payments')->only(['create', 'store']);
        $this->middleware('permission:edit_payments')->only(['edit', 'update', 'approve', 'refund']);
        $this->middleware('permission:delete_payments')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
            'order_id' => 'nullable|exists:orders,id',
            'status' => 'nullable|in:pending,completed,refunded',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'per_page' => 'nullable|integer|in:10,25,50,100',
            'sort' => 'nullable|in:paid_at,amount,created_at',
            'direction' => 'nullable|in:asc,desc',
            'export' => 'nullable|in:csv,xlsx,pdf',
        ]);

        $perPage = (int)($validated['per_page'] ?? $request->session()->get('payments.per_page', 10));
        $request->session()->put('payments.per_page', $perPage);

        $query = Payment::with(['order','createdBy'])
            ->when(($validated['q'] ?? null), function ($q) use ($validated) {
                $term = $validated['q'];
                $q->whereHas('order', fn($w)=>$w->where('order_id','like', "%{$term}%"))
                  ->orWhereHas('createdBy', fn($w)=>$w->where('name','like', "%{$term}%"));
            })
            ->when(($validated['order_id'] ?? null), fn($q,$id)=>$q->where('order_id',$id))
            ->when(($validated['status'] ?? null), fn($q,$s)=>$q->where('status',$s))
            ->when(($validated['from_date'] ?? null), fn($q,$d)=>$q->whereDate('paid_at','>=',$d))
            ->when(($validated['to_date'] ?? null), fn($q,$d)=>$q->whereDate('paid_at','<=',$d));

        $sort = $validated['sort'] ?? 'paid_at';
        $direction = $validated['direction'] ?? 'desc';
        $query->orderBy($sort, $direction);

        if (!empty($validated['export'] ?? null)) {
            abort_unless(\Illuminate\Support\Facades\Gate::allows('export_payments'), 403);
        }
        if (($validated['export'] ?? null) === 'csv') {
            $rows = (clone $query)->get();
            $filename = 'payments_' . now()->format('Ymd_His') . '.csv';
            return response()->streamDownload(function () use ($rows) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Order','Amount','Method','Status','Paid At','By']);
                foreach ($rows as $r) {
                    fputcsv($out, [
                        optional($r->order)->order_id,
                        number_format((float)$r->amount, 2, '.', ''),
                        $r->method,
                        $r->status,
                        $r->paid_at,
                        optional($r->createdBy)->name,
                    ]);
                }
                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv']);
        }
        if (($validated['export'] ?? null) === 'xlsx') {
            $rows = (clone $query)->get()->map(fn($r)=>[
                optional($r->order)->order_id,
                number_format((float)$r->amount, 2, '.', ''),
                $r->method,
                $r->status,
                $r->paid_at,
                optional($r->createdBy)->name,
            ]);
            return ExcelExportService::streamSimpleXlsx(
                'payments_'.now()->format('Ymd_His').'.xlsx',
                ['Order','Amount','Method','Status','Paid At','By'],
                $rows
            );
        }
        if (($validated['export'] ?? null) === 'pdf') {
            $rows = (clone $query)->get()->map(fn($r)=>[
                'Order' => optional($r->order)->order_id,
                'Amount' => number_format((float)$r->amount, 2, '.', ''),
                'Method' => $r->method,
                'Status' => $r->status,
                'Paid At' => $r->paid_at,
                'By' => optional($r->createdBy)->name,
            ]);
            return PdfExportService::streamSimpleTable(
                'payments_'.now()->format('Ymd_His').'.pdf',
                'Payments',
                ['Order','Amount','Method','Status','Paid At','By'],
                $rows
            );
        }
        if ($request->boolean('print')) {
            abort_unless(\Illuminate\Support\Facades\Gate::allows('print_payments'), 403);
            $rows = (clone $query)->get()->map(fn($r)=>[
                'Order' => optional($r->order)->order_id,
                'Amount' => number_format((float)$r->amount, 2, '.', ''),
                'Method' => $r->method,
                'Status' => $r->status,
                'Paid At' => $r->paid_at,
                'By' => optional($r->createdBy)->name,
            ]);
            return view('exports.simple_table', [
                'title' => 'Payments',
                'columns' => ['Order','Amount','Method','Status','Paid At','By'],
                'rows' => $rows,
            ]);
        }

        $payments = $query->paginate($perPage)
            ->appends(array_merge($request->only(['q','order_id','status','from_date','to_date','sort','direction']), ['per_page' => $perPage]));

        return view('payments.index', compact('payments', 'sort', 'direction'));
    }

    public function suggest(Request $request)
    {
        $request->validate(['order_id' => 'required|exists:orders,id']);
        return response()->json($this->payments->suggestedAmountForOrder((int)$request->order_id));
    }

    public function orderSearch(Request $request)
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);
        $limit = (int)($validated['limit'] ?? 50);
            $q = Order::with('customer')
            ->when(($validated['q'] ?? null), function ($w) use ($validated) {
                $term = $validated['q'];
                $w->where('order_id', 'like', "%{$term}%")
                  ->orWhereHas('customer', function ($c) use ($term) {
                      $c->where('name', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%");
                  });
            })
            ->when(($validated['from_date'] ?? null), fn($w,$d)=>$w->whereDate('created_at','>=',$d))
            ->when(($validated['to_date'] ?? null), fn($w,$d)=>$w->whereDate('created_at','<=',$d))
            ->orderByDesc('id')
            ->limit($limit)
                ->get()
                ->map(function ($o) {
                    $label = sprintf('%s — %s — %s', $o->order_id, optional($o->customer)->name, optional($o->created_at)->format('Y-m-d'));
                    try {
                        $suggest = app(\App\Services\PaymentService::class)->suggestedAmountForOrder($o->id);
                    } catch (\Throwable $e) {
                        $suggest = ['penalty' => 0];
                    }
                    return [
                        'id' => $o->id,
                        'label' => $label,
                        'has_penalty' => (float)($suggest['penalty'] ?? 0) > 0,
                    ];
                });
        return response()->json($q);
    }

    public function create()
    {
        $orders = Order::orderByDesc('id')->limit(50)->get();
        return view('payments.create', compact('orders'));
    }

    public function store(Request $request)
    {
        // Normalize checkbox to real boolean before validation (checkbox sends 'on')
        $request->merge(['waived_penalty' => $request->boolean('waived_penalty')]);
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string|in:cash,bank',
            'bank_id' => 'nullable|required_if:payment_method,bank|exists:banks,id',
            'status' => 'nullable|in:pending,completed,refunded',
            'paid_at' => 'nullable|date',
            'notes' => 'nullable|string',
            'waived_penalty' => 'boolean',
            'waiver_reason' => 'nullable|string|max:500',
        ]);

        if ($request->boolean('waived_penalty') && empty($validated['waiver_reason'])) {
            return back()->withErrors(['waiver_reason' => 'Reason is required when requesting to waive penalty.'])->withInput();
        }

    try {
            $payload = [
                'order_id' => (int)$validated['order_id'],
                'amount' => (float)$validated['amount'],
                'method' => $validated['payment_method'] ?? null,
                'status' => $validated['status'] ?? 'completed',
                'paid_at' => $validated['paid_at'] ?? now(),
                'notes' => $validated['notes'] ?? null,
                'created_by' => Auth::id(),
                'waived_penalty' => (bool)$validated['waived_penalty'],
                'waiver_reason' => $validated['waiver_reason'] ?? null,
                'bank_id' => $validated['bank_id'] ?? null,
            ];
            // If a waiver is requested and there is actual penalty, force status to pending (cannot be created completed)
            $suggest = $this->payments->suggestedAmountForOrder((int)$validated['order_id']);
            $penalty = (float)($suggest['penalty'] ?? 0);
            if ($payload['waived_penalty'] && $penalty > 0) {
                $payload['status'] = 'pending';
                // mark requires approval for non-admin creators
                $u = Auth::user();
                $isAdmin = $u instanceof \App\Models\User && method_exists($u, 'hasRole') ? $u->hasRole('Admin') : false;
                $payload['requires_approval'] = !$isAdmin;
            }
            $payment = $this->payments->create($payload);
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }

        $redirect = $request->string('redirect')->toString();
        if (!empty($redirect)) {
            return redirect($redirect)->with('success', 'Payment recorded.');
        }
        return redirect()->route('payments.index')->with('success', 'Payment recorded.');
    }

    public function show(Payment $payment)
    {
        return view('payments.show', compact('payment'));
    }

    public function approve(Payment $payment)
    {
        $updated = $this->payments->approve($payment->id, (int)\Illuminate\Support\Facades\Auth::id());
        return redirect()->route('payments.show', $updated)->with('success', 'Payment approved.');
    }

    public function edit(Payment $payment)
    {
        return view('payments.edit', compact('payment'));
    }

    public function update(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string|max:50',
            'status' => 'required|in:pending,completed,refunded',
            'paid_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);
        // Business rules around edits/approval
        // 1) If payment requires approval (waiver pending), block edits except maybe notes until approved
        if ($payment->requires_approval) {
            return back()->withErrors(['error' => 'This payment is awaiting Admin approval due to penalty waiver. You cannot change it until it is approved.'])->withInput();
        }
        // 2) If payment had a waived penalty, allow receptionist to mark completed only AFTER approved (requires_approval=false)
        //    If waived_penalty is true and not approved, the previous rule already blocked. Here, allowed to change status.
        try {
            $payment->update([
                'amount' => (float)$validated['amount'],
                'method' => $validated['payment_method'] ?? null,
                'status' => $validated['status'],
                'paid_at' => $validated['paid_at'] ?? $payment->paid_at,
                'notes' => $validated['notes'] ?? null,
            ]);
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
        return redirect()->route('payments.index')->with('success', 'Payment updated.');
    }

    public function refund(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'nullable|string|max:500',
            'idempotency_key' => 'nullable|string|max:100',
        ]);
        try {
            $ref = $this->payments->refund($payment->id, (float)$validated['amount'], (int)Auth::id(), $validated['reason'] ?? null, [], $validated['idempotency_key'] ?? null);
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
        return redirect()->route('payments.show', $ref)->with('success', 'Refund recorded.');
    }

    public function reverse(Request $request, Payment $payment)
    {
        // Admin-only via route middleware
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);
        try {
            $rev = $this->payments->reverse($payment->id, (int)Auth::id(), $validated['reason'] ?? null);
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
        return redirect()->route('payments.show', $rev)->with('success', 'Payment reversed.');
    }
}
