<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('edit_stores') || $this->user()?->hasRole('Admin');
    }

    public function rules(): array
    {
        $id = $this->route('store')?->id ?? null;
        return [
            'name' => 'required|string|max:100|unique:stores,name,' . $id,
            'type' => 'required|in:main,sub',
            'description' => 'nullable|string|max:1000',
        ];
    }
}
