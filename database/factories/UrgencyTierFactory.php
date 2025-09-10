<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UrgencyTier>
 */
class UrgencyTierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'label' => fake()->randomElement(['Normal', '3 Days +50%', '2 Days +75%', 'Same Day +100%']),
            'duration_days' => fake()->randomElement([1, 2, 3, 7]),
            'multiplier' => fake()->randomFloat(2, 1.0, 2.0),
        ];
    }
} 