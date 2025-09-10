<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryStock extends Model
{
    protected $table = 'inventory_stock';

    protected $fillable = [
        'inventory_item_id', 'store_id', 'quantity',
    ];

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
} 