<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('attendance_records')) {
            return;
        }

        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id')->index();

            $table->unsignedBigInteger('attendance_session_id')->index();

            $table->unsignedBigInteger('student_id')->index();
            $table->unsignedBigInteger('student_enrollment_id')->index();
            $table->unsignedBigInteger('student_course_registration_id')->index();

            $table->string('status_code', 50)->default('present')->index();

            $table->timestamp('marked_at')->nullable();
            $table->unsignedBigInteger('marked_by')->nullable()->index();

            $table->text('remarks')->nullable();

            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();

            $table->timestamps();

            $table->unique(
                ['attendance_session_id', 'student_course_registration_id'],
                'att_records_session_course_unique'
            );

            $table->index(
                [
                    'tenant_id',
                    'student_id',
                    'student_enrollment_id',
                    'student_course_registration_id',
                    'status_code',
                ],
                'att_records_student_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};