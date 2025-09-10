<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;

function ensureViewOrdersPermissionFor(User $user): void {
    Permission::findOrCreate('view_orders', 'web');
    $user->givePermissionTo('view_orders');
}

test('orders index shows empty state when no orders', function () {
    $user = User::factory()->create();
    ensureViewOrdersPermissionFor($user);

    $response = $this->actingAs($user)->get(route('orders.index', absolute: false));
    $response->assertStatus(200);
    $response->assertSee('No orders yet.', escape: false);
});

test('orders index shows filtered empty state when no orders match filters', function () {
    $user = User::factory()->create();
    ensureViewOrdersPermissionFor($user);

    $response = $this->actingAs($user)->get(route('orders.index', ['status' => 'received'], absolute: false));
    $response->assertStatus(200);
    $response->assertSee('No orders match your filters.', escape: false);
});
