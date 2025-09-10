<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\Store;
use App\Models\Unit;
use App\Models\User;
use Tests\TestCase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class StockUsageTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure permissions exist
        foreach ([
            'view_stock_usage','create_stock_usage','edit_stock_usage','delete_stock_usage',
            'view_inventory','create_inventory','edit_inventory','delete_inventory',
        ] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        Role::findOrCreate('Admin', 'web');
    }

    public function test_index_renders(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['view_stock_usage']);
        $this->actingAs($user);

        $unit = Unit::create(['name' => 'kg']);
        $store = Store::create(['name' => 'Main Store']);
        $item = InventoryItem::create(['name' => 'Detergent', 'unit_id' => $unit->id, 'minimum_stock' => 0]);
        InventoryStock::create(['inventory_item_id' => $item->id, 'store_id' => $store->id, 'quantity' => 5]);

        // create one usage row via DB for listing
        \App\Models\StockUsage::create([
            'inventory_item_id' => $item->id,
            'store_id' => $store->id,
            'unit_id' => $unit->id,
            'quantity_used' => 1,
            'operation_type' => 'washing',
            'usage_date' => now(),
            'created_by' => $user->id,
        ]);

        $resp = $this->get(route('stock-usage.index'));
        $resp->assertOk();
        $resp->assertSee('Stock Usage');
        $resp->assertSee('Detergent');
    }

    public function test_create_usage_decrements_stock(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['create_stock_usage','view_stock_usage']);
        $this->actingAs($user);

        $unit = Unit::create(['name' => 'kg']);
        $store = Store::create(['name' => 'Main Store']);
        $item = InventoryItem::create(['name' => 'Detergent', 'unit_id' => $unit->id, 'minimum_stock' => 0]);
        InventoryStock::create(['inventory_item_id' => $item->id, 'store_id' => $store->id, 'quantity' => 10]);

        $payload = [
            'store_id' => $store->id,
            'usage_date' => now()->format('Y-m-d H:i:s'),
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'unit_id' => $unit->id,
                    'quantity_used' => 3,
                    'operation_type' => 'washing',
                ],
            ],
        ];

        $resp = $this->post(route('stock-usage.store'), $payload);
        $resp->assertRedirect(route('stock-usage.index'));

        $stock = InventoryStock::where('inventory_item_id', $item->id)->where('store_id', $store->id)->first();
        $this->assertNotNull($stock);
        $this->assertEquals(7.0, (float)$stock->quantity);

        $this->assertDatabaseHas('stock_usage', [
            'inventory_item_id' => $item->id,
            'store_id' => $store->id,
            'unit_id' => $unit->id,
            'quantity_used' => 3.00,
            'operation_type' => 'washing',
        ]);
    }

    public function test_create_usage_insufficient_stock(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['create_stock_usage','view_stock_usage']);
        $this->actingAs($user);

        $unit = Unit::create(['name' => 'kg']);
        $store = Store::create(['name' => 'Main Store']);
        $item = InventoryItem::create(['name' => 'Detergent', 'unit_id' => $unit->id, 'minimum_stock' => 0]);
        InventoryStock::create(['inventory_item_id' => $item->id, 'store_id' => $store->id, 'quantity' => 1]);

        $payload = [
            'store_id' => $store->id,
            'usage_date' => now()->format('Y-m-d H:i:s'),
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'unit_id' => $unit->id,
                    'quantity_used' => 5,
                    'operation_type' => 'washing',
                ],
            ],
        ];

        $resp = $this->from(route('stock-usage.create'))->post(route('stock-usage.store'), $payload);
        $resp->assertRedirect(route('stock-usage.create'));
        $resp->assertSessionHasErrors('items');

        $stock = InventoryStock::where('inventory_item_id', $item->id)->where('store_id', $store->id)->first();
        $this->assertEquals(1.0, (float)$stock->quantity);

        $this->assertDatabaseMissing('stock_usage', [
            'inventory_item_id' => $item->id,
            'store_id' => $store->id,
            'quantity_used' => 5.00,
        ]);
    }
}
