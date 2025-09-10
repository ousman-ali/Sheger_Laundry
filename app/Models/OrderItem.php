<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class OrderItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_id', 'cloth_item_id', 'unit_id', 'quantity', 'remarks',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function clothItem(): BelongsTo
    {
        return $this->belongsTo(ClothItem::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function orderItemServices(): HasMany
    {
        return $this->hasMany(OrderItemService::class);
    }

    public function remarkPresets(): BelongsToMany
    {
        return $this->belongsToMany(RemarkPreset::class, 'order_item_remark_preset', 'order_item_id', 'remark_preset_id')
            ->withTimestamps();
    }
}