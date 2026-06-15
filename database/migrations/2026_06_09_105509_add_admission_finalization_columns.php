<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admission_confirmations', function (Blueprint $table) {
            if (!Schema::hasColumn('admission_confirmations', 'student_enrollment_id')) {
                $table->unsignedBigInteger('student_enrollment_id')->nullable()->after('student_id');
            }

            if (!Schema::hasColumn('admission_confirmations', 'finalization_status_code')) {
                $table->string('finalization_status_code', 50)->nullable()->after('transfer_status_code');
            }

            if (!Schema::hasColumn('admission_confirmations', 'finalized_at')) {
                $table->timestamp('finalized_at')->nullable()->after('transferred_at');
            }

            if (!Schema::hasColumn('admission_confirmations', 'finalized_by')) {
                $table->unsignedBigInteger('finalized_by')->nullable()->after('finalized_at');
            }

            if (!Schema::hasColumn('admission_confirmations', 'finalization_remarks')) {
                $table->text('finalization_remarks')->nullable()->after('finalized_by');
            }
        });

        Schema::table('admission_merit_list_applicants', function (Blueprint $table) {
            if (!Schema::hasColumn('admission_merit_list_applicants', 'student_enrollment_id')) {
                $table->unsignedBigInteger('student_enrollment_id')->nullable()->after('student_id');
            }

            if (!Schema::hasColumn('admission_merit_list_applicants', 'admission_finalization_status_code')) {
                $table->string('admission_finalization_status_code', 50)->nullable()->after('department_transfer_status_code');
            }

            if (!Schema::hasColumn('admission_merit_list_applicants', 'admission_finalized_at')) {
                $table->timestamp('admission_finalized_at')->nullable()->after('department_transferred_at');
            }
        });

        if (!Schema::hasTable('admission_finalization_logs')) {
            Schema::create('admission_finalization_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable();

                $table->unsignedBigInteger('admission_confirmation_id');
                $table->unsignedBigInteger('applicant_id')->nullable();
                $table->unsignedBigInteger('student_id')->nullable();
                $table->unsignedBigInteger('student_enrollment_id')->nullable();

                $table->string('action_code', 80);
                $table->string('status_code', 50)->nullable();
                $table->text('remarks')->nullable();

                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'admission_confirmation_id'], 'adm_fin_log_tenant_conf_idx');
                $table->index('student_id', 'adm_fin_log_student_idx');
                $table->index('student_enrollment_id', 'adm_fin_log_enrollment_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('admission_finalization_logs')) {
            Schema::dropIfExists('admission_finalization_logs');
        }

        Schema::table('admission_merit_list_applicants', function (Blueprint $table) {
            foreach ([
                'admission_finalized_at',
                'admission_finalization_status_code',
                'student_enrollment_id',
            ] as $column) {
                if (Schema::hasColumn('admission_merit_list_applicants', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('admission_confirmations', function (Blueprint $table) {
            foreach ([
                'finalization_remarks',
                'finalized_by',
                'finalized_at',
                'finalization_status_code',
                'student_enrollment_id',
            ] as $column) {
                if (Schema::hasColumn('admission_confirmations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};