<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Unit extends Model
{
    use HasFactory;
    protected $fillable = [
        'name', 'parent_unit_id', 'conversion_factor',
    ];

    public function parentUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'parent_unit_id');
    }

    public function childUnits(): HasMany
    {
        return $this->hasMany(Unit::class, 'parent_unit_id');
    }

    public function clothItems(): HasMany
    {
        return $this->hasMany(ClothItem::class);
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function orderItemServices(): HasMany
    {
        return $this->hasMany(OrderItemService::class);
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function stockTransferItems(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }

    public function stockUsage(): HasMany
    {
        return $this->hasMany(StockUsage::class);
    }

    // --- Conversion helpers ---

    /**
     * Return the chain of ancestors including this unit up to the root (unit with no parent).
     * Index 0 is this unit; last index is the root.
     */
    public function ancestry(): array
    {
        $chain = [$this];
        $current = $this;
        while ($current->parent_unit_id) {
            $current = $current->parentUnit()->first();
            if (!$current) break;
            $chain[] = $current;
        }
        return $chain;
    }

    /**
     * Get the multiplicative factor to convert a quantity in THIS unit to the ROOT unit quantity.
     * With our schema, quantity_in_parent = quantity_in_child / conversion_factor.
     */
    public function factorToRoot(): float
    {
        $factor = 1.0;
        $current = $this;
        while ($current && $current->parent_unit_id) {
            $factor = $factor / (float) max(1e-12, ($current->conversion_factor ?? 1));
            $current = $current->parentUnit()->first();
        }
        return $factor;
    }

    /**
     * Determine if this unit is convertible to the target unit (i.e., share the same root).
     */
    public function isConvertibleTo(Unit $target): bool
    {
    $thisAnc = $this->ancestry();
    $targetAnc = $target->ancestry();
    $a = end($thisAnc);
    $b = end($targetAnc);
        return $a && $b && $a->id === $b->id;
    }

    /**
     * Convert a quantity in THIS unit to the TARGET unit. Throws if not convertible.
     */
    public function convertTo(Unit $target, float $qty): float
    {
        if ($this->id === $target->id) return $qty;
        if (!$this->isConvertibleTo($target)) {
            throw new \InvalidArgumentException('Units are not convertible.');
        }
        // qty_in_target = qty_in_this * (factor_this_to_root / factor_target_to_root)
        $thisToRoot = $this->factorToRoot();
        $targetToRoot = $target->factorToRoot();
        return $qty * ($thisToRoot / max(1e-12, $targetToRoot));
    }
}