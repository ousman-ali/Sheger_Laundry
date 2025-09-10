<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItemPenalty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderPenaltyController extends Controller
{
    public function __construct(private \App\Services\NotificationService $notifications)
    {
        // Reuse order permissions: view/edit orders; approvals restricted to Admin role
        $this->middleware('permission:view_orders')->only(['store']);
        $this->middleware('permission:edit_orders')->only(['store','waive','destroy']);
        $this->middleware('role:Admin')->only(['approve']);
    }

    public function store(Request $request, Order $order)
    {
        $validated = $request->validate([
            'order_item_service_id' => 'nullable|exists:order_item_services,id',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'nullable|string|max:500',
        ]);

        $penalty = OrderItemPenalty::create([
            'order_id' => $order->id,
            'order_item_service_id' => $validated['order_item_service_id'] ?? null,
            'amount' => (float)$validated['amount'],
            'waived' => false,
            'waiver_reason' => $validated['reason'] ?? null,
            'requires_approval' => false,
            'created_by' => Auth::id(),
        ]);

        // Activity log (best effort)
        try {
            \App\Models\ActivityLog::create([
                'user_id' => (int)Auth::id(),
                'action' => 'penalty_create',
                'subject_type' => OrderItemPenalty::class,
                'subject_id' => $penalty->id,
                'changes' => [
                    'order_id' => $order->id,
                    'amount' => $penalty->amount,
                ],
            ]);
        } catch (\Throwable $_) { /* ignore */ }

        return redirect()->route('orders.show', $order)->with('success', 'Penalty added.');
    }

    public function waive(Request $request, OrderItemPenalty $penalty)
    {
        $data = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

    /** @var \App\Models\User|null $user */
    $user = Auth::user();
    $isAdmin = $user && $user->hasRole('Admin');
        if ($isAdmin) {
            $penalty->update([
                'waived' => true,
                'waiver_reason' => $data['reason'] ?? $penalty->waiver_reason,
                'requires_approval' => false,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);
        } else {
            $penalty->update([
                'waiver_reason' => $data['reason'] ?? $penalty->waiver_reason,
                'requires_approval' => true,
            ]);
            // Notify Admins
            try {
                $msg = sprintf('Penalty waiver requested for Order %s (%.2f ETB).', $penalty->order?->order_id, (float)$penalty->amount);
                $this->notifications->notifyAdmins('penalty_approval', $msg, route('orders.show', $penalty->order));
            } catch (\Throwable $_) { /* ignore */ }
        }

        try {
            \App\Models\ActivityLog::create([
                'user_id' => (int)$user->id,
                'action' => $isAdmin ? 'penalty_waived' : 'penalty_waiver_requested',
                'subject_type' => OrderItemPenalty::class,
                'subject_id' => $penalty->id,
                'changes' => [
                    'requires_approval' => $penalty->requires_approval,
                    'waived' => $penalty->waived,
                ],
            ]);
        } catch (\Throwable $_) { /* ignore */ }

        return back()->with('success', $isAdmin ? 'Penalty waived.' : 'Waiver requested for approval.');
    }

    public function approve(OrderItemPenalty $penalty)
    {
        $user = Auth::user();
        // Admin only (enforced via middleware), approve a pending waiver
        if (!$penalty->requires_approval) {
            return back();
        }
        $penalty->update([
            'waived' => true,
            'requires_approval' => false,
            'approved_by' => $user?->id,
            'approved_at' => now(),
        ]);
        try {
            $msg = sprintf('Penalty waiver approved for Order %s (%.2f ETB).', $penalty->order?->order_id, (float)$penalty->amount);
            $this->notifications->notifyAdmins('penalty', $msg, route('orders.show', $penalty->order));
        } catch (\Throwable $_) { /* ignore */ }
        try {
            \App\Models\ActivityLog::create([
                'user_id' => (int)($user?->id),
                'action' => 'penalty_approval',
                'subject_type' => OrderItemPenalty::class,
                'subject_id' => $penalty->id,
                'changes' => [
                    'approved_by' => $user?->id,
                ],
            ]);
        } catch (\Throwable $_) { /* ignore */ }
        return back()->with('success', 'Penalty waiver approved.');
    }

    public function destroy(OrderItemPenalty $penalty)
    {
    $order = $penalty->order;
        $penalty->delete();
        try {
            \App\Models\ActivityLog::create([
                'user_id' => (int)Auth::id(),
                'action' => 'penalty_delete',
                'subject_type' => OrderItemPenalty::class,
                'subject_id' => $penalty->id,
                'changes' => [ 'order_id' => $order->id ],
            ]);
        } catch (\Throwable $_) { /* ignore */ }
        return back()->with('success', 'Penalty removed.');
    }
}
