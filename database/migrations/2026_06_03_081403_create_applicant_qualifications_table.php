<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applicant_qualifications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('applicant_id')->constrained('applicants')->cascadeOnDelete();

            $table->foreignId('qualification_level_id')->nullable()->constrained('lookup_values')->nullOnDelete();
            $table->foreignId('education_board_id')->nullable()->constrained('lookup_values')->nullOnDelete();
            $table->foreignId('external_institution_id')->nullable()->constrained('lookup_values')->nullOnDelete();
            $table->foreignId('subject_group_id')->nullable()->constrained('lookup_values')->nullOnDelete();

            $table->string('degree_class_name')->nullable();
            $table->string('roll_no')->nullable();
            $table->string('registration_no')->nullable();
            $table->string('passing_year')->nullable();

            $table->string('result_status_code')->default('passed');

            $table->decimal('total_marks', 10, 2)->nullable();
            $table->decimal('obtained_marks', 10, 2)->nullable();
            $table->decimal('percentage', 6, 2)->nullable();

            $table->decimal('cgpa', 5, 2)->nullable();
            $table->decimal('cgpa_scale', 5, 2)->nullable();

            $table->string('grade')->nullable();

            $table->boolean('equivalence_required')->default(false);
            $table->string('equivalence_status_code')->nullable();

            $table->boolean('is_final_result')->default(true);
            $table->boolean('is_verified')->default(false);

            $table->text('remarks')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'applicant_id'], 'aq_tenant_app_idx');
            $table->index(['tenant_id', 'qualification_level_id'], 'aq_tenant_ql_idx');
            $table->index(['tenant_id', 'subject_group_id'], 'aq_tenant_sg_idx');
            $table->index(['tenant_id', 'result_status_code'], 'aq_tenant_result_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applicant_qualifications');
    }
};