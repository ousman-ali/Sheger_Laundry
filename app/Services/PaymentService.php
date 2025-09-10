<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class PaymentService
{
    public function __construct(private NotificationService $notifications)
    {
    }

    /**
     * Suggest amounts for an order:
     * - base: order total_cost
     * - penalty: explicit order->penalty_amount if set, else computed from daily rate and pickup date
     * - total: base + penalty
     */

    public function suggestedAmountForOrder(int $orderId): array
    {
        $order = Order::findOrFail($orderId);
        // Penalty precedence:
        // 1) If order has explicit penalty_amount > 0, use it.
        // 2) Else, if past pickup date and a daily rate exists, compute days late * daily rate.
        // 0) Itemized penalties take precedence when present (sum of non-waived)
        $itemizedPenalty = (float) ($order->itemPenalties()->where('waived', false)->sum('amount'));
        $explicitPenalty = (float)($order->penalty_amount ?? 0);
        $penalty = $itemizedPenalty > 0
            ? $itemizedPenalty
            : ($explicitPenalty > 0
                ? $explicitPenalty
                : (function () use ($order) {
                // Enable toggle and grace days
                $enabled = (int)(optional(SystemSetting::where('key','penalties_enabled')->first())->value ?? 1);
                if ($enabled !== 1) { return 0.0; }
                $grace = (int)(optional(SystemSetting::where('key','penalty_grace_days')->first())->value ?? 0);
                // Determine per-day rate: prefer service-level max override across services in the order
                $defaultRate = (float)($order->penalty_daily_rate
                    ?? optional(SystemSetting::where('key','penalty_daily_rate')->first())->value
                    ?? config('shebar.penalty_daily_rate', 0));
                $perDay = $defaultRate;
                try {
                    $serviceIds = $order->orderItemServices()->pluck('service_id')->unique()->all();
                    $overrides = [];
                    foreach ($serviceIds as $sid) {
                        $v = optional(SystemSetting::where('key','penalty_rate_service_'.$sid)->first())->value;
                        if ($v !== null && $v !== '') { $overrides[] = (float)$v; }
                    }
                    if (!empty($overrides)) { $perDay = max($overrides); }
                } catch (\Throwable $_) { /* ignore */ }
                if (!empty($order->pickup_date) && $perDay > 0) {
                    // Signed diff: positive when now is after pickup_date + grace
                    $start = $order->pickup_date->copy()->startOfDay()->addDays(max(0,$grace));
                    $daysLate = $start->diffInDays(now()->startOfDay(), false);
                    return max(0, $daysLate) * $perDay;
                }
                return 0.0;
            })());
    // Treat total_cost as (base + explicit penalty_amount). Derive base to avoid double counting.
    $base = max(0.0, (float)($order->total_cost ?? 0) - (float)$explicitPenalty);
    $total = $base + $penalty;
    $paid = (float) (Payment::query()->where('order_id',$order->id)->where('status','completed')->sum('amount'));
    $due = max(0.0, (float)$total - (float)$paid);
        return [
            'base' => $base,
            'penalty' => $penalty,
            'total' => $total,
            'paid' => $paid,
            'due' => $due,
        ];
    }

    public function create(array $data): Payment
    {
        return DB::transaction(function () use ($data) {
            $order = Order::findOrFail($data['order_id']);
            // Compute suggestion and check waivers
            $suggestion = $this->suggestedAmountForOrder($order->id);
            $waivedPenalty = (bool)($data['waived_penalty'] ?? false);
            $amount = (float)$data['amount'];
            $expectedTotal = (float)$suggestion['total'];
            $penaltyAmount = (float)$suggestion['penalty'];
            // Consider either explicit waiver checkbox or implicit underpayment as a waiver intent
            $isWaiverIntent = $waivedPenalty || ($penaltyAmount > 0 && $amount < ($expectedTotal - 0.005));
            if ($isWaiverIntent) {
                $waivedPenalty = true;
            }
            $requiresApproval = (bool)($data['requires_approval'] ?? false);
            $approvedBy = null;
            $approvedAt = null;

            // If receptionist tries to waive penalty while there is a penalty amount, require Admin approval
            if ($isWaiverIntent) {
                // mark requires approval when creator is not Admin
                $creatorId = (int)$data['created_by'];
                $creator = User::find($creatorId);
                $isAdmin = $creator && $creator->hasRole('Admin');
                if (!$isAdmin) {
                    $requiresApproval = true;
                }
            }

            // ensure ledger exists
            $ledger = $order->paymentLedger ?? $order->paymentLedger()->create([
                'total_amount' => (float)($order->total_cost ?? 0),
                'currency' => 'ETB',
            ]);

            // Overpay guard: compute due from expected total (base + penalty) minus completed payments
            try { $ledger->recalc(); } catch (\Throwable $_) { /* ignore */ }
            $paidCompleted = (float)$order->payments()->where('status','completed')->sum('amount');
            $due = max(0.0, (float)$expectedTotal - $paidCompleted);
            if ($amount > $due + 0.005) {
                throw new \DomainException(sprintf(
                    'Payment exceeds due amount. Due: %.2f ETB, Provided: %.2f ETB.',
                    $due,
                    $amount
                ));
            }

            $payment = Payment::create([
                'order_id' => $order->id,
                'payment_ledger_id' => $ledger->id,
                'amount' => $amount,
                'method' => $data['method'] ?? null,
                'bank_id' => $data['bank_id'] ?? null,
                // Force pending for any waiver intent; otherwise honor provided status or default
                'status' => $isWaiverIntent ? 'pending' : ($data['status'] ?? 'completed'),
                'paid_at' => $data['paid_at'] ?? now(),
                'notes' => $data['notes'] ?? null,
                'created_by' => $data['created_by'],
                'waived_penalty' => $waivedPenalty,
                'waiver_reason' => $data['waiver_reason'] ?? null,
                'requires_approval' => $requiresApproval,
                'approved_by' => $approvedBy,
                'approved_at' => $approvedAt,
            ]);

            // Notify Admins (all) for approvals or payment records
            try {
                if ($requiresApproval) {
                    $msg = sprintf('Payment %.2f for Order %s requires approval (waiver requested).', (float)$payment->amount, $order->order_id);
                    $this->notifications->notifyAdmins('payment_approval', $msg, route('orders.show', $order), [
                        'order_id' => $order->id,
                        'payment_id' => $payment->id,
                    ]);
                } else {
                    $msg = sprintf('Payment %.2f for Order %s recorded (%s).', (float)$payment->amount, $order->order_id, $payment->status);
                    $this->notifications->notifyAdmins('payment', $msg, route('orders.show', $order), [
                        'order_id' => $order->id,
                        'payment_id' => $payment->id,
                    ]);
                }
            } catch (\Throwable $e) {
                // swallow notifications errors
            }

            // Activity log
            try {
                ActivityLog::create([
                    'user_id' => (int)$data['created_by'],
                    'action' => $requiresApproval ? 'payment_create_pending_approval' : 'payment_create',
                    'subject_type' => Payment::class,
                    'subject_id' => $payment->id,
                    'changes' => [
                        'order_id' => $order->id,
                        'amount' => $payment->amount,
                        'status' => $payment->status,
                        'waived_penalty' => $payment->waived_penalty,
                        'requires_approval' => $payment->requires_approval,
                    ],
                ]);
            } catch (\Throwable $_) { /* ignore */ }

            // Touch order updated_at so downstream watchers see change and keep audit sync
            try {
                $order->touch();
            } catch (\Throwable $_) {
                // ignore
            }

            // update ledger
            try { $ledger->recalc(); } catch (\Throwable $e) { /* ignore */ }

            return $payment->load(['order','createdBy','ledger']);
        });
    }

    public function approve(int $paymentId, int $approverId): Payment
    {
        return DB::transaction(function () use ($paymentId, $approverId) {
            $payment = Payment::findOrFail($paymentId);
            if (!$payment->requires_approval || $payment->status !== 'pending') {
                return $payment;
            }
            $payment->update([
                'requires_approval' => false,
                'approved_by' => $approverId,
                'approved_at' => now(),
                // Keep status unchanged (likely 'pending'); receptionist may set to 'completed' via Edit after approval
                'status' => $payment->status,
            ]);
            // Activity log
            try {
                ActivityLog::create([
                    'user_id' => $approverId,
                    'action' => 'payment_approved',
                    'subject_type' => Payment::class,
                    'subject_id' => $payment->id,
                    'changes' => [
                        'approved_by' => $approverId,
                        'approved_at' => now()->toDateTimeString(),
                    ],
                ]);
            } catch (\Throwable $_) { /* ignore */ }
            return $payment->fresh();
        });
    }

    public function refund(int $paymentId, float $amount, int $actorUserId, ?string $reason = null, array $meta = [], ?string $idempotencyKey = null): Payment
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Refund amount must be greater than zero.');
        }
        return DB::transaction(function () use ($paymentId, $amount, $actorUserId, $reason, $meta, $idempotencyKey) {
            $original = Payment::findOrFail($paymentId);
            if ($idempotencyKey) {
                $existing = Payment::where('idempotency_key', $idempotencyKey)->first();
                if ($existing) { return $existing; }
            }
            // Don't allow refund greater than original completed amount minus prior refunds
            if ($original->status !== 'completed') {
                throw new \DomainException('Only completed payments can be refunded.');
            }
            $refundedSoFar = (float)$original->children()->where('status','refunded')->sum('amount');
            $maxRefundable = max(0.0, (float)$original->amount - $refundedSoFar);
            if ($amount > $maxRefundable + 0.005) {
                throw new \DomainException(sprintf('Refund exceeds refundable amount. Max: %.2f, Given: %.2f', $maxRefundable, $amount));
            }

            $ledgerId = $original->payment_ledger_id;
            $refund = Payment::create([
                'order_id' => $original->order_id,
                'payment_ledger_id' => $ledgerId,
                'parent_payment_id' => $original->id,
                'amount' => $amount,
                'method' => $original->method,
                'status' => 'refunded',
                'paid_at' => now(),
                'notes' => null,
                'created_by' => $actorUserId,
                'refund_reason' => $reason,
                'metadata' => $meta,
                'idempotency_key' => $idempotencyKey,
            ]);

            // Activity log
            try {
                ActivityLog::create([
                    'user_id' => $actorUserId,
                    'action' => 'payment_refund',
                    'subject_type' => Payment::class,
                    'subject_id' => $refund->id,
                    'changes' => [
                        'original_payment_id' => $original->id,
                        'amount' => $amount,
                        'reason' => $reason,
                    ],
                ]);
            } catch (\Throwable $_) { /* ignore */ }

            // Recalc ledger
            try { optional($refund->ledger)->recalc(); } catch (\Throwable $_) { /* ignore */ }

            // Notify Admins
            try {
                $order = $original->order;
                $msg = sprintf('Refund %.2f for Order %s recorded.', (float)$refund->amount, optional($order)->order_id);
                $this->notifications->notifyAdmins('payment_refund', $msg, route('orders.show', $order), [
                    'order_id' => $original->order_id,
                    'payment_id' => $refund->id,
                ]);
            } catch (\Throwable $_) { /* ignore */ }

            return $refund;
        });
    }

    public function reverse(int $paymentId, int $actorUserId, ?string $reason = null, array $meta = []): Payment
    {
        return DB::transaction(function () use ($paymentId, $actorUserId, $reason, $meta) {
            $original = Payment::findOrFail($paymentId);
            if (!in_array($original->status, ['pending','completed'])) {
                return $original; // nothing to do
            }
            $ledgerId = $original->payment_ledger_id;
            $reversal = Payment::create([
                'order_id' => $original->order_id,
                'payment_ledger_id' => $ledgerId,
                'parent_payment_id' => $original->id,
                'amount' => (float)$original->amount,
                'method' => $original->method,
                'status' => 'refunded', // treat reversal as refunded entry of equal amount
                'paid_at' => now(),
                'notes' => null,
                'created_by' => $actorUserId,
                'refund_reason' => $reason ?? ('Reversal of payment ID '.$original->id),
                'metadata' => $meta,
            ]);
            // Activity log
            try {
                ActivityLog::create([
                    'user_id' => $actorUserId,
                    'action' => 'payment_reversal',
                    'subject_type' => Payment::class,
                    'subject_id' => $reversal->id,
                    'changes' => [
                        'original_payment_id' => $original->id,
                        'amount' => $original->amount,
                        'reason' => $reason,
                    ],
                ]);
            } catch (\Throwable $_) { /* ignore */ }
            try { optional($reversal->ledger)->recalc(); } catch (\Throwable $_) { /* ignore */ }
            return $reversal;
        });
    }
}
