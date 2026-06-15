<?php

namespace App\Core\Modules\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $moduleId = $this->route('module')?->id ?? $this->route('module');

        return [
            'parent_id' => [
                'nullable',
                'integer',
                'exists:modules,id',
            ],

            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'code' => [
                'required',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique('modules', 'code')->ignore($moduleId),
            ],

            'description' => [
                'nullable',
                'string',
                'max:500',
            ],

            'icon' => [
                'nullable',
                'string',
                'max:100',
            ],

            'is_core' => [
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

            'settings_schema' => [
                'nullable',
                'array',
            ],

            'meta' => [
                'nullable',
                'array',
            ],

            'dependency_ids' => [
                'nullable',
                'array',
            ],

            'dependency_ids.*' => [
                'integer',
                'exists:modules,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Module name is required.',
            'code.required' => 'Module code is required.',
            'code.unique' => 'This module code already exists.',
            'code.alpha_dash' => 'Module code may only contain letters, numbers, dashes and underscores.',
        ];
    }
}