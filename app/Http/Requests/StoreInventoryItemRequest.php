<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create_inventory') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100|unique:inventory_items,name',
            'unit_id' => 'required|exists:units,id',
            'minimum_stock' => 'nullable|numeric|min:0',
        ];
    }
}
