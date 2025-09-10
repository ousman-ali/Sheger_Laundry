<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOutRequestItem extends Model
{
    protected $fillable = [
        'stock_out_request_id','inventory_item_id','unit_id','quantity',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(StockOutRequest::class, 'stock_out_request_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
