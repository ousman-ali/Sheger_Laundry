<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    protected $fillable = [
        'order_id','payment_ledger_id','amount','method','bank_id','status','paid_at','notes','created_by',
        'waived_penalty','waiver_reason','requires_approval','approved_by','approved_at',
        // refund/reversal & integrations
        'parent_payment_id','refund_reason','external_ref','idempotency_key','metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'approved_at' => 'datetime',
        'waived_penalty' => 'boolean',
        'requires_approval' => 'boolean',
        'metadata' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function ledger(): BelongsTo
    {
        return $this->belongsTo(PaymentLedger::class, 'payment_ledger_id');
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'parent_payment_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Payment::class, 'parent_payment_id');
    }
}
