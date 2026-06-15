<?php

namespace App\Modules\Student\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Student\Services\StudentAcademicService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentAcademicController extends Controller
{
    public function students(Request $request, StudentAcademicService $service): JsonResponse
    {
        return ApiResponse::success(
            $service->students($request->all()),
            'Students fetched successfully.'
        );
    }

    public function showStudent(int $studentId, StudentAcademicService $service): JsonResponse
    {
        return ApiResponse::success(
            $service->showStudent($studentId),
            'Student detail fetched successfully.'
        );
    }

    public function enrollments(Request $request, StudentAcademicService $service): JsonResponse
    {
        return ApiResponse::success(
            $service->enrollments($request->all()),
            'Student enrollments fetched successfully.'
        );
    }
    public function updateStudentStatus(
    Request $request,
    int $studentId,
    StudentAcademicService $service
): JsonResponse {
    $validated = $request->validate([
        'student_status' => ['required', 'string', 'max:50'],
        'reason' => ['nullable', 'string', 'max:1000'],
        'effective_date' => ['nullable', 'date'],
    ]);

    return ApiResponse::success(
        $service->updateStudentStatus($studentId, $validated),
        'Student status updated successfully.'
    );
}

public function updateEnrollment(
    Request $request,
    int $enrollmentId,
    StudentAcademicService $service
): JsonResponse {
    $validated = $request->validate([
        'program_id' => ['nullable', 'integer'],
        'academic_session_id' => ['nullable', 'integer'],
        'term_id' => ['nullable', 'integer'],
        'section' => ['nullable', 'string', 'max:50'],
        'roll_no' => ['nullable', 'string', 'max:100'],
        'registration_no' => ['nullable', 'string', 'max:100'],
        'status' => ['nullable', 'string', 'max:50'],
        'remarks' => ['nullable', 'string', 'max:1000'],
    ]);

    return ApiResponse::success(
        $service->updateEnrollment($enrollmentId, $validated),
        'Student enrollment updated successfully.'
    );
}
public function courseRegistrationContext(
    int $studentId,
    StudentAcademicService $service
): JsonResponse {
    return ApiResponse::success(
        $service->courseRegistrationContext($studentId),
        'Student course registration context fetched successfully.'
    );
}

public function availableCourses(
    Request $request,
    int $studentId,
    StudentAcademicService $service
): JsonResponse {
    return ApiResponse::success(
        $service->availableCourses($studentId, $request->all()),
        'Available courses fetched successfully.'
    );
}

public function registeredCourses(
    int $studentId,
    StudentAcademicService $service
): JsonResponse {
    return ApiResponse::success(
        $service->registeredCourses($studentId),
        'Registered courses fetched successfully.'
    );
}

public function registerCourses(
    Request $request,
    int $studentId,
    StudentAcademicService $service
): JsonResponse {
    $validated = $request->validate([
        'student_enrollment_id' => ['required', 'integer'],
        'curriculum_subject_ids' => ['required', 'array', 'min:1'],
        'curriculum_subject_ids.*' => ['integer'],
        'registration_type' => ['nullable', 'string', 'max:50'],
        'remarks' => ['nullable', 'string', 'max:1000'],
    ]);

    return ApiResponse::success(
        $service->registerCourses($studentId, $validated),
        'Courses registered successfully.'
    );
}

public function unregisterCourse(
    int $registrationId,
    StudentAcademicService $service
): JsonResponse {
    return ApiResponse::success(
        $service->unregisterCourse($registrationId),
        'Course registration removed successfully.'
    );
}
public function allocationContext(Request $request, StudentAcademicService $service): JsonResponse
{
    return ApiResponse::success(
        $service->allocationContext($request->all()),
        'Student allocation context fetched successfully.'
    );
}

public function bulkAllocate(Request $request, StudentAcademicService $service): JsonResponse
{
    $validated = $request->validate([
        'student_enrollment_ids' => ['required', 'array', 'min:1'],
        'student_enrollment_ids.*' => ['integer'],

        'student_batch_id' => ['nullable', 'integer'],
        'section' => ['required', 'string', 'max:50'],

        'roll_prefix' => ['nullable', 'string', 'max:50'],
        'registration_prefix' => ['nullable', 'string', 'max:50'],
        'start_roll_no' => ['nullable', 'integer', 'min:1'],
        'padding' => ['nullable', 'integer', 'min:1', 'max:10'],

        'allocation_status' => ['nullable', 'string', 'max:50'],
        'overwrite_existing' => ['nullable', 'boolean'],
        'remarks' => ['nullable', 'string', 'max:1000'],
    ]);

    return ApiResponse::success(
        $service->bulkAllocate($validated),
        'Students allocated successfully.'
    );
}
public function academicPlacementOptions(
    Request $request,
    StudentAcademicService $service
): JsonResponse {
    return ApiResponse::success(
        $service->academicPlacementOptions($request->all()),
        'Academic placement options fetched successfully.'
    );
}
public function verifyStudentDocument(
    Request $request,
    int $documentId,
    StudentAcademicService $service
): JsonResponse {
    $validated = $request->validate([
        'verification_status' => ['required', 'string', 'max:50'],
        'remarks' => ['nullable', 'string', 'max:1000'],
    ]);

    return ApiResponse::success(
        $service->verifyStudentDocument($documentId, $validated),
        'Student document verification updated successfully.'
    );
}
public function updateEnrollmentAllocation(
    Request $request,
    int $enrollmentId,
    StudentAcademicService $service
): JsonResponse {
    $validated = $request->validate([
        'student_batch_id' => ['nullable', 'integer'],
        'section' => ['nullable', 'string', 'max:50'],
        'roll_no' => ['nullable', 'string', 'max:100'],
        'registration_no' => ['nullable', 'string', 'max:100'],
        'roll_sequence_no' => ['nullable', 'integer', 'min:1'],
        'allocation_status' => ['nullable', 'string', 'max:50'],
        'remarks' => ['nullable', 'string', 'max:1000'],
    ]);

    return ApiResponse::success(
        $service->updateEnrollmentAllocation($enrollmentId, $validated),
        'Enrollment allocation updated successfully.'
    );
}
public function updateStudentProfile(
    Request $request,
    int $studentId,
    StudentAcademicService $service
): JsonResponse {
    $validated = $request->validate([
        'first_name' => ['nullable', 'string', 'max:150'],
        'last_name' => ['nullable', 'string', 'max:150'],
        'father_name' => ['nullable', 'string', 'max:150'],
        'mother_name' => ['nullable', 'string', 'max:150'],
        'cnic_bform' => ['nullable', 'string', 'max:50'],
        'passport_no' => ['nullable', 'string', 'max:50'],
        'date_of_birth' => ['nullable', 'date'],
        'gender' => ['nullable', 'string', 'max:50'],
        'blood_group_id' => ['nullable', 'integer'],
        'religion_id' => ['nullable', 'integer'],
        'nationality_id' => ['nullable', 'integer'],
        'phone' => ['nullable', 'string', 'max:50'],
        'alternate_phone' => ['nullable', 'string', 'max:50'],
        'email' => ['nullable', 'email', 'max:150'],
        'current_address' => ['nullable', 'string', 'max:1000'],
        'permanent_address' => ['nullable', 'string', 'max:1000'],
        'country_id' => ['nullable', 'integer'],
        'province_id' => ['nullable', 'integer'],
        'city_id' => ['nullable', 'integer'],
        'remarks' => ['nullable', 'string', 'max:1000'],
    ]);

    return ApiResponse::success(
        $service->updateStudentProfile($studentId, $validated),
        'Student profile updated successfully.'
    );
}

public function upsertGuardian(
    Request $request,
    int $studentId,
    StudentAcademicService $service
): JsonResponse {
    $validated = $request->validate([
        'student_guardian_id' => ['nullable', 'integer'],
        'guardian_id' => ['nullable', 'integer'],

        'name' => ['required', 'string', 'max:150'],
        'cnic' => ['nullable', 'string', 'max:50'],
        'phone' => ['nullable', 'string', 'max:50'],
        'alternate_phone' => ['nullable', 'string', 'max:50'],
        'email' => ['nullable', 'email', 'max:150'],
        'occupation' => ['nullable', 'string', 'max:150'],
        'monthly_income' => ['nullable', 'numeric'],
        'address' => ['nullable', 'string', 'max:1000'],
        'country_id' => ['nullable', 'integer'],
        'province_id' => ['nullable', 'integer'],
        'city_id' => ['nullable', 'integer'],

        'relationship_type_id' => ['nullable', 'integer'],
        'is_primary' => ['nullable', 'boolean'],
        'is_emergency_contact' => ['nullable', 'boolean'],
        'can_pick_student' => ['nullable', 'boolean'],
        'remarks' => ['nullable', 'string', 'max:1000'],
        'status' => ['nullable', 'string', 'max:50'],
    ]);

    return ApiResponse::success(
        $service->upsertGuardian($studentId, $validated),
        'Guardian saved successfully.'
    );
}

public function deleteGuardian(
    int $studentGuardianId,
    StudentAcademicService $service
): JsonResponse {
    return ApiResponse::success(
        $service->deleteGuardian($studentGuardianId),
        'Guardian link removed successfully.'
    );
}

public function upsertPreviousEducation(
    Request $request,
    int $studentId,
    StudentAcademicService $service
): JsonResponse {
    $validated = $request->validate([
        'id' => ['nullable', 'integer'],
        'qualification_level_id' => ['nullable', 'integer'],
        'education_board_id' => ['nullable', 'integer'],
        'external_institution_id' => ['nullable', 'integer'],
        'degree_class_name' => ['nullable', 'string', 'max:150'],
        'roll_no' => ['nullable', 'string', 'max:100'],
        'registration_no' => ['nullable', 'string', 'max:100'],
        'passing_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
        'total_marks' => ['nullable', 'integer', 'min:0'],
        'obtained_marks' => ['nullable', 'integer', 'min:0'],
        'percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        'grade' => ['nullable', 'string', 'max:50'],
        'cgpa' => ['nullable', 'numeric', 'min:0', 'max:10'],
        'document_path' => ['nullable', 'string', 'max:1000'],
        'remarks' => ['nullable', 'string', 'max:1000'],
        'status' => ['nullable', 'string', 'max:50'],
    ]);

    return ApiResponse::success(
        $service->upsertPreviousEducation($studentId, $validated),
        'Previous education saved successfully.'
    );
}

public function deletePreviousEducation(
    int $previousEducationId,
    StudentAcademicService $service
): JsonResponse {
    return ApiResponse::success(
        $service->deletePreviousEducation($previousEducationId),
        'Previous education removed successfully.'
    );
}

public function verifyDocument(
    Request $request,
    int $documentId,
    StudentAcademicService $service
): JsonResponse {
    $validated = $request->validate([
        'verification_status' => ['required', 'string', 'max:50'],
        'remarks' => ['nullable', 'string', 'max:1000'],
    ]);

    return ApiResponse::success(
        $service->verifyDocument($documentId, $validated),
        'Student document verification updated successfully.'
    );
}
public function lifecycleContext(
    Request $request,
    StudentAcademicService $service
): JsonResponse {
    return ApiResponse::success(
        $service->lifecycleContext($request->all()),
        'Student lifecycle context fetched successfully.'
    );
}

public function applyLifecycleAction(
    Request $request,
    int $studentId,
    StudentAcademicService $service
): JsonResponse {
    $validated = $request->validate([
        'action_code' => ['required', 'string', 'max:50'],
        'student_enrollment_id' => ['nullable', 'integer'],

        'reason' => ['required', 'string', 'max:1000'],
        'effective_date' => ['nullable', 'date'],
        'remarks' => ['nullable', 'string', 'max:1000'],

        'target_program_id' => ['nullable', 'integer'],
        'target_academic_session_id' => ['nullable', 'integer'],
        'target_term_id' => ['nullable', 'integer'],
        'target_section' => ['nullable', 'string', 'max:50'],
        'target_student_batch_id' => ['nullable', 'integer'],
    ]);

    return ApiResponse::success(
        $service->applyLifecycleAction($studentId, $validated),
        'Student lifecycle action applied successfully.'
    );
}
public function profileCompletionSummary(
    int $studentId,
    StudentAcademicService $service
): JsonResponse {
    return ApiResponse::success(
        $service->profileCompletionSummary($studentId),
        'Student profile completion summary fetched successfully.'
    );
}
}