<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create_stores') || $this->user()?->hasRole('Admin');
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100|unique:stores,name',
            'type' => 'required|in:main,sub',
            'description' => 'nullable|string|max:1000',
        ];
    }
}
