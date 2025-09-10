<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->lexify('item-????'),
            'unit_id' => fn() => \App\Models\Unit::factory()->create()->id,
            'minimum_stock' => 5,
        ];
    }
}
