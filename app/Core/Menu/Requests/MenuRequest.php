<?php

namespace App\Core\Menu\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $menuId = $this->route('menu')?->id ?? $this->route('menu');

        return [
            'tenant_id' => [
                'nullable',
                'integer',
                'exists:tenants,id',
            ],

            'parent_id' => [
                'nullable',
                'integer',
                'exists:menus,id',
            ],

            'module_id' => [
                'nullable',
                'integer',
                'exists:modules,id',
            ],

            'title' => [
                'required',
                'string',
                'max:150',
            ],

            'code' => [
                'required',
                'string',
                'max:150',
                'alpha_dash',
                Rule::unique('menus', 'code')->ignore($menuId),
            ],

            'route' => [
                'nullable',
                'string',
                'max:255',
            ],

            'icon' => [
                'nullable',
                'string',
                'max:100',
            ],

            'permission_name' => [
                'nullable',
                'string',
                'exists:permissions,name',
            ],

            'is_system' => [
                'required',
                'boolean',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],

            'display_order' => [
                'required',
                'integer',
                'min:0',
            ],

            'meta' => [
                'nullable',
                'array',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Menu title is required.',
            'code.required' => 'Menu code is required.',
            'code.unique' => 'This menu code already exists.',
            'code.alpha_dash' => 'Menu code may only contain letters, numbers, dashes and underscores.',
            'permission_name.exists' => 'Selected permission is invalid.',
        ];
    }
}