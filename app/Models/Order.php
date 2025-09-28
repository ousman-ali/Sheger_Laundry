<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\RemarkPreset;
use App\Utils\EthiopianCalendar;

class Order extends Model
{
    use HasFactory;

    protected $appends = ['appointment_date_display', 'pickup_date_display'];

    public function getAppointmentDateDisplayAttribute()
    {
        if (!$this->appointment_date) {
            return null;
        }

        if (strtoupper($this->date_type) === 'EC') {
            return EthiopianCalendar::toEthiopian($this->appointment_date);
        }

        return optional($this->appointment_date)->toDateTimeString();
    }

    public function getPickupDateDisplayAttribute()
    {
        if (!$this->pickup_date) {
            return null;
        }

        if (strtoupper($this->date_type) === 'EC') {
            return EthiopianCalendar::toEthiopian($this->pickup_date);
        }

        return optional($this->pickup_date)->toDateTimeString();
    }

    protected $fillable = [
        'order_id', 'customer_id', 'created_by', 'total_cost', 'discount',
        'vat_percentage', 'appointment_date', 'pickup_date', 'date_type',
        'penalty_amount', 'penalty_daily_rate', 'status', 'remarks',
    ];

    protected $casts = [
        'status' => 'string',
        'appointment_date' => 'datetime',
        'pickup_date' => 'datetime',
        'date_type' => 'string',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(\App\Models\Payment::class);
    }

    public function paymentLedger()
    {
        return $this->hasOne(\App\Models\PaymentLedger::class, 'order_id');
    }

    /**
     * Derive payment status from recorded payments vs order total.
     * Returns one of: 'paid', 'partial', 'unpaid'.
     */
    public function paymentStatus(): string
    {
        $ledger = $this->paymentLedger;
        if (!$ledger) {
            $total = (float)($this->total_cost ?? 0);
            $paid = (float)$this->payments()->where('status', 'completed')->sum('amount');
            if ($paid <= 0.0) return 'unpaid';
            if (abs($paid - $total) < 0.01 || $paid > $total) return 'paid';
            return 'partial';
        }
        return $ledger->status === 'pending' ? 'unpaid' : $ledger->status;
    }

    public function orderItemServices(): HasManyThrough
    {
        return $this->hasManyThrough(OrderItemService::class, OrderItem::class);
    }

    public function itemPenalties(): HasMany
    {
        return $this->hasMany(\App\Models\OrderItemPenalty::class);
    }

    public function remarkPresets(): BelongsToMany
    {
        return $this->belongsToMany(RemarkPreset::class, 'order_remark_preset', 'order_id', 'remark_preset_id')
            ->withTimestamps();
    }
}