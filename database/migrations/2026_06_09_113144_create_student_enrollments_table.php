<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('student_enrollments')) {
            Schema::create('student_enrollments', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('tenant_id')->nullable();

                $table->unsignedBigInteger('student_id');
                $table->unsignedBigInteger('admission_confirmation_id')->nullable();
                $table->unsignedBigInteger('admission_merit_list_applicant_id')->nullable();
                $table->unsignedBigInteger('admission_merit_list_id')->nullable();

                $table->unsignedBigInteger('admission_session_id')->nullable();
                $table->unsignedBigInteger('academic_session_id')->nullable();

                $table->unsignedBigInteger('offered_program_id')->nullable();
                $table->unsignedBigInteger('program_id')->nullable();
                $table->unsignedBigInteger('department_id')->nullable();
                $table->unsignedBigInteger('program_quota_seat_id')->nullable();

                $table->string('enrollment_no', 80)->nullable();
                $table->string('registration_no', 80)->nullable();
                $table->string('roll_no', 80)->nullable();

                $table->string('enrollment_type_code', 50)->nullable()->default('admission');
                $table->string('status_code', 50)->nullable()->default('active');
                $table->string('enrollment_status_code', 50)->nullable()->default('enrolled');

                $table->timestamp('enrolled_at')->nullable();

                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();

                $table->timestamps();

                $table->index(['tenant_id', 'student_id'], 'stu_enr_tenant_student_idx');
                $table->index(['tenant_id', 'admission_session_id'], 'stu_enr_tenant_adm_session_idx');
                $table->index(['tenant_id', 'academic_session_id'], 'stu_enr_tenant_acd_session_idx');
                $table->index(['offered_program_id', 'program_quota_seat_id'], 'stu_enr_offer_quota_idx');
                $table->index('admission_confirmation_id', 'stu_enr_confirmation_idx');
                $table->index('admission_merit_list_applicant_id', 'stu_enr_mla_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_enrollments');
    }
};