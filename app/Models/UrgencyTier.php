<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UrgencyTier extends Model
{
    use HasFactory;
    protected $fillable = [
        'label', 'duration_days', 'multiplier',
    ];

    public function orderItemServices(): HasMany
    {
        return $this->hasMany(OrderItemService::class);
    }
}