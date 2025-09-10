<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Store extends Model
{
    use HasFactory;
    protected $fillable = [
        'name', 'type', 'description',
    ];

    public function inventoryStock(): HasMany
    {
        return $this->hasMany(InventoryStock::class);
    }

    public function stockTransfersFrom(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'from_store_id');
    }

    public function stockTransfersTo(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'to_store_id');
    }

    public function stockUsage(): HasMany
    {
        return $this->hasMany(StockUsage::class);
    }
} 