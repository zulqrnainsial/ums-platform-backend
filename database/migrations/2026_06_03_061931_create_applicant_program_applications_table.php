<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applicant_program_applications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();

            $table->foreignId('admission_session_id')
                ->constrained('admission_sessions')
                ->cascadeOnDelete();

            $table->foreignId('applicant_id')
                ->constrained('applicants')
                ->cascadeOnDelete();

            $table->foreignId('offered_program_id')
                ->constrained('offered_programs')
                ->cascadeOnDelete();

            $table->foreignId('program_quota_seat_id')
                ->constrained('program_quota_seats')
                ->restrictOnDelete();

            $table->string('application_no')->nullable();

            $table->integer('preference_order')->default(1);

            $table->string('eligibility_status_code')->default('pending');
            $table->json('eligibility_result_json')->nullable();
            $table->text('eligibility_remarks')->nullable();

            $table->string('application_status_code')->default('draft');
            $table->string('document_status_code')->default('pending');
            $table->string('fee_status_code')->default('unpaid');
            $table->string('test_status_code')->default('not_required');

            $table->decimal('merit_score', 10, 4)->nullable();
            $table->integer('merit_rank')->nullable();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('confirmed_at')->nullable();

            $table->text('remarks')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'application_no']);
            $table->unique(
                ['tenant_id', 'applicant_id', 'offered_program_id', 'program_quota_seat_id'],
                'applicant_program_quota_unique'
            );

            $table->index(['tenant_id', 'admission_session_id'],'ap_prog_app_ten_adm_ses_id');
            $table->index(['tenant_id', 'applicant_id']);
            $table->index(['tenant_id', 'offered_program_id'],'ap_prog_app_ten_off_prog_id');
            $table->index(['tenant_id', 'program_quota_seat_id'],'ap_prog_app_ten_porg_q_seat_id');
            $table->index(['tenant_id', 'application_status_code'],'ap_prog_app_ten_app_sat_code');
            $table->index(['tenant_id', 'eligibility_status_code'],'ap_prog_app_ten_eli_stat_code');
            $table->index(['tenant_id', 'fee_status_code'],'ap_prog_app_ten_fee_sta_code');
            $table->index(['tenant_id', 'test_status_code'],'ap_prog_app_ten_test_sta_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applicant_program_applications');
    }
};