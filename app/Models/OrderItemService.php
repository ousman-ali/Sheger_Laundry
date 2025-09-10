<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItemService extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_item_id', 'service_id', 'employee_id', 'urgency_tier_id',
        'quantity', 'price_applied', 'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function urgencyTier(): BelongsTo
    {
        return $this->belongsTo(UrgencyTier::class);
    }

    public function penalties()
    {
        return $this->hasMany(\App\Models\OrderItemPenalty::class, 'order_item_service_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(OrderServiceAssignment::class, 'order_item_service_id');
    }

    public function assignedQuantity(): float
    {
        return (float) ($this->assignments()->sum('quantity'));
    }

    public function remainingQuantity(): float
    {
        $rem = (float) $this->quantity - $this->assignedQuantity();
        return max(0.0, $rem);
    }

    public function assignedQuantityForEmployee(int $employeeId): float
    {
        return (float) ($this->assignments()->where('employee_id', $employeeId)->sum('quantity'));
    }
}