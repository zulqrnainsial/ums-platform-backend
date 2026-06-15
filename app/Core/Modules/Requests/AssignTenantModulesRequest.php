<?php

namespace App\Core\Modules\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignTenantModulesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'module_ids' => [
                'required',
                'array',
            ],

            'module_ids.*' => [
                'required',
                'integer',
                'exists:modules,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'module_ids.required' => 'Please select at least one module.',
            'module_ids.*.exists' => 'One or more selected modules are invalid.',
        ];
    }
}