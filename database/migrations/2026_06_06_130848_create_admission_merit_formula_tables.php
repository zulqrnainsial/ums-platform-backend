<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('admission_merit_formulas')) {
            Schema::create('admission_merit_formulas', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('admission_session_id')->nullable();

                $table->string('code', 80);
                $table->string('title', 200);
                $table->text('description')->nullable();

                /*
                 | Formula type:
                 | standard, quota_specific, program_specific, group_specific
                 */
                $table->string('formula_type_code', 80)->default('standard');

                /*
                 | Calculation controls
                 */
                $table->decimal('total_weight', 8, 2)->default(100);
                $table->decimal('passing_merit_score', 8, 2)->nullable();
                $table->unsignedTinyInteger('rounding_precision')->default(2);

                /*
                 | Tie breaking strategy can later include:
                 | test_score_desc, inter_marks_desc, age_asc, application_no_asc
                 */
                $table->json('tie_breaker_json')->nullable();

                /*
                 | Extra rules:
                 | allow_bonus_marks, cap_at_100, require_all_mandatory_components etc.
                 */
                $table->json('rules_json')->nullable();

                $table->string('status_code', 80)->default('active');

                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->unique(['tenant_id', 'code'], 'adm_merit_formula_tenant_code_unique');

                $table->index(['tenant_id', 'admission_session_id'], 'adm_merit_formula_session_idx');
                $table->index(['tenant_id', 'status_code'], 'adm_merit_formula_status_idx');

                $table->foreign('tenant_id', 'adm_merit_formula_tenant_fk')
                    ->references('id')
                    ->on('tenants')
                    ->cascadeOnDelete();

                $table->foreign('admission_session_id', 'adm_merit_formula_session_fk')
                    ->references('id')
                    ->on('admission_sessions')
                    ->nullOnDelete();
            });
        }

        if (!Schema::hasTable('admission_merit_formula_components')) {
            Schema::create('admission_merit_formula_components', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('admission_merit_formula_id');

                $table->string('code', 80);
                $table->string('title', 200);
                $table->text('description')->nullable();

                /*
                 | qualification, test, interview, manual, bonus, deduction
                 */
                $table->string('component_type_code', 80);

                /*
                 | applicant_qualification, applicant_test_result,
                 | assessment_result, manual_entry, fixed_bonus
                 */
                $table->string('source_type_code', 100);

                /*
                 | Example:
                 | matric
                 | intermediate
                 | admission_test
                 | interview
                 | hafiz_quran
                 */
                $table->string('source_key', 120)->nullable();

                /*
                 | percentage_of_marks, obtained_marks, normalized_marks,
                 | fixed_marks, best_of_tests, latest_test
                 */
                $table->string('calculation_method_code', 100)->default('percentage_of_marks');

                /*
                 | Weight in final formula.
                 | Example: Intermediate 40, Test 50, Matric 10.
                 */
                $table->decimal('weight', 8, 2)->default(0);

                /*
                 | Raw max marks from source.
                 | Example: Intermediate total marks 1100.
                 */
                $table->decimal('max_raw_marks', 10, 2)->nullable();

                /*
                 | Normalize source score to this number before applying weight.
                 | Usually 100.
                 */
                $table->decimal('normalize_to', 10, 2)->default(100);

                /*
                 | Minimum required score for this component.
                 */
                $table->decimal('minimum_required_score', 10, 2)->nullable();

                $table->boolean('is_required')->default(false);
                $table->boolean('include_in_total')->default(true);
                $table->boolean('allow_bonus')->default(false);
                $table->boolean('allow_negative')->default(false);

                /*
                 | For conditional rules:
                 | qualification_level = intermediate
                 | subject_group in pre_medical, pre_engineering, ics
                 | test_type = tenant_test / nts / gat
                 */
                $table->json('conditions_json')->nullable();

                /*
                 | For custom source mapping, examples:
                 | {"qualification_level_code":"intermediate","allowed_groups":["pre_engineering","ics"]}
                 | {"test_type_codes":["tenant_test","nts"]}
                 */
                $table->json('source_mapping_json')->nullable();

                $table->unsignedInteger('display_order')->default(1);
                $table->string('status_code', 80)->default('active');

                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->unique(
                    ['tenant_id', 'admission_merit_formula_id', 'code'],
                    'adm_merit_component_formula_code_unique'
                );

                $table->index(
                    ['tenant_id', 'admission_merit_formula_id'],
                    'adm_merit_component_formula_idx'
                );

                $table->foreign('tenant_id', 'adm_merit_component_tenant_fk')
                    ->references('id')
                    ->on('tenants')
                    ->cascadeOnDelete();

                $table->foreign('admission_merit_formula_id', 'adm_merit_component_formula_fk')
                    ->references('id')
                    ->on('admission_merit_formulas')
                    ->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('admission_merit_formula_applicabilities')) {
            Schema::create('admission_merit_formula_applicabilities', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('admission_merit_formula_id');

                /*
                 | session, preference_group, offered_program, quota
                 */
                $table->string('applicability_scope_code', 100);

                $table->unsignedBigInteger('admission_session_id')->nullable();
                $table->unsignedBigInteger('admission_preference_group_id')->nullable();
                $table->unsignedBigInteger('offered_program_id')->nullable();
                $table->unsignedBigInteger('program_quota_seat_id')->nullable();

                $table->date('effective_from')->nullable();
                $table->date('effective_to')->nullable();

                $table->boolean('is_default')->default(false);
                $table->unsignedInteger('priority')->default(100);

                $table->string('status_code', 80)->default('active');

                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->index(
                    ['tenant_id', 'admission_merit_formula_id'],
                    'adm_merit_app_formula_idx'
                );

                $table->index(
                    ['tenant_id', 'applicability_scope_code'],
                    'adm_merit_app_scope_idx'
                );

                $table->foreign('tenant_id', 'adm_merit_app_tenant_fk')
                    ->references('id')
                    ->on('tenants')
                    ->cascadeOnDelete();

                $table->foreign('admission_merit_formula_id', 'adm_merit_app_formula_fk')
                    ->references('id')
                    ->on('admission_merit_formulas')
                    ->cascadeOnDelete();

                $table->foreign('admission_session_id', 'adm_merit_app_session_fk')
                    ->references('id')
                    ->on('admission_sessions')
                    ->nullOnDelete();

                $table->foreign('admission_preference_group_id', 'adm_merit_app_pref_group_fk')
                    ->references('id')
                    ->on('admission_preference_groups')
                    ->nullOnDelete();

                $table->foreign('offered_program_id', 'adm_merit_app_offered_program_fk')
                    ->references('id')
                    ->on('offered_programs')
                    ->nullOnDelete();

                $table->foreign('program_quota_seat_id', 'adm_merit_app_quota_fk')
                    ->references('id')
                    ->on('program_quota_seats')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_merit_formula_applicabilities');
        Schema::dropIfExists('admission_merit_formula_components');
        Schema::dropIfExists('admission_merit_formulas');
    }
};