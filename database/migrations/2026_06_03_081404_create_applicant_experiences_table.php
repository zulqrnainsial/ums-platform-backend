<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applicant_experiences', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('applicant_id')->constrained('applicants')->cascadeOnDelete();

            $table->string('organization_name');
            $table->string('designation')->nullable();

            $table->foreignId('employment_type_id')->nullable()->constrained('lookup_values')->nullOnDelete();
            $table->foreignId('experience_area_id')->nullable()->constrained('lookup_values')->nullOnDelete();

            $table->date('from_date')->nullable();
            $table->date('to_date')->nullable();

            $table->boolean('currently_working')->default(false);
            $table->integer('total_months')->default(0);

            $table->string('status_code')->default('active');

            $table->text('job_description')->nullable();
            $table->text('remarks')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'applicant_id'], 'ae_tenant_app_idx');
            $table->index(['tenant_id', 'employment_type_id'], 'ae_tenant_emp_type_idx');
            $table->index(['tenant_id', 'experience_area_id'], 'ae_tenant_area_idx');
            $table->index(['tenant_id', 'status_code'], 'ae_tenant_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applicant_experiences');
    }
};