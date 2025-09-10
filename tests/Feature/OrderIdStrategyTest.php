<?php

use App\Models\User;
use App\Models\Customer;
use App\Models\ClothItem;
use App\Models\Service;
use App\Models\Unit;
use App\Models\PricingTier;
use App\Models\Order;
use Spatie\Permission\Models\Permission;

test('admin can override order_id on create and edit', function () {
    // Permissions
    foreach (['view_orders','create_orders','edit_orders'] as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
    }
    $admin = User::factory()->create();
    $admin->givePermissionTo(['view_orders','create_orders','edit_orders']);

    // Minimal data
    $customer = Customer::factory()->create(['is_vip' => false]);
    $unit = Unit::factory()->create();
    $cloth = ClothItem::factory()->create(['unit_id' => $unit->id]);
    $service = Service::factory()->create();
    PricingTier::factory()->create(['cloth_item_id' => $cloth->id, 'service_id' => $service->id, 'price' => 10]);

    $this->actingAs($admin);
    $custom = 'ADMIN-OVERRIDE-001';
    $payload = [
        'customer_id' => $customer->id,
        'order_id' => $custom,
        'items' => [[
            'cloth_item_id' => $cloth->id,
            'unit_id' => $unit->id,
            'quantity' => 1,
            'services' => [[ 'service_id' => $service->id, 'quantity' => 1 ]],
        ]],
    ];
    $res = $this->post('/orders', $payload);
    $order = Order::latest('id')->first();
    $res->assertRedirect(route('orders.show', $order, absolute: false));
    $this->assertDatabaseHas('orders', ['id' => $order->id, 'order_id' => $custom]);

    // Edit with another override
    $custom2 = 'ADMIN-OVERRIDE-002';
    $upd = [
        'customer_id' => $customer->id,
        'order_id' => $custom2,
        'items' => [[
            'item_id' => $order->orderItems()->first()->id,
            'cloth_item_id' => $cloth->id,
            'unit_id' => $unit->id,
            'quantity' => 2,
            'services' => [[ 'service_id' => $service->id, 'quantity' => 2 ]],
        ]],
    ];
    $res2 = $this->put(route('orders.update', $order, absolute: false), $upd);
    $res2->assertRedirect(route('orders.show', $order, absolute: false));
    $this->assertDatabaseHas('orders', ['id' => $order->id, 'order_id' => $custom2]);
});

test('non-admin override is ignored', function () {
    // Permissions
    foreach (['view_orders','create_orders'] as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
    }
    $user = User::factory()->create();
    $user->givePermissionTo(['view_orders','create_orders']);

    // Minimal data
    $customer = Customer::factory()->create(['is_vip' => false]);
    $unit = Unit::factory()->create();
    $cloth = ClothItem::factory()->create(['unit_id' => $unit->id]);
    $service = Service::factory()->create();
    PricingTier::factory()->create(['cloth_item_id' => $cloth->id, 'service_id' => $service->id, 'price' => 10]);

    $this->actingAs($user);
    $payload = [
        'customer_id' => $customer->id,
        'order_id' => 'HACK-123',
        'items' => [[
            'cloth_item_id' => $cloth->id,
            'unit_id' => $unit->id,
            'quantity' => 1,
            'services' => [[ 'service_id' => $service->id, 'quantity' => 1 ]],
        ]],
    ];
    $res = $this->post('/orders', $payload);
    $order = Order::latest('id')->first();
    $res->assertRedirect(route('orders.show', $order, absolute: false));
    $this->assertDatabaseMissing('orders', ['id' => $order->id, 'order_id' => 'HACK-123']);
});
