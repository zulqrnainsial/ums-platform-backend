<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admission_confirmations', function (Blueprint $table) {
            if (!Schema::hasColumn('admission_confirmations', 'student_id')) {
                $table->unsignedBigInteger('student_id')->nullable()->after('applicant_id');
            }

            if (!Schema::hasColumn('admission_confirmations', 'department_id')) {
                $table->unsignedBigInteger('department_id')->nullable()->after('offered_program_id');
            }

            if (!Schema::hasColumn('admission_confirmations', 'program_id')) {
                $table->unsignedBigInteger('program_id')->nullable()->after('department_id');
            }

            if (!Schema::hasColumn('admission_confirmations', 'transfer_status_code')) {
                $table->string('transfer_status_code', 50)->nullable()->after('status_code');
            }

            if (!Schema::hasColumn('admission_confirmations', 'transferred_at')) {
                $table->timestamp('transferred_at')->nullable()->after('confirmed_at');
            }
        });

        Schema::table('admission_merit_list_applicants', function (Blueprint $table) {
            if (!Schema::hasColumn('admission_merit_list_applicants', 'student_id')) {
                $table->unsignedBigInteger('student_id')->nullable()->after('applicant_id');
            }

            if (!Schema::hasColumn('admission_merit_list_applicants', 'department_transfer_status_code')) {
                $table->string('department_transfer_status_code', 50)->nullable()->after('admission_confirmation_status_code');
            }

            if (!Schema::hasColumn('admission_merit_list_applicants', 'department_transferred_at')) {
                $table->timestamp('department_transferred_at')->nullable()->after('department_transfer_status_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('admission_confirmations', function (Blueprint $table) {
            foreach ([
                'transferred_at',
                'transfer_status_code',
                'program_id',
                'department_id',
                'student_id',
            ] as $column) {
                if (Schema::hasColumn('admission_confirmations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('admission_merit_list_applicants', function (Blueprint $table) {
            foreach ([
                'department_transferred_at',
                'department_transfer_status_code',
                'student_id',
            ] as $column) {
                if (Schema::hasColumn('admission_merit_list_applicants', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};