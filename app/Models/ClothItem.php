<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClothItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'item_code',        // NEW
        'name',
        'unit_id',
        'clothing_group_id', // NEW
        'description',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function pricingTiers(): HasMany
    {
        return $this->hasMany(PricingTier::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function clothingGroup(): BelongsTo
    {
        return $this->belongsTo(ClothingGroup::class, 'clothing_group_id');
    }
    
}