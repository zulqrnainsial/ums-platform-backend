<?php

namespace App\Modules\Timetable\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Timetable\Services\TimetableValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Modules\Timetable\Services\TimetableEntryService;
use App\Modules\Timetable\Services\TimetableAutoGenerationService;

class TimetableController extends Controller
{
    public function validateEntry(
        Request $request,
        TimetableValidationService $service
    ): JsonResponse {
        $validated = $request->validate([
            'course_offering_id' => ['required', 'integer'],
            'course_teacher_allocation_id' => ['nullable', 'integer'],
            'faculty_member_id' => ['nullable', 'integer'],
            'room_id' => ['required', 'integer'],
            'timetable_calendar_period_id' => ['required', 'integer'],
            'timetable_slot_ids' => ['required', 'array', 'min:1'],
            'timetable_slot_ids.*' => ['required', 'integer'],
            'ignore_timetable_entry_id' => ['nullable', 'integer'],
        ]);

        return response()->json([
            'data' => $service->validate($validated),
            'message' => 'Timetable entry validated successfully.',
        ]);
    }
    public function storeEntry(
    Request $request,
    TimetableEntryService $service
): JsonResponse {
    $validated = $request->validate([
        'course_offering_id' => ['required', 'integer'],
        'course_teacher_allocation_id' => ['nullable', 'integer'],
        'faculty_member_id' => ['nullable', 'integer'],
        'room_id' => ['required', 'integer'],

        'timetable_calendar_period_id' => ['required', 'integer'],

        'timetable_slot_ids' => ['required', 'array', 'min:1'],
        'timetable_slot_ids.*' => ['required', 'integer'],

        'entry_source_code' => ['nullable', 'string', 'max:50'],
        'remarks' => ['nullable', 'string'],
    ]);

    $result = $service->save($validated);

    return response()->json([
        'data' => $result,
        'message' => $result['validation']['valid']
            ? 'Timetable entry saved successfully.'
            : 'Timetable entry saved as conflicted draft.',
    ], $result['validation']['valid'] ? 201 : 422);
}

public function conflicts(
    Request $request,
    TimetableEntryService $service
): JsonResponse {
    return response()->json([
        'data' => $service->conflicts($request->all()),
        'message' => 'Timetable conflicts fetched successfully.',
    ]);
}
public function context(
    Request $request,
    TimetableEntryService $service
): JsonResponse {
    return response()->json([
        'data' => $service->context($request->all()),
        'message' => 'Timetable context fetched successfully.',
    ]);
}

public function slots(
    int $calendarPeriod,
    TimetableEntryService $service
): JsonResponse {
    return response()->json([
        'data' => $service->slots($calendarPeriod),
        'message' => 'Timetable slots fetched successfully.',
    ]);
}

public function entries(
    Request $request,
    TimetableEntryService $service
): JsonResponse {
    return response()->json([
        'data' => $service->entries($request->all()),
        'message' => 'Timetable entries fetched successfully.',
    ]);
}
public function weeklyGrid(
    Request $request,
    TimetableEntryService $service
): JsonResponse {
    return response()->json([
        'data' => $service->weeklyGrid($request->all()),
        'message' => 'Weekly timetable grid fetched successfully.',
    ]);
}
public function approveEntry(
    int $entry,
    Request $request,
    TimetableEntryService $service
): JsonResponse {
    $validated = $request->validate([
        'remarks' => ['nullable', 'string'],
    ]);

    return response()->json([
        'data' => $service->approveEntry($entry, $validated['remarks'] ?? null),
        'message' => 'Timetable entry approved successfully.',
    ]);
}

public function publishEntry(
    int $entry,
    Request $request,
    TimetableEntryService $service
): JsonResponse {
    $validated = $request->validate([
        'remarks' => ['nullable', 'string'],
    ]);

    return response()->json([
        'data' => $service->publishEntry($entry, $validated['remarks'] ?? null),
        'message' => 'Timetable entry published successfully.',
    ]);
}

public function cancelEntry(
    int $entry,
    Request $request,
    TimetableEntryService $service
): JsonResponse {
    $validated = $request->validate([
        'remarks' => ['nullable', 'string'],
    ]);

    return response()->json([
        'data' => $service->cancelEntry($entry, $validated['remarks'] ?? null),
        'message' => 'Timetable entry cancelled successfully.',
    ]);
}

public function approveBatch(
    Request $request,
    TimetableEntryService $service
): JsonResponse {
    $validated = $request->validate([
        'entry_ids' => ['required', 'array', 'min:1'],
        'entry_ids.*' => ['required', 'integer'],
        'remarks' => ['nullable', 'string'],
    ]);

    return response()->json([
        'data' => $service->approveBatch(
            $validated['entry_ids'],
            $validated['remarks'] ?? null
        ),
        'message' => 'Timetable entries approved successfully.',
    ]);
}

public function publishBatch(
    Request $request,
    TimetableEntryService $service
): JsonResponse {
    $validated = $request->validate([
        'entry_ids' => ['required', 'array', 'min:1'],
        'entry_ids.*' => ['required', 'integer'],
        'remarks' => ['nullable', 'string'],
    ]);

    return response()->json([
        'data' => $service->publishBatch(
            $validated['entry_ids'],
            $validated['remarks'] ?? null
        ),
        'message' => 'Timetable entries published successfully.',
    ]);
}


    public function generateCentralTimetable(
        Request $request,
        TimetableAutoGenerationService $service
    ): JsonResponse {
        $validated = $request->validate([
            'timetable_calendar_period_id' => ['required', 'integer'],
            'academic_session_id' => ['required', 'integer'],
            'academic_term_id' => ['required', 'integer'],
            'campus_id' => ['nullable', 'integer'],
            'faculty_id' => ['nullable', 'integer'],
            'department_id' => ['nullable', 'integer'],
            'program_level_id' => ['nullable', 'integer'],
            'program_id' => ['nullable', 'integer'],
            'student_batch_id' => ['nullable', 'integer'],
            'section_id' => ['nullable', 'integer'],
            'course_offering_ids' => ['nullable', 'array'],
            'course_offering_ids.*' => ['integer'],
        ]);

        return response()->json([
            'data' => $service->generate($validated),
            'message' => 'Central timetable generation completed.',
        ], 201);
    }

    public function generationRuns(
        Request $request,
        TimetableAutoGenerationService $service
    ): JsonResponse {
        return response()->json([
            'data' => $service->runs($request->all()),
            'message' => 'Timetable generation runs fetched successfully.',
        ]);
    }

    public function generationRun(
        int $run,
        TimetableAutoGenerationService $service
    ): JsonResponse {
        return response()->json([
            'data' => $service->runDetails($run),
            'message' => 'Timetable generation run fetched successfully.',
        ]);
    }

    public function replacePublishedTeacher(
        int $entry,
        Request $request,
        TimetableEntryService $service
    ): JsonResponse {
        $validated = $request->validate([
            'course_teacher_allocation_id' => ['required', 'integer'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        return response()->json([
            'data' => $service->replacePublishedTeacher(
                $entry,
                (int) $validated['course_teacher_allocation_id'],
                $validated['remarks'] ?? null,
            ),
            'message' => 'Published timetable teacher replaced successfully.',
        ]);
    }

}