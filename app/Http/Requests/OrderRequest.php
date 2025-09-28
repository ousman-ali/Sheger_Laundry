<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.cloth_item_id' => 'required|exists:cloth_items,id',
            'items.*.unit_id' => 'required|exists:units,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.remarks' => 'nullable|string|max:500',
            'items.*.services' => 'required|array|min:1',
            'items.*.services.*.service_id' => 'required|exists:services,id',
            'items.*.services.*.quantity' => 'required|numeric|min:0.01',
            'items.*.services.*.urgency_tier_id' => 'nullable|exists:urgency_tiers,id',
            'discount' => 'nullable|numeric|min:0|max:1000',
            'appointment_date' => 'nullable|date|after:now',
            'pickup_date' => 'nullable|date|after_or_equal:appointment_date',
            'date_type' => 'required|in:GC,EC',
            'remarks' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'customer_id.required' => 'Please select a customer.',
            'customer_id.exists' => 'The selected customer is invalid.',
            'items.required' => 'At least one item is required.',
            'items.min' => 'At least one item is required.',
            'items.*.cloth_item_id.required' => 'Please select a cloth item.',
            'items.*.cloth_item_id.exists' => 'The selected cloth item is invalid.',
            'items.*.unit_id.required' => 'Please select a unit.',
            'items.*.unit_id.exists' => 'The selected unit is invalid.',
            'items.*.quantity.required' => 'Quantity is required.',
            'items.*.quantity.numeric' => 'Quantity must be a number.',
            'items.*.quantity.min' => 'Quantity must be greater than 0.',
            'items.*.services.required' => 'At least one service is required for each item.',
            'items.*.services.min' => 'At least one service is required for each item.',
            'items.*.services.*.service_id.required' => 'Please select a service.',
            'items.*.services.*.service_id.exists' => 'The selected service is invalid.',
            'items.*.services.*.quantity.required' => 'Service quantity is required.',
            'items.*.services.*.quantity.numeric' => 'Service quantity must be a number.',
            'items.*.services.*.quantity.min' => 'Service quantity must be greater than 0.',
            'discount.numeric' => 'Discount must be a number.',
            'discount.min' => 'Discount cannot be negative.',
            'discount.max' => 'Discount cannot exceed 1000.',
            'appointment_date.date' => 'Appointment date must be a valid date.',
            'appointment_date.after' => 'Appointment date must be in the future.',
            'pickup_date.date' => 'Pickup date must be a valid date.',
            'pickup_date.after_or_equal' => 'Pickup date must be after or equal to appointment date.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'customer_id' => 'customer',
            'cloth_item_id' => 'cloth item',
            'unit_id' => 'unit',
            'service_id' => 'service',
            'urgency_tier_id' => 'urgency tier',
        ];
    }
} 