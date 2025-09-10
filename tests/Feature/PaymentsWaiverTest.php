<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Tests\TestCase;
use Spatie\Permission\Models\Permission;

class PaymentsWaiverTest extends TestCase
{

    public function test_receptionist_requests_waiver_sets_pending_and_requires_approval(): void
    {
        // ensure permissions exist
        foreach (['create_payments','view_payments','edit_payments'] as $perm) {
            Permission::findOrCreate($perm, 'web');
        }
        $user = User::factory()->create();
        $user->givePermissionTo(['create_payments','view_payments','edit_payments']);
    $this->actingAs($user, 'web');

        $order = Order::factory()->create([
            'total_cost' => 100.0,
            'pickup_date' => now()->subDays(2),
            'penalty_daily_rate' => 10.0,
        ]);

        $resp = $this->post(route('payments.store'), [
            'order_id' => $order->id,
            'amount' => 100.0, // lower than suggested 120, implies waiver
            'payment_method' => 'cash',
            'status' => 'completed', // should be forced to pending
            'paid_at' => now()->format('Y-m-d H:i:s'),
            'notes' => 'test',
            'waived_penalty' => true,
            'waiver_reason' => 'customer reason',
        ]);

        $resp->assertRedirect(route('payments.index'));
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'requires_approval' => 1,
            'status' => 'pending',
        ]);
    }
}
