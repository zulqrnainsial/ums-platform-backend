<?php

namespace App\Core\Tenant\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'name' => $this->name,
            'code' => $this->code,

            'email' => $this->email,
            'phone' => $this->phone,

            'logo' => $this->logo,
            'theme_color' => $this->theme_color,

            'timezone' => $this->timezone,
            'locale' => $this->locale,

            'status' => $this->status,
            'subscription_status' => $this->subscription_status,

            'subscription_start_date' => $this->subscription_start_date?->format('Y-m-d'),
            'subscription_end_date' => $this->subscription_end_date?->format('Y-m-d'),

            'meta' => $this->meta,

            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}