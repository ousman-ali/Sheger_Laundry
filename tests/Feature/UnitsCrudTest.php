<?php

use App\Models\User;
use App\Models\Unit;

function unitsAdmin(): User {
    foreach (['view_units','create_units','edit_units','delete_units'] as $p) {
        \Spatie\Permission\Models\Permission::findOrCreate($p, 'web');
    }
    $user = User::factory()->create(['email' => 'units-admin@test.local']);
    $user->givePermissionTo(['view_units','create_units','edit_units','delete_units']);
    return $user;
}

it('lists, creates, updates, and deletes units', function () {
    $user = unitsAdmin();

    // Index
    $this->actingAs($user)->get(route('units.index'))->assertOk()->assertSee('Units');

    // Create
    $this->actingAs($user)->post(route('units.store'), [
        'name' => 'kg',
        'parent_unit_id' => null,
        'conversion_factor' => null,
    ])->assertRedirect(route('units.index'));
    $this->assertDatabaseHas('units', ['name' => 'kg']);

    $kg = Unit::where('name','kg')->first();

    // Update
    $this->actingAs($user)->put(route('units.update', $kg), [
        'name' => 'kilogram',
        'parent_unit_id' => null,
        'conversion_factor' => null,
    ])->assertRedirect(route('units.index'));
    $this->assertDatabaseHas('units', ['name' => 'kilogram']);

    // Delete
    $this->actingAs($user)->delete(route('units.destroy', $kg))->assertRedirect(route('units.index'));
    $this->assertDatabaseMissing('units', ['name' => 'kilogram']);
});
