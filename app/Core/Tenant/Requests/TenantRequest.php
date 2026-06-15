<?php

namespace App\Core\Tenant\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->route('tenant')?->id ?? $this->route('tenant');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'code' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('tenants', 'code')->ignore($tenantId),
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:50',
            ],

            'logo' => [
                'nullable',
                'string',
                'max:255',
            ],

            'theme_color' => [
                'nullable',
                'string',
                'max:30',
            ],

            'timezone' => [
                'required',
                'string',
                'max:100',
            ],

            'locale' => [
                'required',
                'string',
                'max:10',
            ],

            'status' => [
                'required',
                Rule::in([
                    'active',
                    'inactive',
                    'pending',
                    'suspended',
                    'archived',
                ]),
            ],

            'subscription_status' => [
                'required',
                Rule::in([
                    'trial',
                    'active',
                    'expired',
                    'cancelled',
                    'suspended',
                ]),
            ],

            'subscription_start_date' => [
                'nullable',
                'date',
            ],

            'subscription_end_date' => [
                'nullable',
                'date',
                'after_or_equal:subscription_start_date',
            ],

            'meta' => [
                'nullable',
                'array',
            ],
            'admin_name' => [
                $this->isMethod('post') ? 'required' : 'nullable',
                'string',
                'max:255',
            ],

            'admin_email' => [
                $this->isMethod('post') ? 'required' : 'nullable',
                'email',
                'max:255',
            ],

            'admin_password' => [
                $this->isMethod('post') ? 'required' : 'nullable',
                'string',
                'min:6',
            ],

            'module_ids' => [
                'nullable',
                'array',
            ],

            'module_ids.*' => [
                'integer',
                'exists:modules,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tenant name is required.',
            'code.required' => 'Tenant code is required.',
            'code.unique' => 'This tenant code already exists.',
            'code.alpha_dash' => 'Tenant code may only contain letters, numbers, dashes and underscores.',
            'timezone.required' => 'Timezone is required.',
            'locale.required' => 'Locale is required.',
            'subscription_end_date.after_or_equal' => 'Subscription end date must be after or equal to start date.',
        ];
    }
}