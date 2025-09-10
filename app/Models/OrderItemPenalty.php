<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItemPenalty extends Model
{
    protected $fillable = [
        'order_id','order_item_service_id','amount','waived','waiver_reason','requires_approval','approved_by','approved_at','created_by',
    ];

    protected $casts = [
        'waived' => 'boolean',
        'requires_approval' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItemService(): BelongsTo
    {
        return $this->belongsTo(OrderItemService::class);
    }
}
