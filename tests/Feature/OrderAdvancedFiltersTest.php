<?php

use App\Models\Order;
use App\Models\User;
use App\Models\Customer;
use App\Models\OrderItem;
use App\Models\OrderItemService;
use App\Models\Service;
use Spatie\Permission\Models\Permission;

function grantViewOrders(User $user): void {
    Permission::findOrCreate('view_orders', 'web');
    $user->givePermissionTo('view_orders');
}

test('orders can be filtered by customer', function () {
    $user = User::factory()->create();
    grantViewOrders($user);

    $custA = Customer::factory()->create(['name' => 'Alice']);
    $custB = Customer::factory()->create(['name' => 'Bob']);

    Order::factory()->count(2)->create(['customer_id' => $custA->id, 'status' => 'received']);
    $bobOrder = Order::factory()->create(['customer_id' => $custB->id, 'status' => 'received']);

    $resp = $this->actingAs($user)->get(route('orders.index', ['customer_id' => $custA->id], absolute: false));
    $resp->assertStatus(200);
    $resp->assertSee('Alice', escape: false);
    $resp->assertDontSee($bobOrder->order_id, escape: false);
});

test('orders can be filtered by date range', function () {
    $user = User::factory()->create();
    grantViewOrders($user);

    // Create orders with known creation dates
    $old = Order::factory()->create(['created_at' => now()->subDays(10), 'status' => 'received']);
    $inRange = Order::factory()->create(['created_at' => now()->subDays(5), 'status' => 'received']);
    $recent = Order::factory()->create(['created_at' => now()->subDays(1), 'status' => 'received']);

    $from = now()->subDays(6)->toDateString();
    $to = now()->subDays(2)->toDateString();

    $resp = $this->actingAs($user)->get(route('orders.index', ['from_date' => $from, 'to_date' => $to], absolute: false));
    $resp->assertStatus(200);
    // Should include the 5-day order but not 10-day or 1-day
    $resp->assertSee($inRange->order_id, escape: false);
    $resp->assertDontSee($old->order_id, escape: false);
    $resp->assertDontSee($recent->order_id, escape: false);
});

test('orders can be filtered by operator assignment', function () {
    $user = User::factory()->create();
    grantViewOrders($user);

    // Create an operator user
    $operator = User::factory()->create(['name' => 'Op One']);

    // Create service to relate to order item services
    $service = Service::factory()->create();

    // Order without operator
    $order1 = Order::factory()->create(['status' => 'received']);

    // Order with operator via order item service
    $order2 = Order::factory()->create(['status' => 'received']);
    $orderItem = OrderItem::factory()->create(['order_id' => $order2->id]);
    OrderItemService::factory()->create([
        'order_item_id' => $orderItem->id,
        'service_id' => $service->id,
        'employee_id' => $operator->id,
    ]);

    $resp = $this->actingAs($user)->get(route('orders.index', ['operator_id' => $operator->id], absolute: false));
    $resp->assertStatus(200);
    $resp->assertSee((string)$order2->order_id, escape: false);
});
