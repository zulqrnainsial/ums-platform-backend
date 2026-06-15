<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offered_programs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();

            $table->foreignId('admission_session_id')
                ->constrained('admission_sessions')
                ->cascadeOnDelete();

            $table->foreignId('academic_session_id')
                ->nullable()
                ->constrained('academic_sessions')
                ->nullOnDelete();

            $table->foreignId('campus_id')
                ->nullable()
                ->constrained('campuses')
                ->nullOnDelete();

            $table->foreignId('faculty_id')
                ->nullable()
                ->constrained('faculties')
                ->nullOnDelete();

            $table->foreignId('institute_id')
                ->nullable()
                ->constrained('institutes')
                ->nullOnDelete();

            $table->foreignId('department_id')
                ->nullable()
                ->constrained('departments')
                ->nullOnDelete();

            $table->foreignId('program_level_id')
                ->nullable()
                ->constrained('program_levels')
                ->nullOnDelete();

            $table->foreignId('program_id')
                ->constrained('programs')
                ->cascadeOnDelete();

            $table->foreignId('curriculum_id')
                ->nullable()
                ->constrained('curriculums')
                ->nullOnDelete();

            /*
             | Reuse existing student_batches.
             | Nullable because batch may be created before or after admission.
             */
            $table->foreignId('student_batch_id')
                ->nullable()
                ->constrained('student_batches')
                ->nullOnDelete();

            $table->string('code');
            $table->string('title');

            $table->foreignId('shift_id')
                ->nullable()
                ->constrained('lookup_values')
                ->nullOnDelete();

            $table->string('shift_code')->nullable(); // morning/evening/weekend/online etc.

            $table->decimal('application_fee', 12, 2)->default(0);
            $table->decimal('admission_fee', 12, 2)->default(0);

            $table->boolean('requires_test')->default(false);
            $table->boolean('requires_interview')->default(false);
            $table->boolean('requires_experience')->default(false);
            $table->boolean('requires_research_profile')->default(false);

            $table->boolean('is_published')->default(false);

            $table->date('application_start_date')->nullable();
            $table->date('application_end_date')->nullable();

            $table->string('status_code')->default('draft'); // draft/open/closed/suspended/completed

            $table->text('description')->nullable();
            $table->text('remarks')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['tenant_id', 'admission_session_id', 'program_id', 'shift_code'],
                'offered_program_unique'
            );

            $table->index(['tenant_id', 'admission_session_id']);
            $table->index(['tenant_id', 'program_id']);
            $table->index(['tenant_id', 'department_id']);
            $table->index(['tenant_id', 'status_code']);
            $table->index(['tenant_id', 'is_published']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offered_programs');
    }
};