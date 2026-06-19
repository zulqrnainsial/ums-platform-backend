<?php

namespace App\Modules\ResourceManagement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResourceManagementService
{
    public function context(array $filters = []): array
    {
        $tenantId = $this->tenantId();

        return [
            'buildings' => $this->buildingOptions($tenantId),
            'floors' => $this->floorOptions($tenantId, $filters),
            'room_types' => $this->roomTypeOptions($tenantId),
            'room_features' => $this->roomFeatureOptions($tenantId),
            'resource_types' => $this->resourceTypeOptions($tenantId),
        ];
    }

    public function buildings(array $filters = []): array
    {
        $tenantId = $this->tenantId();

        $query = DB::table('campus_buildings')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at');

        foreach (['campus_id', 'faculty_id', 'department_id', 'program_id', 'building_type_code', 'ownership_scope_code', 'status_code'] as $field) {
            if (!empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';

            $query->where(function ($q) use ($search) {
                $q->where('building_code', 'like', $search)
                    ->orWhere('building_name', 'like', $search);
            });
        }

        return $query
            ->orderBy('building_name')
            ->paginate((int) ($filters['per_page'] ?? 15))
            ->toArray();
    }

    public function createBuilding(array $data): array
    {
        $tenantId = $this->tenantId();

        $payload = $this->onlyColumns('campus_buildings', array_merge($data, [
            'tenant_id' => $tenantId,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        $id = DB::table('campus_buildings')->insertGetId($payload);

        return (array) DB::table('campus_buildings')->where('id', $id)->first();
    }

    public function floors(array $filters = []): array
    {
        $tenantId = $this->tenantId();

        $query = DB::table('campus_floors as cf')
            ->leftJoin('campus_buildings as cb', 'cb.id', '=', 'cf.campus_building_id')
            ->where('cf.tenant_id', $tenantId)
            ->whereNull('cf.deleted_at');

        if (!empty($filters['campus_building_id'])) {
            $query->where('cf.campus_building_id', $filters['campus_building_id']);
        }

        if (!empty($filters['status_code'])) {
            $query->where('cf.status_code', $filters['status_code']);
        }

        return $query
            ->select([
                'cf.*',
                'cb.building_code',
                'cb.building_name',
            ])
            ->orderBy('cb.building_name')
            ->orderBy('cf.floor_number')
            ->paginate((int) ($filters['per_page'] ?? 15))
            ->toArray();
    }

    public function createFloor(array $data): array
    {
        $tenantId = $this->tenantId();

        $building = DB::table('campus_buildings')
            ->where('tenant_id', $tenantId)
            ->where('id', $data['campus_building_id'])
            ->whereNull('deleted_at')
            ->first();

        abort_if(!$building, 404, 'Building not found.');

        $payload = $this->onlyColumns('campus_floors', array_merge($data, [
            'tenant_id' => $tenantId,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        $id = DB::table('campus_floors')->insertGetId($payload);

        return (array) DB::table('campus_floors')->where('id', $id)->first();
    }

    public function rooms(array $filters = []): array
    {
        $tenantId = $this->tenantId();

        $query = DB::table('rooms as r')
            ->leftJoin('campus_buildings as cb', 'cb.id', '=', 'r.campus_building_id')
            ->leftJoin('campus_floors as cf', 'cf.id', '=', 'r.campus_floor_id')
            ->leftJoin('room_types as rt', 'rt.id', '=', 'r.room_type_id')
            ->where('r.tenant_id', $tenantId)
            ->whereNull('r.deleted_at');

        foreach ([
            'campus_building_id',
            'campus_floor_id',
            'faculty_id',
            'department_id',
            'program_id',
            'room_type_id',
            'room_type_code',
            'is_lab',
            'is_shared',
            'is_active_for_timetable',
            'status_code',
        ] as $field) {
            if ($filters[$field] ?? null !== null) {
                $query->where("r.$field", $filters[$field]);
            }
        }

        if (!empty($filters['min_capacity'])) {
            $query->where('r.capacity', '>=', (int) $filters['min_capacity']);
        }

        if (!empty($filters['requires_multimedia'])) {
            $query->where('r.has_multimedia', true);
        }

        if (!empty($filters['requires_projector'])) {
            $query->where('r.has_projector', true);
        }

        if (!empty($filters['requires_computers'])) {
            $query->where('r.has_computers', true);
        }

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';

            $query->where(function ($q) use ($search) {
                $q->where('r.room_code', 'like', $search)
                    ->orWhere('r.room_name', 'like', $search)
                    ->orWhere('cb.building_name', 'like', $search);
            });
        }

        return $query
            ->select([
                'r.*',
                'cb.building_code',
                'cb.building_name',
                'cf.floor_code',
                'cf.floor_name',
                'rt.name as room_type_name',
            ])
            ->orderBy('cb.building_name')
            ->orderBy('r.room_code')
            ->paginate((int) ($filters['per_page'] ?? 15))
            ->toArray();
    }

    public function createRoom(array $data): array
    {
        $tenantId = $this->tenantId();

        if (!empty($data['room_type_id'])) {
            $type = DB::table('room_types')
                ->where('id', $data['room_type_id'])
                ->where(function ($q) use ($tenantId) {
                    $q->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
                })
                ->first();

            if ($type) {
                $data['room_type_code'] = $data['room_type_code'] ?? $type->code;
                $data['is_lab'] = $data['is_lab'] ?? (bool) $type->is_lab_space;
            }
        }

        $payload = $this->onlyColumns('rooms', array_merge($data, [
            'tenant_id' => $tenantId,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        $id = DB::table('rooms')->insertGetId($payload);

        return $this->room($id);
    }

    public function availableRooms(array $filters = []): array
    {
        $tenantId = $this->tenantId();

        $query = DB::table('rooms as r')
            ->leftJoin('campus_buildings as cb', 'cb.id', '=', 'r.campus_building_id')
            ->leftJoin('campus_floors as cf', 'cf.id', '=', 'r.campus_floor_id')
            ->where('r.tenant_id', $tenantId)
            ->where('r.status_code', 'active')
            ->where('r.is_active_for_timetable', true)
            ->whereNull('r.deleted_at');

        if (!empty($filters['room_type_code'])) {
            $query->where('r.room_type_code', $filters['room_type_code']);
        }

        if (!empty($filters['required_capacity'])) {
            $query->where('r.capacity', '>=', (int) $filters['required_capacity']);
        }

        if (!empty($filters['requires_lab'])) {
            $query->where('r.is_lab', true);
        }

        if (!empty($filters['requires_multimedia'])) {
            $query->where('r.has_multimedia', true);
        }

        if (!empty($filters['requires_projector'])) {
            $query->where('r.has_projector', true);
        }

        if (!empty($filters['requires_computers'])) {
            $query->where('r.has_computers', true);
        }

        return $query
            ->select([
                'r.id',
                'r.room_code',
                'r.room_name',
                'r.room_type_code',
                'r.capacity',
                'r.exam_capacity',
                'r.has_multimedia',
                'r.has_projector',
                'r.has_smart_board',
                'r.has_computers',
                'r.computer_count',
                'r.is_lab',
                'cb.building_name',
                'cf.floor_name',
            ])
            ->orderBy('r.capacity')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();
    }

    private function room(int $id): array
    {
        return (array) DB::table('rooms')->where('id', $id)->first();
    }

    private function buildingOptions(int $tenantId): array
    {
        if (!Schema::hasTable('campus_buildings')) {
            return [];
        }

        return DB::table('campus_buildings')
            ->where('tenant_id', $tenantId)
            ->where('status_code', 'active')
            ->whereNull('deleted_at')
            ->select('id as value', DB::raw("CONCAT(building_code, ' - ', building_name) as label"))
            ->orderBy('building_name')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();
    }

    private function floorOptions(int $tenantId, array $filters): array
    {
        if (!Schema::hasTable('campus_floors')) {
            return [];
        }

        $query = DB::table('campus_floors')
            ->where('tenant_id', $tenantId)
            ->where('status_code', 'active')
            ->whereNull('deleted_at');

        if (!empty($filters['campus_building_id'])) {
            $query->where('campus_building_id', $filters['campus_building_id']);
        }

        return $query
            ->select('id as value', DB::raw("CONCAT(floor_code, ' - ', floor_name) as label"), 'campus_building_id')
            ->orderBy('floor_number')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();
    }

    private function roomTypeOptions(int $tenantId): array
    {
        if (!Schema::hasTable('room_types')) {
            return [];
        }

        return DB::table('room_types')
            ->where(function ($q) use ($tenantId) {
                $q->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
            })
            ->where('status_code', 'active')
            ->select('id as value', 'name as label', 'code', 'is_lab_space')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();
    }

    private function roomFeatureOptions(int $tenantId): array
    {
        if (!Schema::hasTable('room_features')) {
            return [];
        }

        return DB::table('room_features')
            ->where(function ($q) use ($tenantId) {
                $q->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
            })
            ->where('status_code', 'active')
            ->select('id as value', 'feature_name as label', 'feature_code')
            ->orderBy('sort_order')
            ->orderBy('feature_name')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();
    }

    private function resourceTypeOptions(int $tenantId): array
    {
        if (!Schema::hasTable('resource_types')) {
            return [];
        }

        return DB::table('resource_types')
            ->where(function ($q) use ($tenantId) {
                $q->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
            })
            ->where('status_code', 'active')
            ->select('id as value', 'name as label', 'code')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();
    }

    private function onlyColumns(string $table, array $data): array
    {
        return collect($data)
            ->filter(fn ($value, $column) => Schema::hasColumn($table, $column))
            ->toArray();
    }

    private function tenantId(): int
    {
        return (int) (auth()->user()?->tenant_id ?? 0);
    }
}