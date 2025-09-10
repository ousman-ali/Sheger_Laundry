<?php

namespace Database\Factories;

use App\Models\ClothItem;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PricingTier>
 */
class PricingTierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cloth_item_id' => ClothItem::factory(),
            'service_id' => Service::factory(),
            'price' => fake()->randomFloat(2, 10, 100),
        ];
    }
} 