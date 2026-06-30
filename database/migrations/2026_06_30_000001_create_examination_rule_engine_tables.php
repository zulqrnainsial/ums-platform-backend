<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('examination_rule_sets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');

            $table->string('rule_set_code', 100);
            $table->string('rule_set_name', 255);
            $table->text('description')->nullable();

            /*
             | Evaluation modes
             */
            $table->boolean('gpa_enabled')->default(true);
            $table->boolean('obe_enabled')->default(false);

            /*
             | absolute | relative | pass_fail
             */
            $table->string('grading_method_code', 50)->default('absolute');

            /*
             | curriculum_subject_marks
             | credit_hour_based
             | fixed_marks
             | custom_marks
             */
            $table->string('marks_basis_code', 50)
                ->default('curriculum_subject_marks');

            $table->decimal('marks_per_credit_hour', 8, 2)->nullable();
            $table->decimal('fixed_total_marks', 8, 2)->nullable();

            /*
             | combined
             | separate_pass_required
             */
            $table->string('theory_practical_evaluation_code', 50)
                ->default('combined');

            /*
             | marks
             | gpa
             | obe_attainment
             | marks_and_obe
             | gpa_and_obe
             */
            $table->string('subject_pass_basis_code', 50)
                ->default('marks');

            $table->decimal('minimum_subject_percentage', 8, 2)->nullable();
            $table->decimal('minimum_theory_percentage', 8, 2)->nullable();
            $table->decimal('minimum_practical_percentage', 8, 2)->nullable();

            /*
             | Academic standing controls
             */
            $table->boolean('promotion_enabled')->default(true);
            $table->boolean('probation_enabled')->default(true);
            $table->boolean('detention_enabled')->default(true);
            $table->boolean('drop_enabled')->default(true);

            $table->decimal('minimum_semester_gpa', 8, 2)->nullable();
            $table->decimal('minimum_cgpa', 8, 2)->nullable();

            $table->unsignedInteger('maximum_failed_courses')->nullable();
            $table->unsignedInteger('maximum_attempts_per_subject')->nullable();
            $table->unsignedInteger('maximum_probation_terms')->nullable();

            $table->boolean('re_registration_enabled')->default(true);
            $table->boolean('improvement_enabled')->default(true);

            /*
             | A student may improve only below this grade point.
             | Example: 2.50 means C+ or below may be improved.
             */
            $table->decimal('improvement_allowed_below_grade_point', 8, 2)
                ->nullable();

            /*
             | Result / transcript behavior
             */
            $table->boolean('transcript_enabled')->default(true);
            $table->boolean('include_obe_in_result_decision')->default(false);

            $table->string('status_code', 50)->default('active');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['tenant_id', 'rule_set_code'],
                'uq_exam_rule_sets_tenant_code'
            );

            $table->index(['tenant_id', 'status_code']);
        });

        Schema::create('examination_rule_set_bindings', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('examination_rule_set_id');

            $table->unsignedBigInteger('program_id')->nullable();
            $table->unsignedBigInteger('curriculum_id')->nullable();
            $table->unsignedBigInteger('student_batch_id')->nullable();

            $table->unsignedBigInteger('academic_session_id')->nullable();
            $table->unsignedBigInteger('academic_term_id')->nullable();

            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();

            $table->boolean('is_active')->default(true);
            $table->string('remarks', 1000)->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index([
                'tenant_id',
                'program_id',
                'curriculum_id',
                'student_batch_id',
            ], 'idx_exam_rule_binding_scope');

            $table->index([
                'tenant_id',
                'academic_session_id',
                'academic_term_id',
                'is_active',
            ], 'idx_exam_rule_binding_period');
        });

        Schema::create('grading_schemes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('examination_rule_set_id')->nullable();

            $table->string('scheme_code', 100);
            $table->string('scheme_name', 255);

            /*
             | absolute
             | relative_percentile
             | relative_rank
             | relative_z_score
             */
            $table->string('grading_method_code', 50);

            $table->boolean('is_default')->default(false);
            $table->string('status_code', 50)->default('active');
            $table->text('description')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['tenant_id', 'scheme_code'],
                'uq_grading_schemes_tenant_code'
            );

            $table->index([
                'tenant_id',
                'examination_rule_set_id',
                'status_code',
            ], 'idx_grading_scheme_rule_set');
        });

        Schema::create('grading_scheme_rows', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('grading_scheme_id');

            $table->unsignedInteger('sort_order')->default(1);

            /*
             | Grade identity
             */
            $table->string('grade_letter', 20);
            $table->decimal('grade_point', 8, 2)->default(0);
            $table->boolean('is_pass')->default(true);

            /*
             | Absolute grading range
             */
            $table->decimal('minimum_percentage', 8, 2)->nullable();
            $table->decimal('maximum_percentage', 8, 2)->nullable();

            /*
             | Relative grading / ready reckoner range
             */
            $table->decimal('minimum_percentile', 8, 2)->nullable();
            $table->decimal('maximum_percentile', 8, 2)->nullable();

            $table->unsignedInteger('minimum_rank')->nullable();
            $table->unsignedInteger('maximum_rank')->nullable();

            $table->decimal('minimum_z_score', 12, 4)->nullable();
            $table->decimal('maximum_z_score', 12, 4)->nullable();

            $table->string('remarks', 1000)->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            $table->unique(
                ['grading_scheme_id', 'sort_order'],
                'uq_grading_scheme_rows_order'
            );

            $table->index([
                'tenant_id',
                'grading_scheme_id',
            ], 'idx_grading_scheme_rows_scheme');
        });

        Schema::create('evaluation_schemes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('examination_rule_set_id')->nullable();

            $table->string('scheme_code', 100);
            $table->string('scheme_name', 255);

            /*
             | combined
             | separate_theory_practical
             */
            $table->string('evaluation_mode_code', 50)
                ->default('combined');

            $table->decimal('total_weightage_percentage', 8, 2)
                ->default(100);

            $table->string('status_code', 50)->default('active');
            $table->text('description')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['tenant_id', 'scheme_code'],
                'uq_evaluation_schemes_tenant_code'
            );

            $table->index([
                'tenant_id',
                'examination_rule_set_id',
                'status_code',
            ], 'idx_evaluation_scheme_rule_set');
        });

        Schema::create('evaluation_scheme_components', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('evaluation_scheme_id');

            $table->string('component_code', 100);
            $table->string('component_name', 255);

            /*
             | sessional
             | midterm
             | final
             | practical
             | viva
             | project
             | internship
             | other
             */
            $table->string('component_type_code', 50);

            /*
             | theory
             | practical
             | combined
             */
            $table->string('evaluation_part_code', 50)
                ->default('combined');

            $table->decimal('weightage_percentage', 8, 2);

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
                ['evaluation_scheme_id', 'component_code'],
                'uq_eval_scheme_component_code'
            );

            $table->index([
                'tenant_id',
                'evaluation_scheme_id',
                'status_code',
            ], 'idx_eval_scheme_components');
        });

        Schema::create('evaluation_scheme_component_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('evaluation_scheme_component_id');

            $table->string('item_code', 100);
            $table->string('item_name', 255);

            /*
             | quiz
             | assignment
             | test
             | presentation
             | lab_task
             | lab_viva
             | project_task
             | other
             */
            $table->string('item_type_code', 50);

            $table->decimal('weightage_percentage', 8, 2);

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
                    'evaluation_scheme_component_id',
                    'item_code',
                ],
                'uq_eval_component_items_code'
            );

            $table->index([
                'tenant_id',
                'evaluation_scheme_component_id',
                'status_code',
            ], 'idx_eval_component_items');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_scheme_component_items');
        Schema::dropIfExists('evaluation_scheme_components');
        Schema::dropIfExists('evaluation_schemes');
        Schema::dropIfExists('grading_scheme_rows');
        Schema::dropIfExists('grading_schemes');
        Schema::dropIfExists('examination_rule_set_bindings');
        Schema::dropIfExists('examination_rule_sets');
    }
};