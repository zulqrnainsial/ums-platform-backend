<?php

namespace App\Modules\FacultyAllocation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\FacultyAllocation\Services\FacultyAllocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FacultyAllocationController extends Controller
{
    public function context(Request $request, FacultyAllocationService $service): JsonResponse
    {
        return response()->json([
            'data' => $service->context($request->all()),
            'message' => 'Faculty allocation context fetched successfully.',
        ]);
    }

    public function facultyMembers(Request $request, FacultyAllocationService $service): JsonResponse
    {
        return response()->json([
            'data' => $service->facultyMembers($request->all()),
            'message' => 'Faculty members fetched successfully.',
        ]);
    }

    public function storeFacultyMember(Request $request, FacultyAllocationService $service): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'integer'],
            'create_login' => ['nullable', 'boolean'],
            'initial_password' => ['nullable', 'string', 'min:8', 'max:255'],
            'teacher_role_name' => ['nullable', 'string', 'max:100'],
            'sync_user_account' => ['nullable', 'boolean'],
            'department_id' => ['nullable', 'integer'],
            'faculty_id' => ['nullable', 'integer'],
            'employee_no' => ['nullable', 'string', 'max:100'],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'employment_type_code' => ['nullable', 'string', 'max:100'],
            'designation_code' => ['nullable', 'string', 'max:100'],
            'faculty_type_code' => ['nullable', 'string', 'max:100'],
            'status_code' => ['nullable', 'string', 'max:50'],
            'joining_date' => ['nullable', 'date'],
            'leaving_date' => ['nullable', 'date'],
        ]);

        return response()->json([
            'data' => $service->createFacultyMember($validated),
            'message' => 'Faculty member created successfully.',
        ]);
    }

    public function updateFacultyMember(int $facultyMember, Request $request, FacultyAllocationService $service): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'integer'],
            'create_login' => ['nullable', 'boolean'],
            'initial_password' => ['nullable', 'string', 'min:8', 'max:255'],
            'teacher_role_name' => ['nullable', 'string', 'max:100'],
            'sync_user_account' => ['nullable', 'boolean'],
            'department_id' => ['nullable', 'integer'],
            'faculty_id' => ['nullable', 'integer'],
            'employee_no' => ['nullable', 'string', 'max:100'],
            'full_name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'employment_type_code' => ['nullable', 'string', 'max:100'],
            'designation_code' => ['nullable', 'string', 'max:100'],
            'faculty_type_code' => ['nullable', 'string', 'max:100'],
            'status_code' => ['nullable', 'string', 'max:50'],
            'joining_date' => ['nullable', 'date'],
            'leaving_date' => ['nullable', 'date'],
        ]);

        return response()->json([
            'data' => $service->updateFacultyMember($facultyMember, $validated),
            'message' => 'Faculty member updated successfully.',
        ]);
    }

    public function loadPolicies(Request $request, FacultyAllocationService $service): JsonResponse
    {
        return response()->json([
            'data' => $service->loadPolicies($request->all()),
            'message' => 'Faculty load policies fetched successfully.',
        ]);
    }

    public function storeLoadPolicy(Request $request, FacultyAllocationService $service): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['nullable', 'integer'],
            'employment_type_code' => ['nullable', 'string', 'max:100'],
            'designation_code' => ['nullable', 'string', 'max:100'],
            'faculty_type_code' => ['nullable', 'string', 'max:100'],
            'max_weekly_credit_hours' => ['nullable', 'numeric', 'min:0'],
            'max_weekly_contact_hours' => ['nullable', 'numeric', 'min:0'],
            'max_daily_contact_hours' => ['nullable', 'numeric', 'min:0'],
            'max_consecutive_slots' => ['nullable', 'integer', 'min:0'],
            'allow_theory' => ['nullable', 'boolean'],
            'allow_practical' => ['nullable', 'boolean'],
            'allow_lab' => ['nullable', 'boolean'],
            'allow_tutorial' => ['nullable', 'boolean'],
            'status_code' => ['nullable', 'string', 'max:50'],
        ]);

        return response()->json([
            'data' => $service->saveLoadPolicy($validated),
            'message' => 'Faculty load policy saved successfully.',
        ]);
    }

    public function availability(int $facultyMember, Request $request, FacultyAllocationService $service): JsonResponse
    {
        return response()->json([
            'data' => $service->availability($facultyMember, $request->all()),
            'message' => 'Faculty availability fetched successfully.',
        ]);
    }

    public function storeAvailability(int $facultyMember, Request $request, FacultyAllocationService $service): JsonResponse
    {
        $validated = $request->validate([
            'records' => ['required', 'array', 'min:1'],
            'records.*.academic_session_id' => ['nullable', 'integer'],
            'records.*.academic_term_id' => ['nullable', 'integer'],
            'records.*.day_of_week' => ['required', 'integer', 'min:1', 'max:7'],
            'records.*.start_time' => ['required', 'date_format:H:i'],
            'records.*.end_time' => ['required', 'date_format:H:i'],
            'records.*.availability_type' => ['required', 'string', 'max:50'],
            'records.*.reason' => ['nullable', 'string', 'max:255'],
            'records.*.status_code' => ['nullable', 'string', 'max:50'],
        ]);

        return response()->json([
            'data' => $service->saveAvailability($facultyMember, $validated['records']),
            'message' => 'Faculty availability saved successfully.',
        ]);
    }

    public function subjectExpertise(int $facultyMember, FacultyAllocationService $service): JsonResponse
    {
        return response()->json([
            'data' => $service->subjectExpertise($facultyMember),
            'message' => 'Faculty subject expertise fetched successfully.',
        ]);
    }

    public function storeSubjectExpertise(int $facultyMember, Request $request, FacultyAllocationService $service): JsonResponse
    {
        $validated = $request->validate([
            'records' => ['required', 'array', 'min:1'],
            'records.*.subject_id' => ['nullable', 'integer'],
            'records.*.curriculum_subject_id' => ['nullable', 'integer'],
            'records.*.subject_type_code' => ['nullable', 'string', 'max:100'],
            'records.*.expertise_level_code' => ['nullable', 'string', 'max:100'],
            'records.*.can_teach' => ['nullable', 'boolean'],
            'records.*.status_code' => ['nullable', 'string', 'max:50'],
        ]);

        return response()->json([
            'data' => $service->saveSubjectExpertise($facultyMember, $validated['records']),
            'message' => 'Faculty subject expertise saved successfully.',
        ]);
    }

    public function courseOfferings(Request $request, FacultyAllocationService $service): JsonResponse
    {
        return response()->json([
            'data' => $service->courseOfferings($request->all()),
            'message' => 'Course offerings fetched successfully.',
        ]);
    }

    public function storeCourseOffering(Request $request, FacultyAllocationService $service): JsonResponse
    {
        $validated = $request->validate([
            'academic_session_id' => ['nullable', 'integer'],
            'academic_term_id' => ['nullable', 'integer'],
            'program_id' => ['nullable', 'integer'],
            'student_batch_id' => ['nullable', 'integer'],
            'section_id' => ['nullable', 'integer'],
            'academic_teaching_group_id' => ['nullable', 'integer'],
            'curriculum_subject_id' => ['nullable', 'integer'],
            'subject_id' => ['nullable', 'integer'],
            'course_code' => ['nullable', 'string', 'max:100'],
            'course_title' => ['nullable', 'string', 'max:255'],
            'subject_type_code' => ['nullable', 'string', 'max:100'],
            'credit_hours' => ['nullable', 'numeric', 'min:0'],
            'contact_hours_per_week' => ['nullable', 'numeric', 'min:0'],
            'required_sessions_per_week' => ['nullable', 'integer', 'min:0'],
            'required_hours_per_session' => ['nullable', 'numeric', 'min:0'],
            'required_room_type_code' => ['nullable', 'string', 'max:100'],
            'required_capacity' => ['nullable', 'integer', 'min:0'],
            'requires_multimedia' => ['nullable', 'boolean'],
            'requires_lab' => ['nullable', 'boolean'],
            'status_code' => ['nullable', 'string', 'max:50'],
        ]);

        return response()->json([
            'data' => $service->createCourseOffering($validated),
            'message' => 'Course offering created successfully.',
        ]);
    }

    public function updateCourseOffering(int $courseOffering, Request $request, FacultyAllocationService $service): JsonResponse
    {
        $validated = $request->validate([
            'academic_session_id' => ['nullable', 'integer'],
            'academic_term_id' => ['nullable', 'integer'],
            'program_id' => ['nullable', 'integer'],
            'student_batch_id' => ['nullable', 'integer'],
            'section_id' => ['nullable', 'integer'],
            'academic_teaching_group_id' => ['nullable', 'integer'],
            'curriculum_subject_id' => ['nullable', 'integer'],
            'subject_id' => ['nullable', 'integer'],
            'course_code' => ['nullable', 'string', 'max:100'],
            'course_title' => ['nullable', 'string', 'max:255'],
            'subject_type_code' => ['nullable', 'string', 'max:100'],
            'credit_hours' => ['nullable', 'numeric', 'min:0'],
            'contact_hours_per_week' => ['nullable', 'numeric', 'min:0'],
            'required_sessions_per_week' => ['nullable', 'integer', 'min:0'],
            'required_hours_per_session' => ['nullable', 'numeric', 'min:0'],
            'required_room_type_code' => ['nullable', 'string', 'max:100'],
            'required_capacity' => ['nullable', 'integer', 'min:0'],
            'requires_multimedia' => ['nullable', 'boolean'],
            'requires_lab' => ['nullable', 'boolean'],
            'status_code' => ['nullable', 'string', 'max:50'],
        ]);

        return response()->json([
            'data' => $service->updateCourseOffering($courseOffering, $validated),
            'message' => 'Course offering updated successfully.',
        ]);
    }

    public function allocations(Request $request, FacultyAllocationService $service): JsonResponse
    {
        return response()->json([
            'data' => $service->allocations($request->all()),
            'message' => 'Teacher allocations fetched successfully.',
        ]);
    }

    public function validateAllocation(Request $request, FacultyAllocationService $service): JsonResponse
    {
        $validated = $request->validate([
            'course_offering_id' => ['required', 'integer'],
            'faculty_member_id' => ['required', 'integer'],
            'allocation_role_code' => ['nullable', 'string', 'max:100'],
            'allocated_credit_hours' => ['nullable', 'numeric', 'min:0'],
            'allocated_contact_hours' => ['nullable', 'numeric', 'min:0'],
        ]);

        return response()->json([
            'data' => $service->validateAllocation($validated),
            'message' => 'Teacher allocation validated successfully.',
        ]);
    }

    public function storeAllocation(Request $request, FacultyAllocationService $service): JsonResponse
    {
        $validated = $request->validate([
            'course_offering_id' => ['required', 'integer'],
            'faculty_member_id' => ['required', 'integer'],
            'allocation_role_code' => ['nullable', 'string', 'max:100'],
            'allocated_credit_hours' => ['nullable', 'numeric', 'min:0'],
            'allocated_contact_hours' => ['nullable', 'numeric', 'min:0'],
            'remarks' => ['nullable', 'string'],
        ]);

        return response()->json([
            'data' => $service->createAllocation($validated),
            'message' => 'Teacher allocation saved successfully.',
        ]);
    }
public function teachingGroups(Request $request, FacultyAllocationService $service): JsonResponse
{
    return response()->json([
        'data' => $service->teachingGroups($request->all()),
        'message' => 'Teaching groups fetched successfully.',
    ]);
}

public function storeTeachingGroup(Request $request, FacultyAllocationService $service): JsonResponse
{
    $validated = $request->validate([
        'academic_session_id' => ['nullable', 'integer'],
        'academic_term_id' => ['nullable', 'integer'],
        'program_id' => ['nullable', 'integer'],
        'student_batch_id' => ['nullable', 'integer'],
        'section_id' => ['nullable', 'integer'],
        'group_code' => ['required', 'string', 'max:100'],
        'group_name' => ['required', 'string', 'max:255'],
        'group_type_code' => ['required', 'string', 'max:100'],
        'capacity' => ['nullable', 'integer', 'min:0'],
        'actual_strength' => ['nullable', 'integer', 'min:0'],
        'status_code' => ['nullable', 'string', 'max:50'],
    ]);

    return response()->json([
        'data' => $service->createTeachingGroup($validated),
        'message' => 'Teaching group created successfully.',
    ]);
}

public function updateTeachingGroup(int $teachingGroup, Request $request, FacultyAllocationService $service): JsonResponse
{
    $validated = $request->validate([
        'academic_session_id' => ['nullable', 'integer'],
        'academic_term_id' => ['nullable', 'integer'],
        'program_id' => ['nullable', 'integer'],
        'student_batch_id' => ['nullable', 'integer'],
        'section_id' => ['nullable', 'integer'],
        'group_code' => ['nullable', 'string', 'max:100'],
        'group_name' => ['nullable', 'string', 'max:255'],
        'group_type_code' => ['nullable', 'string', 'max:100'],
        'capacity' => ['nullable', 'integer', 'min:0'],
        'actual_strength' => ['nullable', 'integer', 'min:0'],
        'status_code' => ['nullable', 'string', 'max:50'],
    ]);

    return response()->json([
        'data' => $service->updateTeachingGroup($teachingGroup, $validated),
        'message' => 'Teaching group updated successfully.',
    ]);
}

public function teachingGroupMembers(int $teachingGroup, Request $request, FacultyAllocationService $service): JsonResponse
{
    return response()->json([
        'data' => $service->teachingGroupMembers($teachingGroup, $request->all()),
        'message' => 'Teaching group members fetched successfully.',
    ]);
}

public function eligibleStudentsForTeachingGroup(Request $request, FacultyAllocationService $service): JsonResponse
{
    $validated = $request->validate([
        'academic_session_id' => ['required', 'integer'],
        'program_id' => ['required', 'integer'],
        'student_batch_id' => ['required', 'integer'],
        'section_id' => ['nullable', 'integer'],
        'search' => ['nullable', 'string', 'max:255'],
        'limit' => ['nullable', 'integer', 'min:1', 'max:1000'],
    ]);

    return response()->json([
        'data' => $service->eligibleStudentsForTeachingGroup($validated),
        'message' => 'Eligible students fetched successfully.',
    ]);
}

public function syncTeachingGroupMembers(int $teachingGroup, Request $request, FacultyAllocationService $service): JsonResponse
{
    $validated = $request->validate([
        'students' => ['required', 'array'],
        'students.*.student_id' => ['required', 'integer'],
        'students.*.student_enrollment_id' => ['required', 'integer'],
        'students.*.status_code' => ['nullable', 'string', 'max:50'],
    ]);

    return response()->json([
        'data' => $service->syncTeachingGroupMembers($teachingGroup, $validated['students']),
        'message' => 'Teaching group members synced successfully.',
    ]);
}

public function createPracticalGroupsFromSection(Request $request, FacultyAllocationService $service): JsonResponse
{
    $validated = $request->validate([
        'academic_session_id' => ['required', 'integer'],
        'academic_term_id' => ['required', 'integer'],
        'program_id' => ['required', 'integer'],
        'student_batch_id' => ['required', 'integer'],
        'section_id' => ['required', 'integer'],
        'group_count' => ['required', 'integer', 'min:1', 'max:20'],
        'group_prefix' => ['nullable', 'string', 'max:50'],
        'group_type_code' => ['nullable', 'string', 'max:100'],
        'capacity' => ['nullable', 'integer', 'min:0'],
    ]);

    return response()->json([
        'data' => $service->createPracticalGroupsFromSection($validated),
        'message' => 'Practical groups created successfully.',
    ]);
}
public function createSplitCourseOfferings(Request $request, FacultyAllocationService $service): JsonResponse
{
    $validated = $request->validate([
        'academic_session_id' => ['required', 'integer'],
        'academic_term_id' => ['required', 'integer'],
        'program_id' => ['required', 'integer'],
        'student_batch_id' => ['required', 'integer'],
        'curriculum_subject_id' => ['required', 'integer'],

        'offering_types' => ['required', 'array', 'min:1'],

        'offering_types.*.subject_type_code' => ['required', 'string', 'max:100'],

        'offering_types.*.section_ids' => ['nullable', 'array'],
        'offering_types.*.section_ids.*' => ['integer'],

        'offering_types.*.teaching_group_ids' => ['nullable', 'array'],
        'offering_types.*.teaching_group_ids.*' => ['integer'],

        'offering_types.*.credit_hours' => ['nullable', 'numeric', 'min:0'],
        'offering_types.*.contact_hours_per_week' => ['nullable', 'numeric', 'min:0'],

        'offering_types.*.required_sessions_per_week' => ['nullable', 'integer', 'min:0'],
        'offering_types.*.required_hours_per_session' => ['nullable', 'numeric', 'min:0'],

        'offering_types.*.required_room_type_code' => ['nullable', 'string', 'max:100'],
        'offering_types.*.required_capacity' => ['nullable', 'integer', 'min:0'],

        'offering_types.*.requires_multimedia' => ['nullable', 'boolean'],
        'offering_types.*.requires_lab' => ['nullable', 'boolean'],

        'offering_types.*.status_code' => ['nullable', 'string', 'max:50'],
    ]);

    return response()->json([
        'data' => $service->createSplitCourseOfferings($validated),
        'message' => 'Split course offerings created successfully.',
    ]);
}
    public function retireCourseOffering(int $courseOffering, FacultyAllocationService $service): JsonResponse
    {
        return response()->json([
            'data' => $service->retireCourseOffering($courseOffering),
            'message' => 'Course offering retired and active allocations cancelled.',
        ]);
    }

    public function restoreCourseOffering(int $courseOffering, FacultyAllocationService $service): JsonResponse
    {
        return response()->json([
            'data' => $service->restoreCourseOffering($courseOffering),
            'message' => 'Course offering restored successfully.',
        ]);
    }

    public function allocationConflicts(int $allocation, FacultyAllocationService $service): JsonResponse
    {
        return response()->json([
            'data' => $service->allocationConflicts($allocation),
            'message' => 'Allocation conflicts fetched successfully.',
        ]);
    }

    public function cancelAllocation(int $allocation, FacultyAllocationService $service): JsonResponse
    {
        return response()->json([
            'data' => $service->cancelAllocation($allocation),
            'message' => 'Teacher allocation cancelled successfully.',
        ]);
    }

    public function revalidateExistingAllocation(int $allocation, FacultyAllocationService $service): JsonResponse
    {
        return response()->json([
            'data' => $service->revalidateAllocation($allocation),
            'message' => 'Teacher allocation revalidated successfully.',
        ]);
    }

    public function conflicts(Request $request, FacultyAllocationService $service): JsonResponse
    {
        return response()->json([
            'data' => $service->conflicts($request->all()),
            'message' => 'Faculty allocation conflicts fetched successfully.',
        ]);
    }
}