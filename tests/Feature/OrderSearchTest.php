<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function seedUserWithPermission($perm = 'view_orders') {
    $user = User::factory()->create();
    // Assign role and permission minimally
    $roleId = DB::table('roles')->insertGetId(['name' => 'Admin', 'guard_name' => 'web']);
    DB::table('model_has_roles')->insert(['role_id' => $roleId, 'model_type' => User::class, 'model_id' => $user->id]);
    DB::table('permissions')->insertOrIgnore(['name' => $perm, 'guard_name' => 'web']);
    DB::table('role_has_permissions')->insertOrIgnore([
        'permission_id' => DB::table('permissions')->where('name', $perm)->value('id'),
        'role_id' => $roleId,
    ]);
    return $user;
}

test('orders can be searched by order id and customer', function () {
    $user = seedUserWithPermission();

    $customer = Customer::factory()->create(['name' => 'Alice Wonderland']);
    $order1 = Order::factory()->create(['order_id' => 'ORD-ABC-123', 'customer_id' => $customer->id]);
    $order2 = Order::factory()->create(['order_id' => 'ORD-XYZ-999']);

    $this->actingAs($user)
        ->get(route('orders.index', ['q' => 'Alice']))
        ->assertOk()
        ->assertSee('ORD-ABC-123')
        ->assertDontSee('ORD-XYZ-999');

    $this->actingAs($user)
        ->get(route('orders.index', ['q' => 'XYZ']))
        ->assertOk()
        ->assertSee('ORD-XYZ-999')
        ->assertDontSee('ORD-ABC-123');
});
