<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_previous_educations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();

            $table->foreignId('qualification_level_id')->nullable()->constrained('lookup_values')->nullOnDelete();
            $table->foreignId('education_board_id')->nullable()->constrained('lookup_values')->nullOnDelete();
            $table->foreignId('external_institution_id')->nullable()->constrained('lookup_values')->nullOnDelete();

            $table->string('degree_class_name')->nullable();
            $table->string('roll_no')->nullable();
            $table->string('registration_no')->nullable();
            $table->string('passing_year')->nullable();

            $table->integer('total_marks')->nullable();
            $table->integer('obtained_marks')->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->string('grade')->nullable();
            $table->string('cgpa')->nullable();

            $table->string('document_path')->nullable();

            $table->text('remarks')->nullable();

            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'student_id']);
            $table->index(['tenant_id', 'qualification_level_id'],'st_prev_qual_level_id');
            $table->index(['tenant_id', 'education_board_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_previous_educations');
    }
};