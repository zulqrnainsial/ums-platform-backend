<?php

namespace App\Core\User\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'tenant' => $this->whenLoaded('tenant', fn () => [
                'id' => $this->tenant?->id,
                'name' => $this->tenant?->name,
                'code' => $this->tenant?->code,
            ]),

            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'user_type' => $this->user_type,
            'status' => $this->status,

            'roles' => method_exists($this->resource, 'getRoleNames')
                ? $this->getRoleNames()
                : [],

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}