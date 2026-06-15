<?php

namespace App\Core\Modules\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ModuleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,

            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'icon' => $this->icon,

            'is_core' => $this->is_core,
            'is_active' => $this->is_active,
            'display_order' => $this->display_order,

            'settings_schema' => $this->settings_schema,
            'meta' => $this->meta,

            'parent' => $this->whenLoaded('parent', function () {
                return [
                    'id' => $this->parent?->id,
                    'name' => $this->parent?->name,
                    'code' => $this->parent?->code,
                ];
            }),

            'dependencies' => $this->whenLoaded('dependencies', function () {
                return $this->dependencies->map(fn ($module) => [
                    'id' => $module->id,
                    'name' => $module->name,
                    'code' => $module->code,
                ])->values();
            }),

            'dependency_ids' => $this->whenLoaded('dependencies', function () {
                return $this->dependencies->pluck('id')->values();
            }),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}