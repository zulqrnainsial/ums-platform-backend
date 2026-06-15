<?php

namespace App\Core\User\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id ?? $this->route('user');

        return [
            'tenant_id' => [
                'nullable',
                'integer',
                'exists:tenants,id',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->where(fn ($q) => $q->where('tenant_id', $this->input('tenant_id')))
                    ->ignore($userId),
            ],

            'password' => [
                $this->isMethod('post') ? 'required' : 'nullable',
                'string',
                'min:6',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:50',
            ],

            'user_type' => [
                'required',
                Rule::in([
                    'tenant_admin',
                    'admin',
                    'registrar',
                    'accountant',
                    'teacher',
                    'student',
                    'parent',
                    'employee',
                    'custom',
                ]),
            ],

            'status' => [
                'required',
                Rule::in(['active', 'inactive', 'suspended']),
            ],

            'roles' => [
                'nullable',
                'array',
            ],

            'roles.*' => [
                'string',
                'exists:roles,name',
            ],
        ];
    }
}