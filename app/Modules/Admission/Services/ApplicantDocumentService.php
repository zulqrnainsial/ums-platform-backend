<?php

namespace App\Modules\Admission\Services;

use App\Modules\Admission\Models\Applicant;
use App\Modules\Admission\Models\ApplicantDocument;
use App\Modules\Admission\Models\ApplicantProgramApplication;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicantDocumentService
{
    private string $disk = 'local';

    public function upload(array $data, UploadedFile $file): ApplicantDocument
    {
        return DB::transaction(function () use ($data, $file) {
            $tenantId = auth()->user()?->tenant_id;

            if (!$tenantId) {
                abort(403, 'Tenant context is required.');
            }

            $applicant = Applicant::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $data['applicant_id'])
                ->firstOrFail();

            $applicationId = $data['applicant_program_application_id'] ?? null;

            if ($applicationId) {
                ApplicantProgramApplication::query()
                    ->where('tenant_id', $tenantId)
                    ->where('applicant_id', $applicant->id)
                    ->where('id', $applicationId)
                    ->firstOrFail();
            }

            $document = null;

            if (!empty($data['id'])) {
                $document = ApplicantDocument::query()
                    ->where('tenant_id', $tenantId)
                    ->where('applicant_id', $applicant->id)
                    ->where('id', $data['id'])
                    ->firstOrFail();

                $this->deletePhysicalFileIfExists($document);
            }

            $storedFileName = $this->generateStoredFileName($file);
            $folder = $this->buildFolderPath($tenantId, $applicant->id);

            $filePath = $file->storeAs($folder, $storedFileName, $this->disk);

            $payload = [
                'tenant_id' => $tenantId,
                'applicant_id' => $applicant->id,
                'applicant_program_application_id' => $applicationId,
                'document_type_id' => $data['document_type_id'] ?? null,
                'document_title' => $data['document_title'],
                'related_table' => $data['related_table'] ?? null,
                'related_id' => $data['related_id'] ?? null,
                'file_path' => $filePath,
                'original_file_name' => $file->getClientOriginalName(),
                'stored_file_name' => $storedFileName,
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'verification_status_code' => 'pending',
                'verified_at' => null,
                'verified_by' => null,
                'rejection_reason' => null,
                'remarks' => $data['remarks'] ?? null,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ];

            if ($document) {
                $document->update($payload);
                return $document->fresh();
            }

            return ApplicantDocument::create($payload);
        });
    }

    public function download(int $documentId): StreamedResponse
    {
        $document = $this->findTenantDocument($documentId);

        if (!$document->file_path || !Storage::disk($this->disk)->exists($document->file_path)) {
            abort(404, 'Document file not found.');
        }

        return Storage::disk($this->disk)->download(
            $document->file_path,
            $document->original_file_name ?: $document->stored_file_name
        );
    }

    public function preview(int $documentId): StreamedResponse
    {
        $document = $this->findTenantDocument($documentId);

        if (!$document->file_path || !Storage::disk($this->disk)->exists($document->file_path)) {
            abort(404, 'Document file not found.');
        }

        return Storage::disk($this->disk)->response(
            $document->file_path,
            $document->original_file_name ?: $document->stored_file_name,
            [
                'Content-Type' => $document->mime_type ?: 'application/octet-stream',
            ]
        );
    }

    public function delete(int $documentId): void
    {
        DB::transaction(function () use ($documentId) {
            $document = $this->findTenantDocument($documentId);

            $this->deletePhysicalFileIfExists($document);

            $document->delete();
        });
    }

    private function findTenantDocument(int $documentId): ApplicantDocument
    {
        $tenantId = auth()->user()?->tenant_id;

        if (!$tenantId) {
            abort(403, 'Tenant context is required.');
        }

        return ApplicantDocument::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $documentId)
            ->firstOrFail();
    }

    private function deletePhysicalFileIfExists(ApplicantDocument $document): void
    {
        if ($document->file_path && Storage::disk($this->disk)->exists($document->file_path)) {
            Storage::disk($this->disk)->delete($document->file_path);
        }
    }

    private function buildFolderPath(int $tenantId, int $applicantId): string
    {
        return "tenants/{$tenantId}/admission/applicants/{$applicantId}/documents";
    }

    private function generateStoredFileName(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();

        return now()->format('YmdHis') . '_' . Str::random(20) . '.' . strtolower($extension);
    }
}