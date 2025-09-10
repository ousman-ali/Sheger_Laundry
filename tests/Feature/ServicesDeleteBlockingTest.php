<?php

use App\Models\User;
use App\Models\Service;
use App\Models\ClothItem;
use App\Models\Unit;
use App\Models\PricingTier;

function adminForServices(): User {
    foreach (['view_services','delete_services'] as $perm) {
        \Spatie\Permission\Models\Permission::findOrCreate($perm, 'web');
    }
    $user = User::factory()->create(['email' => 'admin-services@test.local']);
    $user->givePermissionTo(['view_services','delete_services']);
    return $user;
}

it('blocks deleting service linked to pricing tiers', function () {
    $user = adminForServices();
    $service = Service::factory()->create();
    $unit = Unit::factory()->create();
    $cloth = ClothItem::factory()->create(['unit_id' => $unit->id]);
    PricingTier::factory()->create(['service_id' => $service->id, 'cloth_item_id' => $cloth->id]);

    $resp = $this->actingAs($user)->delete(route('services.destroy', $service));
    $resp->assertRedirect(route('services.index'));
});
