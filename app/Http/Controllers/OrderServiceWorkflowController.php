<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OrderService as DomainOrderService;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use App\Models\OrderItemService;

class OrderServiceWorkflowController extends Controller
{
    public function __construct(private DomainOrderService $orderService)
    {
    // Assignments need assign_service; updates allowed for Operators or update_service_status
    $this->middleware('permission:assign_service')->only(['assign','assignByOrders','assignByItems','assignByCustomers']);
    $this->middleware('role_or_permission:Operator|update_service_status')->only(['updateStatus','updateAssignmentStatus']);
    }

    public function assign(Request $request)
    {
        $data = $request->validate([
            'service_ids' => 'required|array|min:1',
            'service_ids.*' => 'integer|exists:order_item_services,id',
            'employee_id' => 'nullable|integer|exists:users,id',
            'quantity' => 'nullable|numeric|min:0.01',
        ]);
        if (isset($data['employee_id'])) {
            $this->ensureOperator($data['employee_id']);
        }
        $count = $this->orderService->bulkAssignEmployees($data['service_ids'], $data['employee_id'] ?? null, $data['quantity'] ?? null);
        return back()->with('success', "Assigned employee to {$count} services.");
    }

    public function updateStatus(Request $request)
    {
        $data = $request->validate([
            'service_ids' => 'required|array|min:1',
            'service_ids.*' => 'integer|exists:order_item_services,id',
            'status' => 'required|string|in:pending,assigned,in_progress,completed,on_hold,cancelled',
        ]);
        $newStatus = $data['status'];

        // Pre-validate workflow to provide a friendly error instead of 500
        $services = OrderItemService::query()
            ->whereIn('id', $data['service_ids'])
            ->get(['id','status']);

        $workflow = config('shebar.service_status_workflow', []);
        $invalid = [];

        foreach ($services as $svc) {
            // Idempotent change is okay
            if ($svc->status === $newStatus) { continue; }
            $allowed = $workflow[$svc->status] ?? [];
            if (!in_array($newStatus, $allowed, true)) {
                $invalid[$svc->status] = $allowed; // group by current status
            }
        }

        if (!empty($invalid)) {
            $human = function(string $s): string { return ucwords(str_replace('_',' ', $s)); };
            $attempt = $human($newStatus);
            // Build a concise, human-friendly message listing allowed next steps
            $parts = [];
            foreach ($invalid as $from => $allowed) {
                $fromH = $human($from);
                $allowedH = empty($allowed) ? 'No further steps' : implode(', ', array_map($human, $allowed));
                $parts[] = "from {$fromH}: {$allowedH}";
            }
            $detail = implode('; ', $parts);
            $msg = "You can’t change status directly to {$attempt}. Allowed next steps are {$detail}.";
            return back()->with('error', $msg);
        }

        // All good — perform the update
        try {
            $count = $this->orderService->bulkUpdateServiceStatus($data['service_ids'], $newStatus);
            return back()->with('success', "Updated status for {$count} services.");
        } catch (\InvalidArgumentException $e) {
            // In case of a race where statuses changed between validation and update
            $msg = 'That change is not allowed by the service workflow. Please follow the next allowed step(s).';
            return back()->with('error', $msg);
        }
    }

    public function assignByOrders(Request $request)
    {
        $data = $request->validate([
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'integer|exists:orders,id',
            'employee_id' => 'nullable|integer|exists:users,id',
            'quantity' => 'nullable|numeric|min:0.01',
        ]);
        if (isset($data['employee_id'])) {
            $this->ensureOperator($data['employee_id']);
        }
        $count = $this->orderService->assignByOrderIds($data['order_ids'], $data['employee_id'] ?? null, $data['quantity'] ?? null);
        return back()->with('success', "Assigned employee to {$count} services (by orders).");
    }

    public function assignByItems(Request $request)
    {
        $data = $request->validate([
            'order_item_ids' => 'required|array|min:1',
            'order_item_ids.*' => 'integer|exists:order_items,id',
            'employee_id' => 'nullable|integer|exists:users,id',
            'quantity' => 'nullable|numeric|min:0.01',
        ]);
        if (isset($data['employee_id'])) {
            $this->ensureOperator($data['employee_id']);
        }
        $count = $this->orderService->assignByOrderItemIds($data['order_item_ids'], $data['employee_id'] ?? null, $data['quantity'] ?? null);
        return back()->with('success', "Assigned employee to {$count} services (by items).");
    }

    public function assignByCustomers(Request $request)
    {
        $data = $request->validate([
            'customer_ids' => 'required|array|min:1',
            'customer_ids.*' => 'integer|exists:customers,id',
            'employee_id' => 'nullable|integer|exists:users,id',
            'quantity' => 'nullable|numeric|min:0.01',
        ]);
        if (isset($data['employee_id'])) {
            $this->ensureOperator($data['employee_id']);
        }
        $count = $this->orderService->assignByCustomerIds($data['customer_ids'], $data['employee_id'] ?? null, $data['quantity'] ?? null);
        return back()->with('success', "Assigned employee to {$count} services (by customers).");
    }

    private function ensureOperator(int $userId): void
    {
        $user = User::find($userId);
    if (!$user || !$user->hasRole('Operator')) {
            throw ValidationException::withMessages([
                'employee_id' => 'Selected user is not an operator.',
            ]);
        }
    }

    public function updateAssignmentStatus(Request $request)
    {
        $data = $request->validate([
            'assignment_ids' => 'required|array|min:1',
            'assignment_ids.*' => 'integer|exists:order_service_assignments,id',
            'status' => 'required|string|in:assigned,in_progress,completed,on_hold,cancelled',
        ]);
        /** @var \App\Models\User|null $user */
        $user = \Illuminate\Support\Facades\Auth::user();
        // Operators may only update their own assignments; Admins or users with update_service_status may update any
        $canBroad = $user && ($user->can('update_service_status') || $user->hasRole('Admin'));

        $desired = $data['status'];
        $workflow = config('shebar.service_status_workflow', []);

        $assignments = \App\Models\OrderServiceAssignment::with('orderItemService.orderItem.order')
            ->whereIn('id', $data['assignment_ids'])
            ->get();

        // Filter to only those the user is allowed to change
        $target = $assignments->filter(function($a) use ($canBroad, $user){
            return $canBroad || ((int)$a->employee_id === (int)($user?->id));
        });

        // Pre-validate workflow for assignment status (same workflow as service status)
        $invalid = [];
        foreach ($target as $a) {
            if ($a->status === $desired) { continue; }
            $allowed = $workflow[$a->status] ?? [];
            if (!in_array($desired, $allowed, true)) {
                $invalid[$a->status] = $allowed;
            }
        }

        if (!empty($invalid)) {
            $human = function(string $s): string { return ucwords(str_replace('_',' ', $s)); };
            $attempt = $human($desired);
            $parts = [];
            foreach ($invalid as $from => $allowed) {
                $fromH = $human($from);
                $allowedH = empty($allowed) ? 'No further steps' : implode(', ', array_map($human, $allowed));
                $parts[] = "from {$fromH}: {$allowedH}";
            }
            $detail = implode('; ', $parts);
            $msg = "You can’t change assignment status directly to {$attempt}. Allowed next steps are {$detail}.";
            return back()->with('error', $msg);
        }

        // Apply updates
        $count = 0;
        foreach ($target as $a) {
            $a->status = $desired;
            $a->save();
            $count++;
        }
        return back()->with('success', "Updated status for {$count} assignments.");
    }
}
