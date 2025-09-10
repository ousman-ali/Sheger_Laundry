<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('edit_inventory') ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('inventory')?->id;
        return [
            'name' => 'required|string|max:100|unique:inventory_items,name,' . $id,
            'unit_id' => 'required|exists:units,id',
            'minimum_stock' => 'nullable|numeric|min:0',
        ];
    }
}
