<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RemarkPreset extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'label', 'is_active', 'sort_order', 'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'order_remark_preset', 'remark_preset_id', 'order_id')
            ->withTimestamps();
    }
    
        public function orderItems(): BelongsToMany
        {
            return $this->belongsToMany(OrderItem::class, 'order_item_remark_preset', 'remark_preset_id', 'order_item_id');
        }
}
