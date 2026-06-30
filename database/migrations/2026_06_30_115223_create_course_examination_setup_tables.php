<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_examination_setups', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id');

            $table->unsignedBigInteger('course_offering_id');

            /*
             | Original policy references.
             | These identify the rule and schemes selected at setup time.
             */
            $table->unsignedBigInteger('examination_rule_set_id')->nullable();
            $table->unsignedBigInteger('examination_rule_set_binding_id')->nullable();

            $table->unsignedBigInteger('grading_scheme_id')->nullable();
            $table->unsignedBigInteger('evaluation_scheme_id')->nullable();

            /*
             | Academic context copied from course_offerings.
             */
            $table->unsignedBigInteger('academic_session_id')->nullable();
            $table->unsignedBigInteger('academic_term_id')->nullable();
            $table->unsignedBigInteger('program_id')->nullable();
            $table->unsignedBigInteger('student_batch_id')->nullable();
            $table->unsignedBigInteger('section_id')->nullable();
            $table->unsignedBigInteger('academic_teaching_group_id')->nullable();

            $table->unsignedBigInteger('curriculum_subject_id')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();

            /*
             | Course identity snapshot.
             */
            $table->string('course_code', 100)->nullable();
            $table->string('course_title', 255)->nullable();
            $table->string('subject_type_code', 100)->nullable();

            $table->decimal('credit_hours', 8, 2)->nullable();

            /*
             | Marks snapshot.
             */
            $table->string('marks_basis_code', 50)
                ->default('curriculum_subject_marks');

            $table->decimal('marks_per_credit_hour', 8, 2)->nullable();

            $table->decimal('configured_total_marks', 10, 2)->nullable();
            $table->decimal('effective_total_marks', 10, 2)->nullable();

            $table->decimal('passing_marks', 10, 2)->nullable();
            $table->decimal('passing_percentage', 8, 2)->nullable();

            /*
             | Separate pass configuration snapshot.
             */
            $table->string('theory_practical_evaluation_code', 50)
                ->default('combined');

            $table->decimal('minimum_theory_percentage', 8, 2)->nullable();
            $table->decimal('minimum_practical_percentage', 8, 2)->nullable();

            /*
             | Result policy snapshot.
             */
            $table->string('grading_method_code', 50)->nullable();
            $table->string('subject_pass_basis_code', 50)->nullable();

            $table->boolean('gpa_enabled')->default(true);
            $table->boolean('obe_enabled')->default(false);
            $table->boolean('include_obe_in_result_decision')->default(false);

            /*
             | draft: setup may still be changed
             | configured: structure copied and ready for activities
             | locked: marks/activity setup has started; configuration cannot change
             | archived: historical only
             */
            $table->string('status_code', 50)->default('draft');

            $table->text('remarks')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['tenant_id', 'course_offering_id'],
                'uq_course_examination_setup_offering'
            );

            $table->index([
                'tenant_id',
                'academic_session_id',
                'academic_term_id',
                'program_id',
                'student_batch_id',
            ], 'idx_course_exam_setup_context');

            $table->index([
                'tenant_id',
                'status_code',
            ], 'idx_course_exam_setup_status');
        });

        Schema::create('course_examination_setup_components', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('course_examination_setup_id');

            /*
             | Source component retained only for traceability.
             */
            $table->unsignedBigInteger('evaluation_scheme_component_id')->nullable();

            $table->string('component_code', 100);
            $table->string('component_name', 255);

            $table->string('component_type_code', 50);
            $table->string('evaluation_part_code', 50)
                ->default('combined');

            /*
             | Percentage within the full course evaluation.
             */
            $table->decimal('weightage_percentage', 8, 2);

            /*
             | Calculated snapshot marks for this course.
             */
            $table->decimal('maximum_marks', 10, 2)->nullable();
            $table->decimal('passing_marks', 10, 2)->nullable();

            $table->boolean('is_mandatory')->default(true);
            $table->boolean('requires_separate_pass')->default(false);

            $table->unsignedInteger('sort_order')->default(1);
            $table->string('status_code', 50)->default('active');

            $table->text('remarks')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['course_examination_setup_id', 'component_code'],
                'uq_course_exam_setup_component_code'
            );

            $table->index([
                'tenant_id',
                'course_examination_setup_id',
                'status_code',
            ], 'idx_course_exam_setup_components');
        });

        Schema::create('course_examination_setup_component_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id');

            $table->unsignedBigInteger('course_examination_setup_component_id');

            /*
             | Source item retained only for traceability.
             */
            $table->unsignedBigInteger('evaluation_scheme_component_item_id')
                ->nullable();

            $table->string('item_code', 100);
            $table->string('item_name', 255);
            $table->string('item_type_code', 50);

            /*
             | Percentage within parent component.
             */
            $table->decimal('weightage_percentage', 8, 2);

            /*
             | Calculated snapshot marks for this course.
             */
            $table->decimal('maximum_marks', 10, 2)->nullable();
            $table->decimal('passing_marks', 10, 2)->nullable();

            $table->boolean('is_mandatory')->default(true);

            $table->unsignedInteger('sort_order')->default(1);
            $table->string('status_code', 50)->default('active');

            $table->text('remarks')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                [
                    'course_examination_setup_component_id',
                    'item_code',
                ],
                'uq_course_exam_setup_component_item_code'
            );

            $table->index([
                'tenant_id',
                'course_examination_setup_component_id',
                'status_code',
            ], 'idx_course_exam_setup_component_items');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_examination_setup_component_items');
        Schema::dropIfExists('course_examination_setup_components');
        Schema::dropIfExists('course_examination_setups');
    }
};