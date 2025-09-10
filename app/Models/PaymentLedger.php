<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentLedger extends Model
{
    protected $table = 'order_payment_ledgers';

    protected $fillable = [
        'order_id','total_amount','amount_received','status','currency',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'payment_ledger_id');
    }

    public function recalc(): void
    {
    $completed = (float)$this->payments()->where('status','completed')->sum('amount');
    $refunded = (float)$this->payments()->where('status','refunded')->sum('amount');
    $received = max(0.0, $completed - $refunded);
        $this->amount_received = $received;
        $this->status = $received <= 0 ? 'pending' : (($received + 0.0001) >= (float)$this->total_amount ? 'paid' : 'partial');
        $this->save();
    }
}
