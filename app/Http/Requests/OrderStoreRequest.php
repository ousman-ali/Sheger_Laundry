<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\PricingTier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class OrderStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // permissions middleware handles access
    }

    public function rules(): array
    {
        $applyAll = $this->boolean('apply_all_services');

        $base = [
            'customer_id' => 'required|exists:customers,id',
            'order_id' => 'nullable|string|max:50|unique:orders,order_id',
            'remark_preset_ids' => 'sometimes|array',
            'remark_preset_ids.*' => 'integer|exists:remark_presets,id',
            'apply_all_services' => 'sometimes|boolean',
            'mark_all_urgent' => 'sometimes|boolean',
            'all_urgency_tier_id' => 'nullable|exists:urgency_tiers,id',
            'default_urgency_tier_id' => 'nullable|exists:urgency_tiers,id',
            'items' => 'required|array|min:1',
            'items.*.cloth_item_id' => 'required|exists:cloth_items,id',
            'items.*.unit_id' => 'required|exists:units,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.remarks' => 'nullable|string|max:500',
            'items.*.remark_preset_ids' => 'sometimes|array',
            'items.*.remark_preset_ids.*' => 'integer|exists:remark_presets,id',
            'items.*.default_urgency_tier_id' => 'nullable|exists:urgency_tiers,id',
            // Services are normalized in prepareForValidation; emptiness is checked in withValidator
            'items.*.services' => 'sometimes|array',
            'items.*.services.*.service_id' => 'required|exists:services,id',
            'items.*.services.*.quantity' => 'required|numeric|min:0.01',
            'items.*.services.*.urgency_tier_id' => 'nullable|exists:urgency_tiers,id',
            'discount' => 'nullable|numeric|min:0',
            'appointment_date' => 'nullable|date',
            'pickup_date' => 'nullable|date|after_or_equal:appointment_date',
            'remarks' => 'nullable|string|max:1000',
        ];

        return $base;
    }

    public function messages(): array
    {
        return [
            'items.*.services.required' => 'Select at least one service for each item (manual mode).',
        ];
    }

    protected function prepareForValidation(): void
    {
        $data = $this->all();
        // Only allow manual order_id override for admins; otherwise discard provided value
    $user = Auth::user();
    $isAdmin = $user ? Gate::forUser($user)->allows('create_orders') && Gate::forUser($user)->allows('edit_orders') : false;
    if (isset($data['order_id']) && !$isAdmin) {
            unset($data['order_id']);
        }
        $applyAll = $this->boolean('apply_all_services');
        $markAllUrgent = $this->boolean('mark_all_urgent');
        $globalUrgency = $data['all_urgency_tier_id'] ?? null;

        $data['apply_all_services'] = $applyAll;
        $data['mark_all_urgent'] = $markAllUrgent;

        // Build cloth_item_id => [service_id] map from pricing tiers
        $pricingMap = PricingTier::query()
            ->get()
            ->groupBy('cloth_item_id')
            ->map(fn($rows) => $rows->pluck('service_id')->all());

        if (!empty($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $idx => $item) {
                $clothId = $item['cloth_item_id'] ?? null;
                $allowed = $pricingMap[$clothId] ?? [];
                $allowed = array_map('intval', (array)$allowed);
                $itemQty = isset($item['quantity']) ? (float)$item['quantity'] : 1.0;
                $itemUrg = $item['default_urgency_tier_id'] ?? ($data['default_urgency_tier_id'] ?? null);

                $normalized = [];
                if ($applyAll) {
                    foreach ($allowed as $sid) {
                        $normalized[] = [
                            'service_id' => (int)$sid,
                            'quantity' => $itemQty,
                            'urgency_tier_id' => $itemUrg,
                        ];
                    }
                } else {
                    $services = $item['services'] ?? [];
                    if (is_array($services)) {
                        foreach ($services as $key => $svc) {
                            if (!is_array($svc)) { continue; }
                            $serviceId = isset($svc['service_id']) ? (int)$svc['service_id'] : (int)$key;
                            // Normalize for strict compare safety
                            $serviceId = (int)$serviceId;
                            // When using keyed checkbox-matrix, ensure it was actually selected
                            if (!isset($svc['service_id']) && !isset($svc['selected'])) { continue; }
                            if (!$serviceId) { continue; }
                            $qty = isset($svc['quantity']) && $svc['quantity'] !== '' ? (float)$svc['quantity'] : $itemQty;
                            if ($qty <= 0) { continue; }
                            $normalized[] = [
                                'service_id' => $serviceId,
                                'quantity' => $qty,
                                'urgency_tier_id' => $svc['urgency_tier_id'] ?? $itemUrg,
                            ];
                        }
                    }
                }

                if ($markAllUrgent && $globalUrgency) {
                    foreach ($normalized as &$row) { $row['urgency_tier_id'] = $globalUrgency; }
                    unset($row);
                }

                $data['items'][$idx]['services'] = array_values($normalized);
            }
        }

        $this->replace($data);
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            $data = $this->all();
            // Validate unit compatibility: selected unit must be convertible to the cloth item's unit
            foreach ($data['items'] ?? [] as $idx => $item) {
                $clothId = $item['cloth_item_id'] ?? null;
                $unitId = $item['unit_id'] ?? null;
                if ($clothId && $unitId) {
                    $cloth = \App\Models\ClothItem::find($clothId);
                    $selectedUnit = \App\Models\Unit::find($unitId);
                    $canonicalUnit = $cloth ? \App\Models\Unit::find($cloth->unit_id) : null;
                    if ($selectedUnit && $canonicalUnit && !$selectedUnit->isConvertibleTo($canonicalUnit)) {
                        $v->errors()->add("items.$idx.unit_id", "Unit '{$selectedUnit->name}' is not compatible with cloth item unit '{$canonicalUnit->name}'.");
                    }
                }
            }
            // Build pricing map for validation of combinations
            $pricingMap = PricingTier::query()
                ->get()
                ->groupBy('cloth_item_id')
                ->map(fn($rows) => $rows->pluck('service_id')->all());

            foreach ($data['items'] ?? [] as $idx => $item) {
                $services = $item['services'] ?? [];
                if (empty($services)) {
                    $v->errors()->add("items.$idx.services", 'Please choose at least one service.');
                    continue;
                }
                $clothId = $item['cloth_item_id'] ?? null;
                $allowed = array_map('intval', (array)($pricingMap[$clothId] ?? []));
                $validCount = 0;
                foreach ($services as $svc) {
                    if (!is_array($svc)) { continue; }
                    $sid = isset($svc['service_id']) ? (int)$svc['service_id'] : null;
                    if (!$sid) { continue; }
                    if (!in_array($sid, $allowed, true)) {
                        $v->errors()->add("items.$idx.services", "Service $sid has no pricing tier for cloth item $clothId.");
                        continue;
                    }
                    $qty = (float)($svc['quantity'] ?? 0);
                    if ($qty <= 0) {
                        $v->errors()->add("items.$idx.services", "Quantity for service $sid must be greater than 0.");
                        continue;
                    }
                    $validCount++;
                }
                if ($validCount === 0) {
                    $v->errors()->add("items.$idx.services", 'Please choose at least one service.');
                }
            }
        });
    }

    protected function expandServices($pricingMap = null): void
    {
        $data = $this->all();
        foreach ($data['items'] as $idx => $item) {
            $clothId = $item['cloth_item_id'];
            $serviceIds = $pricingMap[$clothId] ?? [];
            $urgency = $item['default_urgency_tier_id'] ?? $data['default_urgency_tier_id'] ?? null;
            $qty = (float)($item['quantity'] ?? 1);
            $services = [];
            foreach ($serviceIds as $sid) {
                $services[] = [
                    'service_id' => $sid,
                    'quantity' => $qty,
                    'urgency_tier_id' => $urgency,
                ];
            }
            if (empty($services)) {
                // Fallback: add validation error if no pricing tiers
                // We cannot push directly after validation cycle; mark placeholder to fail later
                $services = [];
            }
            $data['items'][$idx]['services'] = $services;
        }
        $this->replace($data);
    }
}
