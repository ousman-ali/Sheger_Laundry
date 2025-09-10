<?php

namespace Database\Factories;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClothItem>
 */
class ClothItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['T-shirt', 'Shirt', 'Pants', 'Dress', 'Suit', 'Blanket', 'Curtain', 'Carpet']),
            'unit_id' => Unit::factory(),
            'description' => fake()->optional()->sentence(),
        ];
    }
} 