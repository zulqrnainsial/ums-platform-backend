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

    return DB::table('buildings as b')
        ->leftJoin('campuses as c', 'c.id', '=', 'b.campus_id')
        ->where('b.tenant_id', $tenantId)
        ->whereNull('b.deleted_at')
        ->when(!empty($filters['campus_id']), fn ($q) => $q->where('b.campus_id', $filters['campus_id']))
        ->when(!empty($filters['status_code']), fn ($q) => $q->where('b.status', $filters['status_code']))
        ->select([
            'b.id',
            'b.tenant_id',
            'b.campus_id',
            'c.name as campus_name',
            'b.code as building_code',
            'b.name as building_name',
            'b.total_floors',
            DB::raw("'academic' as building_type_code"),
            DB::raw("'shared' as ownership_scope_code"),
            'b.description as location_description',
            'b.status as status_code',
            'b.created_at',
            'b.updated_at',
        ])
        ->orderBy('b.name')
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

    return DB::table('floors as f')
        ->leftJoin('buildings as b', 'b.id', '=', 'f.building_id')
        ->leftJoin('campuses as c', 'c.id', '=', 'f.campus_id')
        ->where('f.tenant_id', $tenantId)
        ->whereNull('f.deleted_at')
        ->when(!empty($filters['campus_building_id']), fn ($q) => $q->where('f.building_id', $filters['campus_building_id']))
        ->when(!empty($filters['building_id']), fn ($q) => $q->where('f.building_id', $filters['building_id']))
        ->when(!empty($filters['campus_id']), fn ($q) => $q->where('f.campus_id', $filters['campus_id']))
        ->when(!empty($filters['status_code']), fn ($q) => $q->where('f.status', $filters['status_code']))
        ->select([
            'f.id',
            'f.tenant_id',
            'f.campus_id',
            'c.name as campus_name',
            'f.building_id',
            'f.building_id as campus_building_id',
            'b.code as building_code',
            'b.name as building_name',
            'f.code as floor_code',
            'f.name as floor_name',
            'f.floor_number',
            'f.status as status_code',
            'f.created_at',
            'f.updated_at',
        ])
        ->orderBy('b.name')
        ->orderBy('f.floor_number')
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
        ->leftJoin('campuses as c', 'c.id', '=', 'r.campus_id')
        ->leftJoin('buildings as b', 'b.id', '=', 'r.building_id')
        ->leftJoin('floors as f', 'f.id', '=', 'r.floor_id')
        ->where('r.tenant_id', $tenantId)
        ->whereNull('r.deleted_at');

    if (!empty($filters['campus_building_id'])) {
        $query->where('r.building_id', $filters['campus_building_id']);
    }

    if (!empty($filters['campus_floor_id'])) {
        $query->where('r.floor_id', $filters['campus_floor_id']);
    }

    if (!empty($filters['room_type_code'])) {
        $type = $this->mapUiRoomTypeToExistingRoomType($filters['room_type_code']);
        $query->where('r.room_type', $type);
    }

    if (($filters['is_lab'] ?? null) !== null) {
        if ((bool) $filters['is_lab']) {
            $query->where('r.room_type', 'lab');
        } else {
            $query->where('r.room_type', '!=', 'lab');
        }
    }

    if (($filters['is_active_for_timetable'] ?? null) !== null) {
        $query->where('r.is_available_for_timetable', (bool) $filters['is_active_for_timetable']);
    }

    if (!empty($filters['status_code'])) {
        $query->where('r.status', $filters['status_code']);
    }

    if (!empty($filters['min_capacity'])) {
        $query->where('r.capacity', '>=', (int) $filters['min_capacity']);
    }

    return $query
        ->select([
            'r.id',
            'r.tenant_id',
            'r.campus_id',
            'c.name as campus_name',
            'r.building_id',
            'r.building_id as campus_building_id',
            'b.code as building_code',
            'b.name as building_name',
            'r.floor_id',
            'r.floor_id as campus_floor_id',
            'f.code as floor_code',
            'f.name as floor_name',
            'r.code as room_code',
            'r.name as room_name',
            'r.room_type',
            'r.room_type as room_type_code',
            'r.room_type as room_type_name',
            'r.capacity',
            DB::raw('NULL as exam_capacity'),
            DB::raw('0 as has_multimedia'),
            DB::raw('0 as has_projector'),
            DB::raw('0 as has_smart_board'),
            DB::raw("CASE WHEN r.room_type = 'lab' THEN 1 ELSE 0 END as has_computers"),
            DB::raw("CASE WHEN r.room_type = 'lab' THEN r.capacity ELSE NULL END as computer_count"),
            DB::raw("CASE WHEN r.room_type = 'lab' THEN 1 ELSE 0 END as is_lab"),
            DB::raw('1 as is_shared'),
            'r.is_available_for_timetable as is_active_for_timetable',
            'r.status as status_code',
            'r.description as remarks',
            'r.created_at',
            'r.updated_at',
        ])
        ->orderBy('b.name')
        ->orderBy('r.code')
        ->paginate((int) ($filters['per_page'] ?? 15))
        ->toArray();
}


    public function createRoom(array $data): array
{
    $tenantId = $this->tenantId();

    $buildingId = $data['campus_building_id'] ?? $data['building_id'] ?? null;
    $floorId = $data['campus_floor_id'] ?? $data['floor_id'] ?? null;

    $campusId = $data['campus_id'] ?? null;

    if (!$campusId && $buildingId) {
        $campusId = DB::table('buildings')
            ->where('tenant_id', $tenantId)
            ->where('id', $buildingId)
            ->value('campus_id');
    }

    abort_if(!$campusId, 422, 'Campus could not be resolved from selected building.');

    $payload = [
        'tenant_id' => $tenantId,
        'campus_id' => $campusId,
        'building_id' => $buildingId,
        'floor_id' => $floorId,
        'code' => $data['room_code'] ?? $data['code'] ?? null,
        'name' => $data['room_name'] ?? $data['name'] ?? null,
        'room_type' => $this->mapUiRoomTypeToExistingRoomType($data['room_type_code'] ?? null),
        'capacity' => $data['capacity'] ?? 0,
        'is_available_for_timetable' => $data['is_active_for_timetable'] ?? true,
        'description' => $data['remarks'] ?? null,
        'status' => $data['status_code'] ?? 'active',
        'created_by' => auth()->id(),
        'updated_by' => auth()->id(),
        'created_at' => now(),
        'updated_at' => now(),
    ];

    $id = DB::table('rooms')->insertGetId($payload);

    return $this->room($id);
}

private function room(int $id): array
{
    $row = DB::table('rooms')->where('id', $id)->first();

    return [
        'id' => $row->id,
        'tenant_id' => $row->tenant_id,
        'campus_id' => $row->campus_id,
        'building_id' => $row->building_id,
        'campus_building_id' => $row->building_id,
        'floor_id' => $row->floor_id,
        'campus_floor_id' => $row->floor_id,
        'room_code' => $row->code,
        'room_name' => $row->name,
        'room_type_code' => $row->room_type,
        'capacity' => $row->capacity,
        'is_active_for_timetable' => $row->is_available_for_timetable,
        'status_code' => $row->status,
    ];
}

    public function availableRooms(array $filters = []): array
{
    $tenantId = $this->tenantId();

    $query = DB::table('rooms as r')
        ->leftJoin('buildings as b', 'b.id', '=', 'r.building_id')
        ->leftJoin('floors as f', 'f.id', '=', 'r.floor_id')
        ->where('r.tenant_id', $tenantId)
        ->where('r.status', 'active')
        ->where('r.is_available_for_timetable', true)
        ->whereNull('r.deleted_at');

    if (!empty($filters['room_type_code'])) {
        $query->where('r.room_type', $this->mapUiRoomTypeToExistingRoomType($filters['room_type_code']));
    }

    if (!empty($filters['required_capacity'])) {
        $query->where('r.capacity', '>=', (int) $filters['required_capacity']);
    }

    if (!empty($filters['requires_lab'])) {
        $query->where('r.room_type', 'lab');
    }

    return $query
        ->select([
            'r.id',
            'r.code as room_code',
            'r.name as room_name',
            'r.room_type as room_type_code',
            'r.capacity',
            DB::raw('NULL as exam_capacity'),
            DB::raw('0 as has_multimedia'),
            DB::raw('0 as has_projector'),
            DB::raw('0 as has_smart_board'),
            DB::raw("CASE WHEN r.room_type = 'lab' THEN 1 ELSE 0 END as has_computers"),
            DB::raw("CASE WHEN r.room_type = 'lab' THEN r.capacity ELSE NULL END as computer_count"),
            DB::raw("CASE WHEN r.room_type = 'lab' THEN 1 ELSE 0 END as is_lab"),
            'b.name as building_name',
            'f.name as floor_name',
        ])
        ->orderBy('r.capacity')
        ->get()
        ->map(fn ($row) => (array) $row)
        ->toArray();
}

private function buildingOptions(int $tenantId): array
{
    return DB::table('buildings')
        ->where('tenant_id', $tenantId)
        ->where('status', 'active')
        ->whereNull('deleted_at')
        ->select('id as value', DB::raw("CONCAT(code, ' - ', name) as label"), 'campus_id')
        ->orderBy('name')
        ->get()
        ->map(fn ($row) => (array) $row)
        ->toArray();
}

private function floorOptions(int $tenantId, array $filters): array
{
    $query = DB::table('floors')
        ->where('tenant_id', $tenantId)
        ->where('status', 'active')
        ->whereNull('deleted_at');

    if (!empty($filters['campus_building_id'])) {
        $query->where('building_id', $filters['campus_building_id']);
    }

    return $query
        ->select(
            'id as value',
            DB::raw("CONCAT(code, ' - ', name) as label"),
            'building_id',
            'building_id as campus_building_id',
            'campus_id'
        )
        ->orderBy('floor_number')
        ->get()
        ->map(fn ($row) => (array) $row)
        ->toArray();
}

private function roomTypeOptions(int $tenantId): array
{
    return [
        ['value' => 'classroom', 'label' => 'Classroom', 'code' => 'classroom', 'is_lab_space' => false],
        ['value' => 'lab', 'label' => 'Lab', 'code' => 'lab', 'is_lab_space' => true],
        ['value' => 'faculty_room', 'label' => 'Faculty Room', 'code' => 'faculty_room', 'is_lab_space' => false],
        ['value' => 'office', 'label' => 'Office', 'code' => 'office', 'is_lab_space' => false],
        ['value' => 'meeting_room', 'label' => 'Meeting Room', 'code' => 'meeting_room', 'is_lab_space' => false],
        ['value' => 'seminar_hall', 'label' => 'Seminar Hall', 'code' => 'seminar_hall', 'is_lab_space' => false],
        ['value' => 'auditorium', 'label' => 'Auditorium', 'code' => 'auditorium', 'is_lab_space' => false],
        ['value' => 'library', 'label' => 'Library', 'code' => 'library', 'is_lab_space' => false],
        ['value' => 'other', 'label' => 'Other', 'code' => 'other', 'is_lab_space' => false],
    ];
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
private function mapUiRoomTypeToExistingRoomType(?string $type): string
{
    return match ($type) {
        'computer_lab', 'science_lab', 'lab' => 'lab',
        'lecture_theater', 'classroom' => 'classroom',
        'seminar_room' => 'seminar_hall',
        default => $type ?: 'classroom',
    };
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