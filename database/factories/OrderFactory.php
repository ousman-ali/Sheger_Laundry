<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => 'ORD-' . now()->format('Ymd') . '-' . fake()->randomNumber(3),
            'customer_id' => Customer::factory(),
            'created_by' => User::factory(),
            'total_cost' => fake()->randomFloat(2, 10, 500),
            'discount' => fake()->randomFloat(2, 0, 50),
            'vat_percentage' => 15.00,
            'appointment_date' => fake()->dateTimeBetween('now', '+1 week'),
            'pickup_date' => fake()->dateTimeBetween('+1 week', '+2 weeks'),
            'penalty_amount' => 0.00,
            'penalty_daily_rate' => 10.00,
            'status' => fake()->randomElement(['received', 'processing', 'washing', 'drying_steaming', 'ironing', 'packaging', 'ready_for_pickup', 'delivered']),
            'remarks' => fake()->optional()->sentence(),
        ];
    }
} 