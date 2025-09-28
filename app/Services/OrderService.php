<?php

namespace App\Services;

use Andegna\DateTime;
use Andegna\DateTimeFactory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemService;
use App\Models\PricingTier;
use App\Models\UrgencyTier;
use App\Models\ActivityLog;
use App\Models\Notification;
use App\Models\OrderServiceAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

use App\Services\NotificationService;

class OrderService
{
    public function __construct(private NotificationService $notifications)
    {
    }

    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $customer = \App\Models\Customer::findOrFail($data['customer_id']);
            // If Admin supplied a manual order_id, use it as-is (validated unique in request)
            if (!empty($data['order_id'])) {
                $orderId = (string)$data['order_id'];
            } else {
                $orderId = $this->generateOrderId();
                $vipPrefixConf = \App\Models\SystemSetting::getValue('vip_order_id_prefix', config('shebar.vip_order_id_prefix', 'VIP'));
                $vipPrefix = $customer->is_vip ? (($vipPrefixConf ?: 'VIP').'-') : '';
                $orderId = $vipPrefix . $orderId;
            }

            $order = Order::create([
                'order_id' => $orderId,
                'customer_id' => $data['customer_id'],
                'customer_code' => $customer->code,
                'customer_is_vip' => (bool)$customer->is_vip,
                'created_by' => Auth::id(),
                'total_cost' => 0,
                'discount' => $data['discount'] ?? 0,
                'vat_percentage' => system_setting('vat_percentage', config('shebar.vat_percentage')),
                'appointment_date' => $data['appointment_date'] ?? null,
                'pickup_date' => $data['pickup_date'] ?? null,
                'date_type' => $data['date_type'] ?? 'GC',
                'penalty_daily_rate' => config('shebar.penalty_daily_rate'),
                'status' => config('shebar.default_order_status'),
                'remarks' => $data['remarks'] ?? null,
            ]);

            $totalCost = 0;

            foreach ($data['items'] as $itemIndex => $item) {
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'cloth_item_id' => $item['cloth_item_id'],
                    'unit_id' => $item['unit_id'],
                    'quantity' => $item['quantity'],
                    'remarks' => $item['remarks'] ?? null,
                ]);

                // Sync item-level remark presets if provided
                if (!empty($item['remark_preset_ids']) && is_array($item['remark_preset_ids'])) {
                    $ids = array_values(array_unique(array_map('intval', $item['remark_preset_ids'])));
                    $orderItem->remarkPresets()->sync($ids);
                }

                $baseServices = $item['services'] ?? [];
                // Filter out any malformed entries early
                $baseServices = array_values(array_filter($baseServices, function($svc){
                    return is_array($svc) && isset($svc['service_id']) && $svc['service_id'];
                }));
                if (empty($baseServices)) {
                    throw new \RuntimeException("No valid services provided for item index $itemIndex.");
                }

                $segments = $item['quantity_breakdown'] ?? null;
                if ($segments) {
                    foreach ($segments as $segment) {
                        $segmentQty = (float)$segment['quantity'];
                        $segmentUrgency = $segment['urgency_tier_id'] ?? null;
                        foreach ($baseServices as $serviceData) {
                            if (!isset($serviceData['service_id'])) { continue; }
                            $price = $this->calculateServicePrice(
                                $item['cloth_item_id'],
                                (int)$serviceData['service_id'],
                                $segmentQty,
                                $segmentUrgency ?? ($serviceData['urgency_tier_id'] ?? null)
                            );
                            OrderItemService::create([
                                'order_item_id' => $orderItem->id,
                                'service_id' => (int)$serviceData['service_id'],
                                'urgency_tier_id' => $segmentUrgency ?? ($serviceData['urgency_tier_id'] ?? null),
                                'quantity' => $segmentQty,
                                'price_applied' => $price,
                                'status' => 'pending',
                            ]);
                            $totalCost += $price;
                        }
                    }
                } else {
                    foreach ($baseServices as $serviceData) {
                        if (!isset($serviceData['service_id'])) { continue; }
                        $price = $this->calculateServicePrice(
                            $item['cloth_item_id'],
                            (int)$serviceData['service_id'],
                            (float)$serviceData['quantity'],
                            $serviceData['urgency_tier_id'] ?? null
                        );

                        OrderItemService::create([
                            'order_item_id' => $orderItem->id,
                            'service_id' => (int)$serviceData['service_id'],
                            'urgency_tier_id' => $serviceData['urgency_tier_id'] ?? null,
                            'quantity' => (float)$serviceData['quantity'],
                            'price_applied' => $price,
                            'status' => 'pending',
                        ]);

                        $totalCost += $price;
                    }
                }
            }

            $finalTotal = $this->calculateFinalTotal($totalCost, $order->vat_percentage, $order->discount);
            $order->update(['total_cost' => $finalTotal]);

            $this->logActivity('created_order', $order);

            // Notify Admins of new order
            try {
                $msg = sprintf('New order %s created for %s', $orderId, optional($order->customer)->name);
                app(\App\Services\NotificationService::class)
                    ->notifyAdmins('order_status', $msg, route('orders.show', $order), [
                        'order_id' => $order->id,
                    ]);
            } catch (\Throwable $e) { /* ignore */ }

            // Notify Admins about the new order (actionable link)
            try { $this->notifyAdminsNewOrder($order); } catch (\Throwable $_) {}

            return $order->load(['customer', 'orderItems.clothItem.unit', 'orderItems.remarkPresets', 'orderItems.orderItemServices.service']);
        });
    }

    protected function notifyAdminsNewOrder(Order $order): void
    {
        $admins = User::role('Admin')->get();
        $url = route('orders.show', $order);
        foreach ($admins as $admin) {
            // Use existing enum 'order_status' for notification type
            $msg = sprintf('New order %s created for %s', $order->order_id, optional($order->customer)->name);
            $this->notifications->createNotification((int)$admin->id, 'order_status', $msg, $url, [
                'order_id' => $order->id,
                'order_code' => $order->order_id,
            ]);
        }
    }

    public function updateOrderStatus(Order $order, string $newStatus): bool
    {
        $allowedStatuses = config('shebar.order_status_workflow')[$order->status] ?? [];
        
        if (!in_array($newStatus, $allowedStatuses) && $newStatus !== 'cancelled') {
            throw new \InvalidArgumentException("Invalid status transition from {$order->status} to {$newStatus}");
        }

        $oldStatus = $order->status;
        $order->update(['status' => $newStatus]);

        $this->logActivity('updated_order_status', $order, [
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ]);

        return true;
    }

    /**
     * Assign or adjust an employee assignment for a given service with quantity support.
     * If quantity is null, assigns the remaining unassigned quantity to the employee.
     * Clamps to remaining quantity; creates/updates an assignment row per employee.
     */
    public function autoAssignItemServices(OrderItem $orderItem): void
    {
        // 1. Load cloth item with its clothing group
        $clothItem = $orderItem->clothItem()->with('clothingGroup')->first();

        // 2. Ensure cloth item, group, and user exist
        if (!$clothItem || !$clothItem->clothingGroup || !$clothItem->clothingGroup->user_id) {
            return; // Skip if no employee is linked
        }

        $employeeId = $clothItem->clothingGroup->user_id;

        // 3. Loop through all order item services
        foreach ($orderItem->orderItemServices as $service) {
            // prevent duplicate assignments
            $alreadyAssigned = $service->assignments()
                ->where('employee_id', $employeeId)
                ->exists();

            if (!$alreadyAssigned) {
                // 4. Create assignment record
                OrderServiceAssignment::create([
                    'order_item_service_id' => $service->id,
                    'employee_id'           => $employeeId,
                    'quantity'              => $service->quantity,
                    'status'                => 'assigned',
                ]);

                // 5. Update service status
                $service->update(['status' => 'assigned']);
            }
        }
    }

    public function assignEmployee(OrderItemService $service, ?int $employeeId, ?float $quantity = null): OrderItemService
    {
        if (!$employeeId) {
            return $service;
        }

        $appliedQty = 0.0;
        $service = DB::transaction(function () use ($service, $employeeId, $quantity, &$appliedQty) {
            /** @var OrderItemService $locked */
            $locked = OrderItemService::query()->whereKey($service->id)->lockForUpdate()->firstOrFail();

            $totalQty = (float) $locked->quantity;
            $currentlyAssigned = (float) $locked->assignments()->lockForUpdate()->sum('quantity');
            $remaining = max(0.0, $totalQty - $currentlyAssigned);

            // Decide how much to allocate
            $requestedQty = $quantity === null ? $remaining : (float)$quantity;

            // 🔑 Smart assignment rules
            if ($remaining <= 0 || $requestedQty > $remaining) {
                // Case 1 + Case 2: Replace
                $locked->assignments()->delete();

                $assignQty = $quantity === null ? $totalQty : min($requestedQty, $totalQty);
                $locked->assignments()->create([
                    'employee_id' => $employeeId,
                    'quantity'   => $assignQty,
                    'status'     => 'assigned',
                ]);

                $appliedQty = $assignQty;
            } else {
                // Case 3: Add (normal behavior)
                $assignment = $locked->assignments()->where('employee_id', $employeeId)->lockForUpdate()->first();
                if ($assignment) {
                    $assignment->quantity = (float) $assignment->quantity + $requestedQty;
                    if ($assignment->status === 'cancelled') {
                        $assignment->status = 'assigned';
                    }
                    $assignment->save();
                } else {
                    $locked->assignments()->create([
                        'employee_id' => $employeeId,
                        'quantity'   => $requestedQty,
                        'status'     => 'assigned',
                    ]);
                }
                $appliedQty = $requestedQty;
            }

            // Update service status
            $newAssigned = $locked->assignments()->sum('quantity');
            if ($locked->status === 'pending' && $newAssigned >= $totalQty) {
                $locked->status = 'assigned';
                $locked->save();
            }

            return $locked->loadMissing(['assignments', 'orderItem.order', 'service']);
        });

        if ($appliedQty > 0) {
            $this->logActivity('assigned_employee', $service->orderItem->order, [
                'order_item_service_id' => $service->id,
                'employee_id' => $employeeId,
                'quantity' => $appliedQty,
            ]);

            try {
                $msg = sprintf('You were assigned %s × %.2f (Order %s, Customer %s)',
                    $service->service->name,
                    $appliedQty,
                    $service->orderItem->order->order_id,
                    optional($service->orderItem->order->customer)->name
                );
                $url = route('orders.show', $service->orderItem->order);
                $this->notifications->createNotification($employeeId, 'assignment', $msg, $url, [
                    'order_id'   => $service->orderItem->order->id,
                    'order_code' => $service->orderItem->order->order_id,
                    'service_id' => $service->id,
                    'quantity'   => $appliedQty,
                ]);
            } catch (\Throwable $e) { /* ignore */ }
        }

        $this->deriveServiceStatusFromAssignments($service);
        $this->deriveOrderStatusFromServices($service->orderItem->order);

        return $service;
    }

    /**
     * Update a service's status respecting allowed workflow.
     */
    public function updateServiceStatus(OrderItemService $service, string $newStatus): OrderItemService
    {
        // Idempotent: if status is unchanged, do nothing
        if ($newStatus === $service->status) {
            return $service;
        }
    $allowed = config('shebar.service_status_workflow')[$service->status] ?? [];
        if (!in_array($newStatus, $allowed)) {
            throw new \InvalidArgumentException("Invalid service status transition from {$service->status} to {$newStatus}");
        }
        $old = $service->status;
        $service->status = $newStatus;
        $service->save();

        // Keep assignment rows in sync with service status for clarity (coarse-grained)
        try {
            $service->assignments()->update(['status' => $newStatus]);
        } catch (\Throwable $e) { /* ignore sync issues */ }

        $this->logActivity('updated_service_status', $service->orderItem->order, [
            'order_item_service_id' => $service->id,
            'old_status' => $old,
            'new_status' => $newStatus,
        ]);

        // Derive order status
        $this->deriveOrderStatusFromServices($service->orderItem->order);

        return $service;
    }

    /**
     * Bulk assign employee to many services.
     */
    public function bulkAssignEmployees(array $serviceIds, ?int $employeeId, ?float $quantity = null): int
    {
        $count = 0;
        $services = OrderItemService::whereIn('id', $serviceIds)->get();
        foreach ($services as $svc) {
            $this->assignEmployee($svc, $employeeId, $quantity);
            $count++;
        }
        return $count;
    }

    /**
     * Assign all services in given order IDs to an employee.
     */
    public function assignByOrderIds(array $orderIds, ?int $employeeId, ?float $quantity = null): int
    {
    $services = OrderItemService::whereHas('orderItem.order', fn($q)=>$q->whereIn('id', $orderIds))->get();
        $count = 0;
        foreach ($services as $svc) {
            $this->assignEmployee($svc, $employeeId, $quantity);
            $count++;
        }
        return $count;
    }

    /**
     * Assign all services for given order item IDs to an employee.
     */
    public function assignByOrderItemIds(array $orderItemIds, ?int $employeeId, ?float $quantity = null): int
    {
    $services = OrderItemService::whereIn('order_item_id', $orderItemIds)->get();
        $count = 0;
        foreach ($services as $svc) {
            $this->assignEmployee($svc, $employeeId, $quantity);
            $count++;
        }
        return $count;
    }

    /**
     * Assign all open services for all orders of given customers.
     */
    public function assignByCustomerIds(array $customerIds, ?int $employeeId, ?float $quantity = null): int
    {
        $services = OrderItemService::whereHas('orderItem.order', function($q) use ($customerIds) {
            $q->whereIn('customer_id', $customerIds);
        })->get();
        $count = 0;
        foreach ($services as $svc) {
            $this->assignEmployee($svc, $employeeId, $quantity);
            $count++;
        }
        return $count;
    }

    /**
     * Bulk update service statuses.
     */
    public function bulkUpdateServiceStatus(array $serviceIds, string $newStatus): int
    {
        $count = 0;
        $services = OrderItemService::whereIn('id', $serviceIds)->get();
        foreach ($services as $svc) {
            $this->updateServiceStatus($svc, $newStatus);
            $count++;
        }
        return $count;
    }

    /**
     * Derive order.status from its services: any in_progress => processing; all completed => ready_for_pickup.
     */
    public function deriveOrderStatusFromServices(Order $order): void
    {
        $order->loadMissing('orderItems.orderItemServices');
        $services = $order->orderItems->flatMap->orderItemServices;
        if ($services->isEmpty()) {
            return;
        }
        if ($services->contains(fn($s) => $s->status === 'in_progress')) {
            if ($order->status !== 'processing') {
                $old = $order->status;
                $order->update(['status' => 'processing']);
                $this->logActivity('updated_order_status', $order, ['old_status' => $old, 'new_status' => 'processing']);
            }
            return;
        }
        if ($services->every(fn($s) => $s->status === 'completed')) {
            if ($order->status !== 'ready_for_pickup') {
                $old = $order->status;
                $order->update(['status' => 'ready_for_pickup']);
                $this->logActivity('updated_order_status', $order, ['old_status' => $old, 'new_status' => 'ready_for_pickup']);
            }
            return;
        }
        // else keep current
    }

    /**
     * Reassess a service status based on assignment progress (partial vs full).
     * If partially assigned and still pending, set to 'assigned'; if any in_progress in assignments, set service to in_progress.
     */
    public function deriveServiceStatusFromAssignments(OrderItemService $service): void
    {
        $service->loadMissing('assignments');
        $total = (float) $service->quantity;
        $assigned = (float) $service->assignedQuantity();
        $anyInProgress = $service->assignments->contains(fn($a) => $a->status === 'in_progress');
        if ($anyInProgress) {
            if ($service->status !== 'in_progress') {
                $service->status = 'in_progress';
                $service->save();
            }
            return;
        }

        // Mark assigned only when fully allocated, else keep pending
        if ($assigned >= $total) {
            if ($service->status !== 'assigned') {
                $service->status = 'assigned';
                $service->save();
            }
        } else {
            if (!in_array($service->status, ['pending'])) {
                $service->status = 'pending';
                $service->save();
            }
        }
    }

    public function updateOrder(Order $order, array $data): Order
    {
        return DB::transaction(function () use ($order, $data) {
            $original = $order->load(['orderItems.orderItemServices']);
            $changesLog = [
                'added_items' => [],
                'updated_items' => [],
                'removed_items' => [],
                'added_services' => [],
                'updated_services' => [],
                'removed_services' => [],
            ];

            $updatePayload = [
                'customer_id' => $data['customer_id'],
                'discount' => $data['discount'] ?? $order->discount,
                'appointment_date' => $data['appointment_date'] ?? $order->appointment_date,
                'pickup_date' => $data['pickup_date'] ?? $order->pickup_date,
                'date_type' => $data['date_type'] ?? $order->date_type,
                'remarks' => $data['remarks'] ?? $order->remarks,
            ];
            if (!empty($data['order_id'])) {
                $updatePayload['order_id'] = (string)$data['order_id'];
            }
            $order->update($updatePayload);

            // Map existing items by id
            $existingItems = $original->orderItems->keyBy('id');
            $processedItemIds = [];
            $subtotal = 0;

            foreach ($data['items'] as $itemData) {
                $itemId = $itemData['item_id'] ?? null;
                if ($itemId && $existingItems->has($itemId)) {
                    $orderItem = $existingItems[$itemId];
                    $dirty = [];
                    foreach (['cloth_item_id','unit_id','quantity','remarks'] as $field) {
                        $newVal = $itemData[$field];
                        if ($orderItem->{$field} != $newVal) {
                            $dirty[$field] = [$orderItem->{$field}, $newVal];
                            $orderItem->{$field} = $newVal;
                        }
                    }
                    if (!empty($dirty)) {
                        $orderItem->save();
                        $changesLog['updated_items'][] = ['item_id' => $orderItem->id, 'changes' => $dirty];
                    }
                    // Sync item-level remark presets for existing item
                    if (isset($itemData['remark_preset_ids']) && is_array($itemData['remark_preset_ids'])) {
                        $ids = array_values(array_unique(array_map('intval', $itemData['remark_preset_ids'])));
                        $orderItem->remarkPresets()->sync($ids);
                    }
                } else {
                    $orderItem = OrderItem::create([
                        'order_id' => $order->id,
                        'cloth_item_id' => $itemData['cloth_item_id'],
                        'unit_id' => $itemData['unit_id'],
                        'quantity' => $itemData['quantity'],
                        'remarks' => $itemData['remarks'] ?? null,
                    ]);
                    $changesLog['added_items'][] = ['item_id' => $orderItem->id];
                    // Sync item-level remark presets for new item
                    if (isset($itemData['remark_preset_ids']) && is_array($itemData['remark_preset_ids'])) {
                        $ids = array_values(array_unique(array_map('intval', $itemData['remark_preset_ids'])));
                        $orderItem->remarkPresets()->sync($ids);
                    }
                }
                $processedItemIds[] = $orderItem->id;

                // Services diff
                $existingServices = $orderItem->orderItemServices()->get()->keyBy('service_id');
                $incomingServiceIds = [];
                foreach ($itemData['services'] as $svcData) {
                    $svcId = (int)$svcData['service_id'];
                    $incomingServiceIds[] = $svcId;
                    if ($existingServices->has($svcId)) {
                        $svcModel = $existingServices[$svcId];
                        $dirty = [];
                        $newQty = (float)$svcData['quantity'];
                        $newUrgency = $svcData['urgency_tier_id'] ?? null;
                        if ($svcModel->quantity != $newQty || $svcModel->urgency_tier_id != $newUrgency) {
                            $price = $this->calculateServicePrice(
                                $orderItem->cloth_item_id,
                                $svcId,
                                $newQty,
                                $newUrgency
                            );
                            if ($svcModel->quantity != $newQty) {
                                $dirty['quantity'] = [$svcModel->quantity, $newQty];
                                $svcModel->quantity = $newQty;
                            }
                            if ($svcModel->urgency_tier_id != $newUrgency) {
                                $dirty['urgency_tier_id'] = [$svcModel->urgency_tier_id, $newUrgency];
                                $svcModel->urgency_tier_id = $newUrgency;
                            }
                            if ($svcModel->price_applied != $price) {
                                $dirty['price_applied'] = [$svcModel->price_applied, $price];
                                $svcModel->price_applied = $price;
                            }
                            $svcModel->save();
                            $changesLog['updated_services'][] = ['service_id' => $svcId, 'item_id' => $orderItem->id, 'changes' => $dirty];
                        }
                        $subtotal += $svcModel->price_applied;
                    } else {
                        $price = $this->calculateServicePrice(
                            $orderItem->cloth_item_id,
                            $svcId,
                            (float)$svcData['quantity'],
                            $svcData['urgency_tier_id'] ?? null
                        );
                        $newSvc = OrderItemService::create([
                            'order_item_id' => $orderItem->id,
                            'service_id' => $svcId,
                            'urgency_tier_id' => $svcData['urgency_tier_id'] ?? null,
                            'quantity' => (float)$svcData['quantity'],
                            'price_applied' => $price,
                            'status' => 'pending',
                        ]);
                        $changesLog['added_services'][] = ['service_id' => $svcId, 'item_id' => $orderItem->id];
                        $subtotal += $price;
                    }
                }

                // Remove services not incoming (only if pending)
                foreach ($existingServices as $svcId => $svcModel) {
                    if (!in_array($svcId, $incomingServiceIds)) {
                        if ($svcModel->status === 'pending') {
                            $changesLog['removed_services'][] = ['service_id' => $svcId, 'item_id' => $orderItem->id];
                            $svcModel->delete();
                        } else {
                            $subtotal += $svcModel->price_applied; // still counts
                        }
                    }
                }
            }

            // Remove items not processed (only if all services pending)
            foreach ($existingItems as $exId => $exItem) {
                if (!in_array($exId, $processedItemIds)) {
                    $allPending = $exItem->orderItemServices()->where('status','!=','pending')->exists() === false;
                    if ($allPending) {
                        $changesLog['removed_items'][] = ['item_id' => $exId];
                        $exItem->orderItemServices()->delete();
                        $exItem->delete();
                    } else {
                        // Still include its services in subtotal
                        foreach ($exItem->orderItemServices as $svc) {
                            $subtotal += $svc->price_applied;
                        }
                    }
                }
            }

            // If subtotal still zero (e.g., only updates) recompute from DB to be safe
            if ($subtotal === 0) {
                $subtotal = $order->orderItemServices()->sum('price_applied');
            }

            $order->vat_percentage = system_setting('vat_percentage', config('shebar.vat_percentage'));
            $finalTotal = $this->calculateFinalTotal($subtotal, $order->vat_percentage, $order->discount);
            $order->update([
                'vat_percentage' => $order->vat_percentage,
                'total_cost' => $finalTotal,
            ]);

            $this->logActivity('updated_order', $order, $changesLog);

            return $order->load(['customer','orderItems.clothItem.unit','orderItems.orderItemServices.service','orderItems.orderItemServices.urgencyTier']);
        });
    }

    private function generateOrderId(): string
    {
        // Collision-safe sequential number per day: PREFIX-<date>-SEQ
    $prefix = \App\Models\SystemSetting::getValue('order_id_prefix', config('shebar.order_id_prefix', 'ORD')) ?: 'ORD';
    $dateFmt = \App\Models\SystemSetting::getValue('order_id_format', config('shebar.order_id_format', 'Ymd')) ?: 'Ymd';
        $date = now()->format($dateFmt);
    $widthSetting = \App\Models\SystemSetting::getValue('order_id_sequence_length', null);
    $width = (int) ($widthSetting !== null ? $widthSetting : (config('shebar.order_id_sequence_length', config('shebar.order_id_suffix_length', 3))));

        // Use a lightweight counter table to avoid gaps and ensure uniqueness without random suffixes
        $seq = 0;
        DB::transaction(function () use ($date, &$seq) {
            $row = DB::table('order_counters')->where('date_key', $date)->lockForUpdate()->first();
            if ($row) {
                $seq = (int)$row->last_seq + 1;
                DB::table('order_counters')->where('id', $row->id)->update(['last_seq' => $seq, 'updated_at' => now()]);
            } else {
                $seq = 1;
                DB::table('order_counters')->insert([
                    'date_key' => $date,
                    'last_seq' => $seq,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }, 3);

    $id = sprintf('%s-%s-%0'.$width.'d', $prefix, $date, $seq);

        // In case of historical data or concurrent race, ensure unique; retry a couple of times if needed
        $tries = 0;
        while (Order::where('order_id', $id)->exists() && $tries < 3) {
            $seq++;
            $id = sprintf('%s-%s-%0'.$width.'d', $prefix, $date, $seq);
        }
        return $id;
    }

    private function calculateServicePrice(int $clothItemId, int $serviceId, float $quantity, ?int $urgencyTierId): float
    {
        $pricingTier = PricingTier::where('cloth_item_id', $clothItemId)
            ->where('service_id', $serviceId)
            ->firstOrFail();

        $basePrice = $pricingTier->price * $quantity;

        if ($urgencyTierId) {
            $urgencyTier = UrgencyTier::findOrFail($urgencyTierId);
            $basePrice *= $urgencyTier->multiplier;
        }

        return $basePrice;
    }

    private function calculateFinalTotal(float $subtotal, float $vatPercentage, float $discount): float
    {
        $vatAmount = $subtotal * ($vatPercentage / 100);
        $totalWithVat = $subtotal + $vatAmount;
        return $totalWithVat - $discount;
    }

    private function logActivity(string $action, $subject, array $changes = []): void
    {
        ActivityLog::create([
            
            'user_id' => Auth::id(),
            'action' => $action,
            'subject_type' => get_class($subject),
            'subject_id' => $subject->id,
            'changes' => $changes,
        ]);
    }
}