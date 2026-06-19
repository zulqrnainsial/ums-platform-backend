<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('attendance_sessions')) {
            return;
        }

        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id')->index();

            $table->unsignedBigInteger('academic_session_id')->nullable()->index();
            $table->unsignedBigInteger('academic_term_id')->nullable()->index();
            $table->unsignedBigInteger('program_id')->nullable()->index();

            $table->unsignedBigInteger('student_batch_id')->nullable()->index();
            $table->unsignedBigInteger('section_id')->nullable()->index();

            $table->unsignedBigInteger('curriculum_subject_id')->nullable()->index();
            $table->unsignedBigInteger('subject_id')->nullable()->index();

            $table->string('course_code', 100)->nullable()->index();
            $table->string('course_title', 255)->nullable();

            $table->date('attendance_date')->index();

            $table->string('session_type', 50)->default('lecture')->index();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();

            $table->string('topic', 255)->nullable();
            $table->text('remarks')->nullable();

            $table->string('status_code', 50)->default('draft')->index();

            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable()->index();

            $table->timestamp('locked_at')->nullable();
            $table->unsignedBigInteger('locked_by')->nullable()->index();

            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();

            $table->timestamps();
            $table->softDeletes();

            $table->index(
                [
                    'tenant_id',
                    'academic_session_id',
                    'academic_term_id',
                    'program_id',
                    'student_batch_id',
                    'section_id',
                    'curriculum_subject_id',
                    'attendance_date',
                ],
                'att_sessions_main_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_sessions');
    }
};