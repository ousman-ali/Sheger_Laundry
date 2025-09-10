<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\StockUsage;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;

class StockUsageService
{
    /**
     * Record multiple usage rows and update inventory stock atomically.
     * Input shape:
     * - store_id: int
     * - usage_date: date/datetime string
     * - created_by: int
     * - items: array of { inventory_item_id, unit_id?, quantity_used, operation_type }
     */
    public function recordBulkUsage(array $payload): int
    {
        $storeId = (int) $payload['store_id'];
        $usageDate = $payload['usage_date'];
        $createdBy = (int) $payload['created_by'];
        $items = $payload['items'] ?? [];

        // Normalize to canonical units and group by item for availability check
        $normalized = [];
        $requiredByItem = [];
        foreach (array_values($items) as $idx => $row) {
            $inventoryItem = InventoryItem::findOrFail($row['inventory_item_id']);
            $canonicalUnit = Unit::findOrFail($inventoryItem->unit_id);
            $enteredUnit = isset($row['unit_id']) ? Unit::findOrFail($row['unit_id']) : $canonicalUnit;
            $qty = (float) $row['quantity_used'];
            $qtyCanonical = $enteredUnit->id === $canonicalUnit->id
                ? $qty
                : $enteredUnit->convertTo($canonicalUnit, $qty);

            $normalized[] = [
                'inventory_item_id' => $inventoryItem->id,
                'unit_id' => $canonicalUnit->id,
                'quantity' => $qtyCanonical,
                'operation_type' => $row['operation_type'],
            ];
            $requiredByItem[$inventoryItem->id] = ($requiredByItem[$inventoryItem->id] ?? 0) + $qtyCanonical;
        }

        return DB::transaction(function () use ($storeId, $usageDate, $createdBy, $normalized, $requiredByItem) {
            // Check availability per item
            foreach ($requiredByItem as $invId => $need) {
                $stock = InventoryStock::where('inventory_item_id', $invId)
                    ->where('store_id', $storeId)
                    ->lockForUpdate()
                    ->first();
                $available = $stock?->quantity ?? 0.0;
                if ($available < $need) {
                    $name = optional(InventoryItem::find($invId))->name ?? ('#'.$invId);
                    throw new \RuntimeException("Insufficient stock for $name in selected store. Need $need, available $available.");
                }
            }

            // Create usage rows and decrement stock
            foreach ($normalized as $n) {
                StockUsage::create([
                    'inventory_item_id' => $n['inventory_item_id'],
                    'store_id' => $storeId,
                    'unit_id' => $n['unit_id'],
                    'entered_unit_id' => $n['entered_unit_id'] ?? $n['unit_id'],
                    'entered_quantity' => $n['entered_quantity'] ?? $n['quantity'],
                    'canonical_quantity' => $n['quantity'],
                    'quantity_used' => $n['quantity'],
                    'operation_type' => $n['operation_type'],
                    'usage_date' => $usageDate,
                    'created_by' => $createdBy,
                ]);

                $stock = InventoryStock::where('inventory_item_id', $n['inventory_item_id'])
                    ->where('store_id', $storeId)
                    ->lockForUpdate()
                    ->first();
                if ($stock) {
                    $stock->decrement('quantity', $n['quantity']);
                } else {
                    // No stock record, cannot decrement; guarded by availability check but be safe
                    throw new \RuntimeException('Stock record not found during usage update.');
                }
            }

            return count($normalized);
        });
    }
}
