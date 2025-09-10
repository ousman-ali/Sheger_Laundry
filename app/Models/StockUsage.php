<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockUsage extends Model
{
    protected $table = 'stock_usage';

    protected $fillable = [
    'inventory_item_id', 'store_id', 'unit_id', 'entered_unit_id', 'entered_quantity', 'canonical_quantity', 'quantity_used',
    'operation_type', 'usage_date', 'created_by',
    ];

    protected $casts = [
        'usage_date' => 'datetime',
    ];

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
} 