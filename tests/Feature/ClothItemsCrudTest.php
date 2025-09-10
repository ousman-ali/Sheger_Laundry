<?php

use App\Models\User;
use App\Models\ClothItem;
use App\Models\Unit;
use Illuminate\Foundation\Testing\WithFaker;

uses(WithFaker::class);

function adminUser(): User {
    // Ensure permissions exist before assigning
    foreach (['view_cloth_items','create_cloth_items','edit_cloth_items','delete_cloth_items'] as $perm) {
        \Spatie\Permission\Models\Permission::findOrCreate($perm, 'web');
    }
    $user = User::factory()->create([ 'email' => 'admin@test.local' ]);
    $user->givePermissionTo(['view_cloth_items','create_cloth_items','edit_cloth_items','delete_cloth_items']);
    return $user;
}

it('lists cloth items with filters and pagination', function () {
    $user = adminUser();
    $unit = Unit::factory()->create();
    foreach (range(1,3) as $i) {
        ClothItem::factory()->create(['unit_id' => $unit->id, 'name' => 'Shirt '.$i]);
    }
    foreach (range(1,2) as $i) {
        ClothItem::factory()->create(['unit_id' => $unit->id, 'name' => 'Blanket '.$i]);
    }

    $resp = $this->actingAs($user)->get(route('cloth-items.index', ['q' => 'Shirt', 'per_page' => 10]));
    $resp->assertOk();
    $resp->assertSee('Cloth Items');
});

it('creates a cloth item', function () {
    $user = adminUser();
    $unit = Unit::factory()->create();

    $resp = $this->actingAs($user)->post(route('cloth-items.store'), [
        'name' => 'T-shirt',
        'unit_id' => $unit->id,
        'description' => 'Cotton T-shirt',
    ]);

    $resp->assertRedirect(route('cloth-items.index'));
    $this->assertDatabaseHas('cloth_items', [ 'name' => 'T-shirt', 'unit_id' => $unit->id ]);
});

it('updates a cloth item', function () {
    $user = adminUser();
    $unit = Unit::factory()->create();
    $item = ClothItem::factory()->create(['unit_id' => $unit->id, 'name' => 'Item A']);

    $resp = $this->actingAs($user)->put(route('cloth-items.update', $item), [
        'name' => 'Item B',
        'unit_id' => $unit->id,
        'description' => 'Updated',
    ]);

    $resp->assertRedirect(route('cloth-items.index'));
    $this->assertDatabaseHas('cloth_items', [ 'id' => $item->id, 'name' => 'Item B' ]);
});

it('prevents deleting cloth item in use', function () {
    $user = adminUser();
    $unit = Unit::factory()->create();
    $item = ClothItem::factory()->create(['unit_id' => $unit->id, 'name' => 'Item A']);
    // Create linked pricing tier to block deletion
    $service = App\Models\Service::factory()->create();
    App\Models\PricingTier::factory()->create([
        'cloth_item_id' => $item->id,
        'service_id' => $service->id,
    ]);

    $resp = $this->actingAs($user)->delete(route('cloth-items.destroy', $item));
    $resp->assertRedirect(route('cloth-items.index'));
});
