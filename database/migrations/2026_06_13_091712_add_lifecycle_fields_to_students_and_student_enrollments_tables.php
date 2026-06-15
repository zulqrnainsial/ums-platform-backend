<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                if (!Schema::hasColumn('students', 'lifecycle_status')) {
                    if (Schema::hasColumn('students', 'student_status')) {
                        $table->string('lifecycle_status', 50)->nullable()->index()->after('student_status');
                    } else {
                        $table->string('lifecycle_status', 50)->nullable()->index();
                    }
                }

                if (!Schema::hasColumn('students', 'lifecycle_reason')) {
                    $table->text('lifecycle_reason')->nullable()->after('lifecycle_status');
                }

                if (!Schema::hasColumn('students', 'lifecycle_effective_date')) {
                    $table->date('lifecycle_effective_date')->nullable()->after('lifecycle_reason');
                }

                if (!Schema::hasColumn('students', 'lifecycle_action_at')) {
                    $table->timestamp('lifecycle_action_at')->nullable()->after('lifecycle_effective_date');
                }

                if (!Schema::hasColumn('students', 'lifecycle_action_by')) {
                    $table->unsignedBigInteger('lifecycle_action_by')->nullable()->index()->after('lifecycle_action_at');
                }
            });
        }

        if (Schema::hasTable('student_enrollments')) {
            Schema::table('student_enrollments', function (Blueprint $table) {
                if (!Schema::hasColumn('student_enrollments', 'lifecycle_status')) {
                    if (Schema::hasColumn('student_enrollments', 'status')) {
                        $table->string('lifecycle_status', 50)->nullable()->index()->after('status');
                    } elseif (Schema::hasColumn('student_enrollments', 'allocation_status')) {
                        $table->string('lifecycle_status', 50)->nullable()->index()->after('allocation_status');
                    } else {
                        $table->string('lifecycle_status', 50)->nullable()->index();
                    }
                }

                if (!Schema::hasColumn('student_enrollments', 'lifecycle_reason')) {
                    $table->text('lifecycle_reason')->nullable()->after('lifecycle_status');
                }

                if (!Schema::hasColumn('student_enrollments', 'lifecycle_effective_date')) {
                    $table->date('lifecycle_effective_date')->nullable()->after('lifecycle_reason');
                }

                if (!Schema::hasColumn('student_enrollments', 'lifecycle_action_at')) {
                    $table->timestamp('lifecycle_action_at')->nullable()->after('lifecycle_effective_date');
                }

                if (!Schema::hasColumn('student_enrollments', 'lifecycle_action_by')) {
                    $table->unsignedBigInteger('lifecycle_action_by')->nullable()->index()->after('lifecycle_action_at');
                }

                if (!Schema::hasColumn('student_enrollments', 'transfer_from_enrollment_id')) {
                    $table->unsignedBigInteger('transfer_from_enrollment_id')->nullable()->index()->after('lifecycle_action_by');
                }

                if (!Schema::hasColumn('student_enrollments', 'transfer_remarks')) {
                    $table->text('transfer_remarks')->nullable()->after('transfer_from_enrollment_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('student_enrollments')) {
            Schema::table('student_enrollments', function (Blueprint $table) {
                foreach ([
                    'transfer_remarks',
                    'transfer_from_enrollment_id',
                    'lifecycle_action_by',
                    'lifecycle_action_at',
                    'lifecycle_effective_date',
                    'lifecycle_reason',
                    'lifecycle_status',
                ] as $column) {
                    if (Schema::hasColumn('student_enrollments', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                foreach ([
                    'lifecycle_action_by',
                    'lifecycle_action_at',
                    'lifecycle_effective_date',
                    'lifecycle_reason',
                    'lifecycle_status',
                ] as $column) {
                    if (Schema::hasColumn('students', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};