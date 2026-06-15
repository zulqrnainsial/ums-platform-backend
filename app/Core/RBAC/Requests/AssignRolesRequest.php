<?php

namespace App\Core\RBAC\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignRolesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'roles' => [
                'required',
                'array',
            ],

            'roles.*' => [
                'required',
                'string',
                'exists:roles,name',
            ],
        ];
    }
}