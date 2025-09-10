<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\SystemSetting;
use App\Models\User;
use Tests\TestCase;

class PenaltyCalculationTest extends TestCase
{
    public function test_system_setting_penalty_rate_is_used_when_order_rate_missing(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin, 'web');
        SystemSetting::updateOrCreate(['key' => 'penalty_daily_rate'], ['value' => '5']);

        $order = Order::factory()->create([
            'total_cost' => 100,
            'pickup_date' => now()->subDays(3),
            'penalty_daily_rate' => null,
            'penalty_amount' => 0,
        ]);

        $resp = $this->get(route('payments.suggest', ['order_id' => $order->id]));
        $resp->assertOk();
        $data = $resp->json();
        // 3 days late * 5 = 15 penalty
        expect((float)$data['penalty'])->toBeFloat()->toBeGreaterThan(14.99);
        expect((float)$data['total'])->toBe(115.0);
    }
}
