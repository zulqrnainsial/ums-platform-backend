<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_course_registrations', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id')->index();

            $table->unsignedBigInteger('student_id')->index();
            $table->unsignedBigInteger('student_enrollment_id')->index();

            $table->unsignedBigInteger('program_id')->nullable()->index();
            $table->unsignedBigInteger('academic_session_id')->nullable()->index();
            $table->unsignedBigInteger('academic_term_id')->nullable()->index();
            $table->unsignedBigInteger('term_id')->nullable()->index();

            $table->unsignedBigInteger('curriculum_id')->nullable()->index();
            $table->unsignedBigInteger('curriculum_subject_id')->nullable()->index();
            $table->unsignedBigInteger('subject_id')->nullable()->index();

            $table->string('course_code', 100)->nullable()->index();
            $table->string('course_title', 255)->nullable();

            $table->decimal('credit_hours', 5, 2)->default(0);
            $table->string('subject_type_code', 80)->nullable();

            $table->string('registration_type', 50)->default('regular')->index();
            $table->string('status', 50)->default('registered')->index();

            $table->boolean('is_locked')->default(false);
            $table->timestamp('registered_at')->nullable();

            $table->text('remarks')->nullable();

            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();

            $table->timestamps();

            $table->unique(
                ['tenant_id', 'student_enrollment_id', 'subject_id', 'registration_type'],
                'scr_unique_enrollment_subject_type'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_course_registrations');
    }
};