<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePricingTierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create_pricing') ?? false;
    }

    public function rules(): array
    {
        return [
            'cloth_item_id' => ['required','integer','exists:cloth_items,id',
                Rule::unique('pricing_tiers', 'cloth_item_id')
                    ->where(fn($q) => $q->where('service_id', $this->input('service_id')))
            ],
            'service_id' => ['required','integer','exists:services,id'],
            'price' => ['required','numeric','min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'cloth_item_id.unique' => 'Pricing tier already exists for this cloth item and service.',
        ];
    }
}
