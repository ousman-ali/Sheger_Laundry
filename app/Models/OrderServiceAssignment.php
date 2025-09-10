<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderServiceAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_item_service_id', 'employee_id', 'quantity', 'status',
    ];

    public function orderItemService(): BelongsTo
    {
        return $this->belongsTo(OrderItemService::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}
