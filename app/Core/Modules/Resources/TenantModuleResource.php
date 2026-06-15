<?php

namespace App\Core\Modules\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantModuleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'tenant_id' => $this->pivot?->tenant_id,
            'module_id' => $this->id,

            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'icon' => $this->icon,

            'is_core' => $this->is_core,
            'is_active' => $this->is_active,

            'is_enabled' => (bool) $this->pivot?->is_enabled,
            'enabled_at' => $this->pivot?->enabled_at,
            'disabled_at' => $this->pivot?->disabled_at,

            'settings' => $this->pivot?->settings,
        ];
    }
}