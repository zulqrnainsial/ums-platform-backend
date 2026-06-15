<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('applicant_application_groups')) {
            return;
        }

        Schema::create('applicant_application_groups', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('admission_session_id')->constrained('admission_sessions')->cascadeOnDelete();
            $table->foreignId('applicant_id')->constrained('applicants')->cascadeOnDelete();

            $table->string('application_group_no', 60);

            /*
             | draft / submitted / under_review / merit_processed / confirmed / cancelled
             */
            $table->string('status_code', 50)->default('draft');

            $table->timestamp('submitted_at')->nullable();

            $table->text('remarks')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'application_group_no'], 'aag_tenant_group_no_unique');
            $table->unique(
                ['tenant_id', 'admission_session_id', 'applicant_id'],
                'aag_tenant_session_app_unique'
            );

            $table->index(['tenant_id', 'applicant_id'], 'aag_tenant_app_idx');
            $table->index(['tenant_id', 'admission_session_id'], 'aag_tenant_session_idx');
            $table->index(['tenant_id', 'status_code'], 'aag_tenant_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applicant_application_groups');
    }
};
