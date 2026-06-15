<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('student_enrollments')) {
            return;
        }

        Schema::table('student_enrollments', function (Blueprint $table) {
            if (!Schema::hasColumn('student_enrollments', 'student_batch_id')) {
                $table->unsignedBigInteger('student_batch_id')->nullable()->index()->after('student_id');
            }

            if (!Schema::hasColumn('student_enrollments', 'section')) {
                $table->string('section', 50)->nullable()->index()->after('student_batch_id');
            }

            if (!Schema::hasColumn('student_enrollments', 'roll_no')) {
                $table->string('roll_no', 100)->nullable()->index()->after('section');
            }

            if (!Schema::hasColumn('student_enrollments', 'registration_no')) {
                $table->string('registration_no', 100)->nullable()->index()->after('roll_no');
            }

            if (!Schema::hasColumn('student_enrollments', 'roll_sequence_no')) {
                $table->unsignedInteger('roll_sequence_no')->nullable()->index()->after('registration_no');
            }

            if (!Schema::hasColumn('student_enrollments', 'allocation_status')) {
                $table->string('allocation_status', 50)->default('pending')->index()->after('roll_sequence_no');
            }

            if (!Schema::hasColumn('student_enrollments', 'allocated_at')) {
                $table->timestamp('allocated_at')->nullable()->after('allocation_status');
            }

            if (!Schema::hasColumn('student_enrollments', 'allocated_by')) {
                $table->unsignedBigInteger('allocated_by')->nullable()->index()->after('allocated_at');
            }

            if (!Schema::hasColumn('student_enrollments', 'allocation_remarks')) {
                $table->text('allocation_remarks')->nullable()->after('allocated_by');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('student_enrollments')) {
            return;
        }

        Schema::table('student_enrollments', function (Blueprint $table) {
            foreach ([
                'allocation_remarks',
                'allocated_by',
                'allocated_at',
                'allocation_status',
                'roll_sequence_no',
                'registration_no',
                'roll_no',
                'section',
                'student_batch_id',
            ] as $column) {
                if (Schema::hasColumn('student_enrollments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};