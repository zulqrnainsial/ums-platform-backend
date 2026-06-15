<?php

namespace App\Core\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
                'max:255',
            ],

            'password' => [
                'required',
                'string',
                'min:6',
            ],

            /*
             |--------------------------------------------------------------------------
             | Tenant Code
             |--------------------------------------------------------------------------
             | Super Admin can login without tenant_code.
             | Tenant users should provide tenant_code.
             | Later we can also detect tenant from domain/subdomain.
             */
            'tenant_code' => [
                'nullable',
                'string',
                'max:100',
            ],

            'device_name' => [
                'nullable',
                'string',
                'max:100',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email is required.',
            'email.email' => 'Please enter a valid email address.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 6 characters.',
        ];
    }
}