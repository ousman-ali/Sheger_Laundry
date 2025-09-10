<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\PaymentLedger;

class OrderObserver
{
    public function created(Order $order): void
    {
        PaymentLedger::firstOrCreate(
            ['order_id' => $order->id],
            ['total_amount' => (float)($order->total_cost ?? 0), 'currency' => 'ETB']
        );
    }

    public function updated(Order $order): void
    {
        if ($order->wasChanged('total_cost')) {
            $ledger = $order->paymentLedger;
            if ($ledger) {
                $ledger->total_amount = (float)$order->total_cost;
                $ledger->recalc();
            }
        }
    }
}
