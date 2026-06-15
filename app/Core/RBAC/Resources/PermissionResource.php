<?php

namespace App\Core\RBAC\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $parts = explode('.', $this->name);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'guard_name' => $this->guard_name,

            'module' => $parts[0] ?? null,
            'entity' => $parts[1] ?? null,
            'action' => $parts[2] ?? null,

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}