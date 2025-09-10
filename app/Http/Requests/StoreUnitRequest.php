<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUnitRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->can('create_units');
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:50|unique:units,name',
            'parent_unit_id' => 'nullable|exists:units,id',
            // conversion_factor is required IF a parent is selected; otherwise must be null
            'conversion_factor' => [
                'nullable',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) {
                    $hasParent = (bool) $this->input('parent_unit_id');
                    if ($hasParent && ($value === null || $value === '')) {
                        $fail('Conversion Factor is required when a parent unit is selected.');
                    }
                    if (!$hasParent && ($value !== null && $value !== '')) {
                        $fail('Conversion Factor must be empty when no parent unit is selected.');
                    }
                },
            ],
        ];
    }
}
