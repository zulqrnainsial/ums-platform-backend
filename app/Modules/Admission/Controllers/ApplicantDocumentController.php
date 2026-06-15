<?php

namespace App\Modules\Admission\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Admission\Models\ApplicantDocument;
use App\Modules\Admission\Services\ApplicantDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Modules\Admission\Services\ApplicantEditLockService;
class ApplicantDocumentController extends Controller
{
    public function __construct(
        private readonly ApplicantDocumentService $service,
        private readonly ApplicantEditLockService $editLockService
    ) {
    }

    public function upload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['nullable', 'integer', 'exists:applicant_documents,id'],

            'applicant_id' => ['required', 'integer', 'exists:applicants,id'],
            'applicant_program_application_id' => [
                'nullable',
                'integer',
                'exists:applicant_program_applications,id',
            ],

            'document_type_id' => ['nullable', 'integer', 'exists:lookup_values,id'],
            'document_title' => ['required', 'string', 'max:255'],

            'related_table' => ['nullable', 'string', 'max:100'],
            'related_id' => ['nullable', 'integer'],

            'remarks' => ['nullable', 'string'],

            'file' => [
                'required',
                'file',
                'max:5120',
                'mimes:pdf,jpg,jpeg,png,webp,doc,docx',
            ],
        ]);
$this->editLockService->assertCanEdit(
    applicantId: (int) $validated['applicant_id'],
    tenantId: $request->user()?->tenant_id,
    area: 'documents'
);
        $document = $this->service->upload($validated, $request->file('file'));

        return ApiResponse::success(
            $this->formatDocument($document),
            'Applicant document uploaded successfully.'
        );
    }

    public function show(int $id): JsonResponse
    {
        $tenantId = auth()->user()?->tenant_id;

        $document = ApplicantDocument::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->with(['documentType', 'applicant'])
            ->firstOrFail();

        return ApiResponse::success(
            $this->formatDocument($document),
            'Applicant document fetched successfully.'
        );
    }

    public function download(int $id): StreamedResponse
    {
        return $this->service->download($id);
    }

    public function preview(int $id): StreamedResponse
    {
        return $this->service->preview($id);
    }

    public function destroy(int $id): JsonResponse
    {
        $tenantId = auth()->user()?->tenant_id;

        $document = ApplicantDocument::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->firstOrFail();

        $this->editLockService->assertCanEdit(
            applicantId: (int) $document->applicant_id,
            tenantId: $tenantId,
            area: 'documents'
        );
        $this->service->delete($id);

        return ApiResponse::success(
            null,
            'Applicant document deleted successfully.'
        );
    }

    public function updateVerification(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'verification_status_code' => [
                'required',
                'string',
                Rule::in(['pending', 'submitted', 'verified', 'rejected', 'deficient']),
            ],
            'rejection_reason' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
        ]);

        $tenantId = auth()->user()?->tenant_id;

        $document = ApplicantDocument::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->firstOrFail();

        $document->update([
            'verification_status_code' => $validated['verification_status_code'],
            'verified_at' => $validated['verification_status_code'] === 'verified' ? now() : null,
            'verified_by' => $validated['verification_status_code'] === 'verified' ? auth()->id() : null,
            'rejection_reason' => $validated['rejection_reason'] ?? null,
            'remarks' => $validated['remarks'] ?? $document->remarks,
            'updated_by' => auth()->id(),
        ]);

        return ApiResponse::success(
            $this->formatDocument($document->fresh()),
            'Applicant document verification updated successfully.'
        );
    }

    private function formatDocument(ApplicantDocument $document): array
    {
        return [
            'id' => $document->id,
            'tenant_id' => $document->tenant_id,
            'applicant_id' => $document->applicant_id,
            'applicant_program_application_id' => $document->applicant_program_application_id,
            'document_type_id' => $document->document_type_id,
            'document_type_name' => $document->documentType?->name,
            'document_title' => $document->document_title,
            'related_table' => $document->related_table,
            'related_id' => $document->related_id,
            'original_file_name' => $document->original_file_name,
            'stored_file_name' => $document->stored_file_name,
            'mime_type' => $document->mime_type,
            'file_size' => $document->file_size,
            'verification_status_code' => $document->verification_status_code,
            'verified_at' => $document->verified_at,
            'verified_by' => $document->verified_by,
            'rejection_reason' => $document->rejection_reason,
            'remarks' => $document->remarks,
            'preview_url' => "/api/admission/applicant-documents/{$document->id}/preview",
            'download_url' => "/api/admission/applicant-documents/{$document->id}/download",
            'created_at' => $document->created_at,
            'updated_at' => $document->updated_at,
        ];
    }
}