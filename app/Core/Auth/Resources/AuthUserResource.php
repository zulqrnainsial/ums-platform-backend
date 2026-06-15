<?php

namespace App\Core\Auth\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,

            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $this->avatar,

            'user_type' => $this->user_type,
            'status' => $this->status,

            'tenant' => $this->whenLoaded('tenant', function () {
                return [
                    'id' => $this->tenant?->id,
                    'name' => $this->tenant?->name,
                    'code' => $this->tenant?->code,
                    'status' => $this->tenant?->status,
                    'timezone' => $this->tenant?->timezone,
                    'locale' => $this->tenant?->locale,
                    'theme_color' => $this->tenant?->theme_color,
                    'logo' => $this->tenant?->logo,
                ];
            }),

            'roles' => method_exists($this->resource, 'getRoleNames')
                ? $this->getRoleNames()
                : [],

            'permissions' => method_exists($this->resource, 'getAllPermissions')
                ? $this->getAllPermissions()->pluck('name')->values()
                : [],
        ];
    }
}