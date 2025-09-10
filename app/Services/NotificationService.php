<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Order;
use App\Models\InventoryStock;
use Carbon\Carbon;

class NotificationService
{
    /**
     * Create a notification
     */
    public function createNotification(int $userId, string $type, string $message, ?string $url = null, array $meta = []): Notification
    {
        $notification = Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'message' => $message,
            'url' => $url,
            'meta' => $meta ?: null,
            'is_read' => false,
        ]);

        // Broadcast to the target user's channel (if broadcasting enabled)
        try {
            event(new \App\Events\NotificationCreated($notification));
        } catch (\Throwable $e) {
            // Fail-safe: ignore broadcasting errors to avoid impacting core flows
        }

        return $notification;
    }

    /**
     * Notify all users with the Admin role.
     */
    public function notifyAdmins(string $type, string $message, ?string $url = null, array $meta = []): void
    {
        $admins = \App\Models\User::role('Admin')->get(['id']);
        foreach ($admins as $admin) {
            try {
                $this->createNotification((int)$admin->id, $type, $message, $url, $meta);
            } catch (\Throwable $e) { /* ignore per-recipient errors */ }
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(int $notificationId): bool
    {
        $notification = Notification::findOrFail($notificationId);
        return $notification->update(['is_read' => true]);
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead(int $userId): int
    {
        $count = Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);
        try {
            broadcast(new \App\Events\NotificationsMarkedRead($userId));
        } catch (\Throwable $e) {}
        return $count;
    }

    /**
     * Get unread notifications for a user
     */
    public function getUnreadNotifications(int $userId, int $limit = 10)
    {
        return Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Check for low stock items and create notifications
     */
    public function checkLowStockItems(): void
    {
        if (!config('shebar.enable_stock_alerts')) {
            return;
        }

        $threshold = config('shebar.low_stock_threshold', 10);
        
        $lowStockItems = InventoryStock::select(
            'inventory_stock.*',
            'inventory_items.name as item_name',
            'stores.name as store_name'
        )
        ->join('inventory_items', 'inventory_stock.inventory_item_id', '=', 'inventory_items.id')
        ->join('stores', 'inventory_stock.store_id', '=', 'stores.id')
        ->where('inventory_stock.quantity', '<=', $threshold)
        ->get();

        foreach ($lowStockItems as $item) {
            $this->createNotification(
                1, // Admin user ID - should be configurable
                'low_stock',
                "Low stock alert: {$item->item_name} in {$item->store_name} (Quantity: {$item->quantity})"
            );
        }
    }

    /**
     * Create pickup reminder notifications
     */
    public function createPickupReminders(): void
    {
        if (!config('shebar.enable_notifications')) {
            return;
        }

        $reminderDays = config('shebar.pickup_reminder_days', 1);
        $reminderDate = Carbon::now()->addDays($reminderDays);

        $orders = Order::where('status', 'ready_for_pickup')
            ->where('pickup_date', '<=', $reminderDate)
            ->where('pickup_date', '>', Carbon::now())
            ->get();

        foreach ($orders as $order) {
            $this->createNotification(
                $order->created_by,
                'pickup_reminder',
                "Order {$order->order_id} is ready for pickup on {$order->pickup_date}"
            );
        }
    }

    /**
     * Create order status change notifications
     */
    public function createOrderStatusNotification(Order $order, string $oldStatus, string $newStatus): void
    {
        if (!config('shebar.enable_notifications')) {
            return;
        }

        $notifications = [
            'ready_for_pickup' => "Order {$order->order_id} is ready for pickup",
            'delivered' => "Order {$order->order_id} has been delivered",
            'cancelled' => "Order {$order->order_id} has been cancelled",
        ];

        if (isset($notifications[$newStatus])) {
            $this->createNotification(
                $order->created_by,
                'order_status',
                $notifications[$newStatus]
            );
        }
    }

    /**
     * Get notification statistics
     */
    public function getNotificationStats(int $userId): array
    {
        return [
            'total' => Notification::where('user_id', $userId)->count(),
            'unread' => Notification::where('user_id', $userId)->where('is_read', false)->count(),
            'read' => Notification::where('user_id', $userId)->where('is_read', true)->count(),
        ];
    }

    /**
     * Delete old notifications (cleanup)
     */
    public function cleanupOldNotifications(int $daysOld = 30): int
    {
        $cutoffDate = Carbon::now()->subDays($daysOld);
        
        return Notification::where('created_at', '<', $cutoffDate)
            ->where('is_read', true)
            ->delete();
    }
} 