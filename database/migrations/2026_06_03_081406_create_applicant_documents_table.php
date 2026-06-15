<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applicant_documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();

            $table->foreignId('applicant_id')->constrained('applicants')->cascadeOnDelete();

            $table->foreignId('applicant_program_application_id')
                ->nullable()
                ->constrained('applicant_program_applications')
                ->nullOnDelete();

            $table->foreignId('document_type_id')->nullable()->constrained('lookup_values')->nullOnDelete();

            $table->string('document_title');

            /*
             | Optional polymorphic-style reference without Laravel morphs.
             | Example:
             | related_table = applicant_qualifications
             | related_id = 5
             */
            $table->string('related_table')->nullable();
            $table->unsignedBigInteger('related_id')->nullable();

            $table->string('file_path')->nullable();
            $table->string('original_file_name')->nullable();
            $table->string('stored_file_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();

            $table->string('verification_status_code')->default('pending');

            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();

            $table->text('rejection_reason')->nullable();
            $table->text('remarks')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'applicant_id'], 'adoc_tenant_app_idx');
            $table->index(['tenant_id', 'applicant_program_application_id'], 'adoc_tenant_appl_idx');
            $table->index(['tenant_id', 'document_type_id'], 'adoc_tenant_doc_type_idx');
            $table->index(['tenant_id', 'verification_status_code'], 'adoc_tenant_vstatus_idx');
            $table->index(['related_table', 'related_id'], 'adoc_related_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applicant_documents');
    }
};