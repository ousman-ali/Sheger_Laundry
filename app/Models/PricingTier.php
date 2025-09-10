<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PricingTier extends Model
{
    use HasFactory;
    protected $fillable = [
        'cloth_item_id', 'service_id', 'price',
    ];

    public function clothItem(): BelongsTo
    {
        return $this->belongsTo(ClothItem::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}