<?php

namespace Database\Factories;

use App\Models\OrderItem;
use App\Models\OrderItemService;
use App\Models\Service;
use App\Models\UrgencyTier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItemService>
 */
class OrderItemServiceFactory extends Factory
{
    protected $model = OrderItemService::class;

    public function definition(): array
    {
        return [
            'order_item_id' => OrderItem::factory(),
            'service_id' => Service::factory(),
            'employee_id' => null,
            'urgency_tier_id' => UrgencyTier::factory(),
            'quantity' => 1,
            'price_applied' => 0,
            'status' => 'pending',
        ];
    }
}
