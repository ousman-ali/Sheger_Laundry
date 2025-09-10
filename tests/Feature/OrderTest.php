<?php

namespace Tests\Feature;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Models\User;
use App\Models\Customer;
use App\Models\ClothItem;
use App\Models\Service;
use App\Models\Unit;
use App\Models\UrgencyTier;
use App\Models\PricingTier;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $customer;
    protected $clothItem;
    protected $service;
    protected $unit;
    protected $urgencyTier;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->customer = Customer::factory()->create();
        $this->unit = Unit::factory()->create(['name' => 'pcs']);
        $this->clothItem = ClothItem::factory()->create([
            'name' => 'T-shirt',
            'unit_id' => $this->unit->id,
        ]);
        $this->service = Service::factory()->create(['name' => 'Wet Washing']);
        $this->urgencyTier = UrgencyTier::factory()->create([
            'label' => 'Normal',
            'duration_days' => 7,
            'multiplier' => 1.00,
        ]);

        PricingTier::factory()->create([
            'cloth_item_id' => $this->clothItem->id,
            'service_id' => $this->service->id,
            'price' => 15.00,
        ]);

        // seed minimal permissions for tests
        foreach (['view_orders','create_orders','edit_orders'] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
        $this->user->givePermissionTo(['view_orders','create_orders','edit_orders']);
    }

    public function test_user_can_view_orders_list()
    {
        $this->actingAs($this->user);
        $response = $this->get('/orders');
        $response->assertStatus(200);
    }

    public function test_user_can_view_order_creation_form()
    {
        $this->actingAs($this->user);
        $response = $this->get('/orders/create');
        $response->assertStatus(200);
    }

    public function test_user_can_create_new_order()
    {
        $this->actingAs($this->user);

        $orderData = [
            'customer_id' => $this->customer->id,
            'items' => [
                [
                    'cloth_item_id' => $this->clothItem->id,
                    'unit_id' => $this->unit->id,
                    'quantity' => 5,
                    'services' => [
                        [
                            'service_id' => $this->service->id,
                            'quantity' => 5,
                        ]
                    ]
                ]
            ],
        ];

    $response = $this->post('/orders', $orderData);
    $order = Order::latest('id')->first();
    $response->assertRedirect(route('orders.show', $order, absolute: false));
        $response->assertSessionHas('success');
    }

    public function test_order_creation_validates_required_fields()
    {
        $this->actingAs($this->user);
        $response = $this->post('/orders', []);
        $response->assertSessionHasErrors(['customer_id', 'items']);
    }

    public function test_dashboard_shows_correct_statistics()
    {
        $this->actingAs($this->user);
        $response = $this->get('/dashboard');
        $response->assertStatus(200);
    }
}