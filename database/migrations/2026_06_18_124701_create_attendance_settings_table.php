<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('attendance_settings')) {
            return;
        }

        Schema::create('attendance_settings', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id')->index();

            $table->unsignedBigInteger('academic_session_id')->nullable()->index();
            $table->unsignedBigInteger('program_id')->nullable()->index();
            $table->unsignedBigInteger('academic_term_id')->nullable()->index();

            $table->unsignedInteger('late_after_minutes')->nullable();
            $table->unsignedInteger('absent_after_minutes')->nullable();

            $table->decimal('minimum_attendance_percentage', 5, 2)->nullable();

            $table->boolean('allow_student_view')->default(true);
            $table->boolean('allow_teacher_edit_after_submit')->default(false);

            $table->string('status_code', 50)->default('active')->index();

            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();

            $table->timestamps();

            $table->unique(
                ['tenant_id', 'academic_session_id', 'program_id', 'academic_term_id'],
                'att_settings_scope_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_settings');
    }
};