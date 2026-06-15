<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_requests', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('student_id')->index();
            $table->unsignedBigInteger('student_enrollment_id')->nullable()->index();

            $table->string('request_no', 100)->nullable()->index();
            $table->string('request_type', 80)->index();

            $table->string('title', 255)->nullable();
            $table->text('description')->nullable();

            $table->json('requested_payload_json')->nullable();
            $table->json('admin_decision_payload_json')->nullable();

            $table->unsignedBigInteger('related_document_id')->nullable()->index();
            $table->unsignedBigInteger('related_course_registration_id')->nullable()->index();
            $table->unsignedBigInteger('related_curriculum_subject_id')->nullable()->index();
            $table->unsignedBigInteger('related_subject_id')->nullable()->index();

            $table->string('status', 50)->default('pending')->index();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable()->index();

            $table->text('student_remarks')->nullable();
            $table->text('admin_remarks')->nullable();

            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();

            $table->timestamps();

            $table->index(['tenant_id', 'student_id', 'request_type', 'status'], 'sr_tenant_student_type_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_requests');
    }
};