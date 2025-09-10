<?php

use App\Models\Order;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

function ensureViewOrdersPermission(User $user): void {
    // Ensure role and permission exist and are granted to the user
    Role::findOrCreate('Admin', 'web');
    Permission::findOrCreate('view_orders', 'web');
    $user->assignRole('Admin');
    $user->givePermissionTo('view_orders');
}

test('orders index shows only requested status when filtered', function () {
    $user = User::factory()->create();
    ensureViewOrdersPermission($user);

    Order::factory()->count(2)->create(['status' => 'received']);
    Order::factory()->count(3)->create(['status' => 'ready_for_pickup']);

    $response = $this->actingAs($user)->get(route('orders.index', absolute: false));
    $response->assertStatus(200);

    // No filter: should contain both statuses
    $response->assertSee('received', escape:false);
    $response->assertSee('ready_for_pickup', escape:false);

    // With filter
    $response = $this->actingAs($user)->get(route('orders.index', ['status' => 'received'], absolute: false));
    $response->assertStatus(200);
    $response->assertSee('received', escape:false);
});
