<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

function ensureRole(string $name): void {
    Role::findOrCreate($name, 'web');
}

test('admin root redirects to dashboard', function () {
    ensureRole('Admin');
    $user = User::factory()->create();
    $user->assignRole('Admin');

    $response = $this->actingAs($user)->get('/');
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('receptionist root redirects to reception dashboard', function () {
    ensureRole('Receptionist');
    $user = User::factory()->create();
    $user->assignRole('Receptionist');

    $response = $this->actingAs($user)->get('/');
    $response->assertRedirect(route('reception.index', absolute: false));
});

test('manager root redirects to manager dashboard', function () {
    ensureRole('Manager');
    $user = User::factory()->create();
    $user->assignRole('Manager');

    $response = $this->actingAs($user)->get('/');
    $response->assertRedirect(route('manager.index', absolute: false));
});

test('operator root redirects to operator my tasks', function () {
    ensureRole('Operator');
    $user = User::factory()->create();
    $user->assignRole('Operator');

    $response = $this->actingAs($user)->get('/');
    $response->assertRedirect(route('operator.my', absolute: false));
});

test('receptionist can access reception dashboard', function () {
    ensureRole('Receptionist');
    $user = User::factory()->create();
    $user->assignRole('Receptionist');

    $response = $this->actingAs($user)->get(route('reception.index', absolute: false));
    $response->assertOk();
});

test('manager can access manager dashboard', function () {
    ensureRole('Manager');
    $user = User::factory()->create();
    $user->assignRole('Manager');

    $response = $this->actingAs($user)->get(route('manager.index', absolute: false));
    $response->assertOk();
});

test('operator can access my tasks', function () {
    ensureRole('Operator');
    $user = User::factory()->create();
    $user->assignRole('Operator');

    $response = $this->actingAs($user)->get(route('operator.my', absolute: false));
    $response->assertOk();
});
