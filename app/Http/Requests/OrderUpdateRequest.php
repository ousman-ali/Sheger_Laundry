<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class OrderUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Order $order */
        $order = $this->route('order');
        if ($order && in_array($order->status, ['ready_for_pickup','delivered','cancelled'])) {
            return false; // prevent edits on terminal states for now
        }
        return true;
    }

    public function rules(): array
    {
        $applyAll = $this->boolean('apply_all_services');

        $base = [
            'customer_id' => 'required|exists:customers,id',
            // Allow Admins to override order_id on edit; unique except current record
            'order_id' => 'nullable|string|max:50|unique:orders,order_id,' . optional($this->route('order'))->id,
            'remark_preset_ids' => 'sometimes|array',
            'remark_preset_ids.*' => 'integer|exists:remark_presets,id',
            'apply_all_services' => 'sometimes|boolean',
            'default_urgency_tier_id' => 'nullable|exists:urgency_tiers,id',
            'discount' => 'nullable|numeric|min:0',
            'appointment_date' => 'nullable|date',
            'pickup_date' => 'nullable|date|after_or_equal:appointment_date',
            'remarks' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'sometimes|exists:order_items,id',
            'items.*.cloth_item_id' => 'required|exists:cloth_items,id',
            'items.*.unit_id' => 'required|exists:units,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.remarks' => 'nullable|string|max:500',
            'items.*.remark_preset_ids' => 'sometimes|array',
            'items.*.remark_preset_ids.*' => 'integer|exists:remark_presets,id',
            'items.*.default_urgency_tier_id' => 'nullable|exists:urgency_tiers,id',
        ];

        if (!$applyAll) {
            $base = array_merge($base, [
                'items.*.services' => 'required|array|min:1',
                'items.*.services.*.service_row_id' => 'sometimes|exists:order_item_services,id',
                'items.*.services.*.service_id' => 'required|exists:services,id',
                'items.*.services.*.quantity' => 'required|numeric|min:0.01',
                'items.*.services.*.urgency_tier_id' => 'nullable|exists:urgency_tiers,id',
                'items.*.services.*._delete' => 'sometimes|boolean',
            ]);
        } else {
            $base = array_merge($base, [
                'items.*.services' => 'sometimes|array',
                'items.*.services.*.service_row_id' => 'sometimes|exists:order_item_services,id',
                'items.*.services.*.service_id' => 'sometimes|exists:services,id',
                'items.*.services.*.quantity' => 'sometimes|numeric|min:0.01',
                'items.*.services.*.urgency_tier_id' => 'nullable|exists:urgency_tiers,id',
                'items.*.services.*._delete' => 'sometimes|boolean',
            ]);
        }

        return $base;
    }

    protected function prepareForValidation(): void
    {
        // Only allow manual order_id override for sufficiently privileged users (Admin-like)
        $data = $this->all();
        $user = Auth::user();
        $isAdminLike = $user ? (Gate::forUser($user)->allows('create_orders') && Gate::forUser($user)->allows('edit_orders')) : false;
        if (isset($data['order_id']) && !$isAdminLike) {
            unset($data['order_id']);
        }
        $this->replace($data);
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            // Validate unit compatibility on update
            $data = $this->all();
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
            if ($this->boolean('apply_all_services') && !$v->errors()->any()) {
                $this->expandServices();
            }
        });
    }

    protected function expandServices(): void
    {
        $allServiceIds = \App\Models\Service::query()->pluck('id')->all();
        $data = $this->all();
        $globalUrgency = $data['default_urgency_tier_id'] ?? null;

        foreach ($data['items'] as $idx => $item) {
            $perItemUrgency = $item['default_urgency_tier_id'] ?? $globalUrgency;
            $itemQty = (float)($item['quantity'] ?? 1);
            $explicitRemovals = [];
            $overrides = [];
            if (!empty($item['services'])) {
                foreach ($item['services'] as $svc) {
                    if (!isset($svc['service_id'])) continue;
                    $sid = (int)$svc['service_id'];
                    if (!empty($svc['_delete'])) {
                        $explicitRemovals[$sid] = true;
                        continue;
                    }
                    $overrides[$sid] = [
                        'service_id' => $sid,
                        'service_row_id' => $svc['service_row_id'] ?? null,
                        'quantity' => isset($svc['quantity']) ? (float)$svc['quantity'] : $itemQty,
                        'urgency_tier_id' => $svc['urgency_tier_id'] ?? $perItemUrgency,
                    ];
                }
            }
            $final = array_values($overrides);
            foreach ($allServiceIds as $sid) {
                if (!isset($overrides[$sid]) && !isset($explicitRemovals[$sid])) {
                    $final[] = [
                        'service_id' => $sid,
                        'quantity' => $itemQty,
                        'urgency_tier_id' => $perItemUrgency,
                    ];
                }
            }
            $data['items'][$idx]['services'] = $final;
        }

        $this->replace($data);

        validator($data, [
            'items.*.services' => 'required|array|min:1',
            'items.*.services.*.service_id' => 'required|exists:services,id',
            'items.*.services.*.quantity' => 'required|numeric|min:0.01',
            'items.*.services.*.urgency_tier_id' => 'nullable|exists:urgency_tiers,id',
        ])->validate();
    }
}
