<?php

namespace App\Modules\Attendance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Attendance\Services\AttendanceMarkingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceMarkingController extends Controller
{
    public function context(Request $request, AttendanceMarkingService $service): JsonResponse
    {
        return response()->json([
            'data' => $service->context($request->all()),
            'message' => 'Attendance context fetched successfully.',
        ]);
    }

    public function students(Request $request, AttendanceMarkingService $service): JsonResponse
    {
        $validated = $request->validate([
            'academic_session_id' => ['required', 'integer'],
            'program_id' => ['required', 'integer'],
            'academic_term_id' => ['nullable', 'integer'],
            'student_batch_id' => ['required', 'integer'],
            'section_id' => ['required', 'integer'],
            'curriculum_subject_id' => ['required', 'integer'],
            'timetable_entry_id' => ['nullable', 'integer'],
        ]);

        return response()->json([
            'data' => $service->students($validated),
            'message' => 'Attendance students fetched successfully.',
        ]);
    }

    public function save(Request $request, AttendanceMarkingService $service): JsonResponse
    {
        $validated = $request->validate([
            'academic_session_id' => ['required', 'integer'],
            'program_id' => ['required', 'integer'],
            'academic_term_id' => ['nullable', 'integer'],
            'student_batch_id' => ['required', 'integer'],
            'section_id' => ['required', 'integer'],
            'curriculum_subject_id' => ['required', 'integer'],
            'timetable_entry_id' => ['nullable', 'integer'],

            'attendance_date' => ['required', 'date'],
            'session_type' => ['nullable', 'string', 'max:50'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'topic' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string', 'max:1000'],

            'records' => ['required', 'array', 'min:1'],
            'records.*.student_id' => ['required', 'integer'],
            'records.*.student_enrollment_id' => ['required', 'integer'],
            'records.*.student_course_registration_id' => ['required', 'integer'],
            'records.*.status_code' => ['required', 'string', 'max:50'],
            'records.*.remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        return response()->json([
            'data' => $service->save($validated),
            'message' => 'Attendance saved successfully.',
        ]);
    }

    public function lock(int $session, AttendanceMarkingService $service): JsonResponse
    {
        return response()->json([
            'data' => $service->lock($session),
            'message' => 'Attendance session locked successfully.',
        ]);
    }
}