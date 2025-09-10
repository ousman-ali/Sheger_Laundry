<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Unit>
 */
class UnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Use a unique synthetic unit name to avoid collisions with the unique index
            'name' => fake()->unique()->lexify('unit-????'),
            'parent_unit_id' => null,
            'conversion_factor' => null,
        ];
    }
} 