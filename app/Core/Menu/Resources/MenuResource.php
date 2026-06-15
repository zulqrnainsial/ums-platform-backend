<?php

namespace App\Core\Menu\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'tenant_id' => $this->tenant_id,
            'parent_id' => $this->parent_id,
            'module_id' => $this->module_id,

            'title' => $this->title,
            'code' => $this->code,
            'route' => $this->route,
            'icon' => $this->icon,
            'permission_name' => $this->permission_name,

            'is_system' => $this->is_system,
            'is_active' => $this->is_active,
            'display_order' => $this->display_order,

            'meta' => $this->meta,

            'module' => $this->whenLoaded('module', function () {
                return [
                    'id' => $this->module?->id,
                    'name' => $this->module?->name,
                    'code' => $this->module?->code,
                ];
            }),

            'parent' => $this->whenLoaded('parent', function () {
                return [
                    'id' => $this->parent?->id,
                    'title' => $this->parent?->title,
                    'code' => $this->parent?->code,
                ];
            }),

            'children' => MenuResource::collection($this->whenLoaded('children')),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}