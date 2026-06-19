<?php

namespace App\Modules\ResourceManagement\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ResourceManagement\Services\ResourceManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResourceManagementController extends Controller
{
    public function context(Request $request, ResourceManagementService $service): JsonResponse
    {
        return response()->json([
            'data' => $service->context($request->all()),
            'message' => 'Resource management context fetched successfully.',
        ]);
    }

    public function buildings(Request $request, ResourceManagementService $service): JsonResponse
    {
        return response()->json([
            'data' => $service->buildings($request->all()),
            'message' => 'Buildings fetched successfully.',
        ]);
    }

    public function storeBuilding(Request $request, ResourceManagementService $service): JsonResponse
    {
        $validated = $request->validate([
            'campus_id' => ['nullable', 'integer'],
            'faculty_id' => ['nullable', 'integer'],
            'department_id' => ['nullable', 'integer'],
            'program_id' => ['nullable', 'integer'],
            'building_code' => ['required', 'string', 'max:100'],
            'building_name' => ['required', 'string', 'max:255'],
            'building_type_code' => ['nullable', 'string', 'max:100'],
            'ownership_scope_code' => ['nullable', 'string', 'max:100'],
            'location_description' => ['nullable', 'string', 'max:500'],
            'status_code' => ['nullable', 'string', 'max:50'],
        ]);

        return response()->json([
            'data' => $service->createBuilding($validated),
            'message' => 'Building created successfully.',
        ]);
    }

    public function floors(Request $request, ResourceManagementService $service): JsonResponse
    {
        return response()->json([
            'data' => $service->floors($request->all()),
            'message' => 'Floors fetched successfully.',
        ]);
    }

    public function storeFloor(Request $request, ResourceManagementService $service): JsonResponse
    {
        $validated = $request->validate([
            'campus_building_id' => ['required', 'integer'],
            'floor_code' => ['required', 'string', 'max:100'],
            'floor_name' => ['required', 'string', 'max:255'],
            'floor_number' => ['nullable', 'integer'],
            'status_code' => ['nullable', 'string', 'max:50'],
        ]);

        return response()->json([
            'data' => $service->createFloor($validated),
            'message' => 'Floor created successfully.',
        ]);
    }

    public function rooms(Request $request, ResourceManagementService $service): JsonResponse
    {
        return response()->json([
            'data' => $service->rooms($request->all()),
            'message' => 'Rooms fetched successfully.',
        ]);
    }

    public function storeRoom(Request $request, ResourceManagementService $service): JsonResponse
    {
        $validated = $request->validate([
            'campus_building_id' => ['nullable', 'integer'],
            'campus_floor_id' => ['nullable', 'integer'],
            'faculty_id' => ['nullable', 'integer'],
            'department_id' => ['nullable', 'integer'],
            'program_id' => ['nullable', 'integer'],
            'room_type_id' => ['nullable', 'integer'],
            'room_type_code' => ['nullable', 'string', 'max:100'],
            'room_code' => ['required', 'string', 'max:100'],
            'room_name' => ['required', 'string', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:0'],
            'exam_capacity' => ['nullable', 'integer', 'min:0'],
            'has_multimedia' => ['nullable', 'boolean'],
            'has_projector' => ['nullable', 'boolean'],
            'has_smart_board' => ['nullable', 'boolean'],
            'has_computers' => ['nullable', 'boolean'],
            'computer_count' => ['nullable', 'integer', 'min:0'],
            'is_shared' => ['nullable', 'boolean'],
            'is_lab' => ['nullable', 'boolean'],
            'is_active_for_timetable' => ['nullable', 'boolean'],
            'status_code' => ['nullable', 'string', 'max:50'],
            'remarks' => ['nullable', 'string'],
        ]);

        return response()->json([
            'data' => $service->createRoom($validated),
            'message' => 'Room created successfully.',
        ]);
    }

    public function availableRooms(Request $request, ResourceManagementService $service): JsonResponse
    {
        return response()->json([
            'data' => $service->availableRooms($request->all()),
            'message' => 'Available rooms fetched successfully.',
        ]);
    }
}