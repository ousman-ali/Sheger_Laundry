<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Unit;
use App\Models\Store;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InventoryStockTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure permissions exist
        foreach ([
            'view_inventory','create_inventory','edit_inventory','delete_inventory'
        ] as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }
        $role = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $role->givePermissionTo(Permission::all());
    }

    public function test_inventory_stock_page_renders_and_lists_rows(): void
    {
    /** @var \App\Models\User $user */
    $user = User::factory()->createOne();
        $user->assignRole('Admin');

    /** @var \App\Models\Unit $unit */
    $unit = Unit::factory()->createOne(['name' => 'Kg']);
    /** @var \App\Models\Store $store */
    $store = Store::factory()->createOne(['name' => 'Main']);
    /** @var \App\Models\InventoryItem $item */
    $item = InventoryItem::factory()->createOne(['name' => 'Detergent', 'unit_id' => $unit->id, 'minimum_stock' => 5]);
        InventoryStock::create(['inventory_item_id' => $item->id, 'store_id' => $store->id, 'quantity' => 3]);

    $resp = $this->actingAs($user, 'web')->get(route('inventory.stock'));
        $resp->assertStatus(200);
        $resp->assertSee('Inventory Stock');
        $resp->assertSee('Detergent');
        $resp->assertSee('Main');
    }

    public function test_csv_export_downloads(): void
    {
    /** @var \App\Models\User $user */
    $user = User::factory()->createOne();
        $user->assignRole('Admin');

    $resp = $this->actingAs($user, 'web')->get(route('inventory.stock', ['export' => 'csv']));
        $resp->assertStatus(200);
    $this->assertStringContainsString('text/csv', (string) $resp->headers->get('Content-Type'));
        $resp->assertHeader('Content-Disposition');
    }

    public function test_pdf_export_downloads(): void
    {
    /** @var \App\Models\User $user */
    $user = User::factory()->createOne();
        $user->assignRole('Admin');

    $resp = $this->actingAs($user, 'web')->get(route('inventory.stock', ['export' => 'pdf']));
        $resp->assertStatus(200);
        $resp->assertHeader('Content-Type', 'application/pdf');
    }
}
