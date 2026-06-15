<?php

namespace App\Modules\Student\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Student\Services\StudentPortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class StudentPortalController extends Controller
{
    public function dashboard(StudentPortalService $service): JsonResponse
    {
        return ApiResponse::success(
            $service->dashboard(),
            'Student portal dashboard fetched successfully.'
        );
    }

    public function profile(StudentPortalService $service): JsonResponse
    {
        return ApiResponse::success(
            $service->profile(),
            'Student profile fetched successfully.'
        );
    }

    public function enrollment(StudentPortalService $service): JsonResponse
    {
        return ApiResponse::success(
            $service->enrollment(),
            'Student enrollment fetched successfully.'
        );
    }

    public function courses(StudentPortalService $service): JsonResponse
    {
        return ApiResponse::success(
            $service->courses(),
            'Student courses fetched successfully.'
        );
    }

    public function documents(StudentPortalService $service): JsonResponse
    {
        return ApiResponse::success(
            $service->documents(),
            'Student documents fetched successfully.'
        );
    }
    public function requests(StudentPortalService $service): JsonResponse
{
    return ApiResponse::success(
        $service->requests(),
        'Student requests fetched successfully.'
    );
}
public function availableCourses(StudentPortalService $service): JsonResponse
{
    return ApiResponse::success(
        $service->availableCourses(),
        'Available courses fetched successfully.'
    );
}
public function submitProfileCorrectionRequest(
    Request $request,
    StudentPortalService $service
): JsonResponse {
    $validated = $request->validate([
        'requested_changes' => ['required', 'array'],
        'reason' => ['required', 'string', 'max:1000'],
    ]);

    return ApiResponse::success(
        $service->submitProfileCorrectionRequest($validated),
        'Profile correction request submitted successfully.'
    );
}

public function submitDocumentResubmissionRequest(
    Request $request,
    StudentPortalService $service
): JsonResponse {
    $validated = $request->validate([
        'student_document_id' => ['required', 'integer'],
        'reason' => ['required', 'string', 'max:1000'],
        'new_file_path' => ['nullable', 'string', 'max:1000'],
        'new_file_name' => ['nullable', 'string', 'max:255'],
    ]);

    return ApiResponse::success(
        $service->submitDocumentResubmissionRequest($validated),
        'Document resubmission request submitted successfully.'
    );
}
public function uploadProfilePicture(
    Request $request,
    StudentPortalService $service
): JsonResponse {
    $validated = $request->validate([
        'photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
    ]);

    return ApiResponse::success(
        $service->uploadProfilePicture($request->file('photo')),
        'Profile picture uploaded successfully.'
    );
}

public function feeStatus(StudentPortalService $service): JsonResponse
{
    return ApiResponse::success(
        $service->feeStatus(),
        'Student fee status fetched successfully.'
    );
}

public function uploadDocument(
    Request $request,
    StudentPortalService $service
): JsonResponse {
    $validated = $request->validate([
        'document_title' => ['required', 'string', 'max:255'],
        'document_type' => ['required', 'string', 'max:100'],
        'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:5120'],
        'remarks' => ['nullable', 'string', 'max:1000'],
    ]);

    return ApiResponse::success(
        $service->uploadDocument($validated, $request->file('file')),
        'Document uploaded successfully.'
    );
}

public function researchPublications(StudentPortalService $service): JsonResponse
{
    return ApiResponse::success(
        $service->researchPublications(),
        'Research/publications fetched successfully.'
    );
}

public function storeResearchPublication(
    Request $request,
    StudentPortalService $service
): JsonResponse {
    $validated = $request->validate([
        'type' => ['nullable', 'string', 'max:50'],
        'title' => ['required', 'string', 'max:500'],
        'journal_or_conference' => ['nullable', 'string', 'max:500'],
        'publisher' => ['nullable', 'string', 'max:255'],
        'doi' => ['nullable', 'string', 'max:255'],
        'url' => ['nullable', 'string', 'max:1000'],
        'publication_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
        'abstract' => ['nullable', 'string', 'max:5000'],
        'remarks' => ['nullable', 'string', 'max:1000'],
        'attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:5120'],
    ]);

    return ApiResponse::success(
        $service->storeResearchPublication($validated, $request->file('attachment')),
        'Research/publication submitted successfully.'
    );
}

public function deleteResearchPublication(
    int $publicationId,
    StudentPortalService $service
): JsonResponse {
    return ApiResponse::success(
        $service->deleteResearchPublication($publicationId),
        'Research/publication removed successfully.'
    );
}
public function submitCourseAddDropRequest(
    Request $request,
    StudentPortalService $service
): JsonResponse {
    $validated = $request->validate([
        'action_type' => ['required', 'string', 'max:50'],
        'student_enrollment_id' => ['nullable', 'integer'],
        'curriculum_subject_id' => ['nullable', 'integer'],
        'course_registration_id' => ['nullable', 'integer'],
        'reason' => ['required', 'string', 'max:1000'],
    ]);

    return ApiResponse::success(
        $service->submitCourseAddDropRequest($validated),
        'Course add/drop request submitted successfully.'
    );
}
}