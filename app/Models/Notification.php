<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = [
    'user_id', 'type', 'message', 'url', 'meta', 'is_read',
    ];

    protected $casts = [
    'is_read' => 'boolean',
    'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
} 