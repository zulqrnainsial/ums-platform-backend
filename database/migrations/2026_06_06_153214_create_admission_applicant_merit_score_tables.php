<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('admission_applicant_merit_scores')) {
            Schema::create('admission_applicant_merit_scores', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('applicant_id');
                $table->unsignedBigInteger('admission_application_id')->nullable();
                $table->unsignedBigInteger('admission_session_id')->nullable();
                $table->unsignedBigInteger('offered_program_id')->nullable();
                $table->unsignedBigInteger('admission_preference_group_id')->nullable();
                $table->unsignedBigInteger('program_quota_seat_id')->nullable();

                $table->unsignedBigInteger('admission_merit_formula_id');

                $table->decimal('total_component_weight', 10, 2)->default(0);
                $table->decimal('total_weighted_score', 10, 4)->default(0);
                $table->decimal('bonus_score', 10, 4)->default(0);
                $table->decimal('deduction_score', 10, 4)->default(0);
                $table->decimal('final_merit_score', 10, 4)->default(0);

                $table->boolean('is_eligible_for_merit')->default(true);
                $table->json('failed_required_components_json')->nullable();
                $table->json('calculation_snapshot_json')->nullable();

                /*
                 | calculated, recalculated, locked, cancelled
                 */
                $table->string('status_code', 80)->default('calculated');

                $table->dateTime('calculated_at')->nullable();
                $table->unsignedBigInteger('calculated_by')->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->unique(
                    [
                        'tenant_id',
                        'applicant_id',
                        'admission_application_id',
                        'offered_program_id',
                        'program_quota_seat_id',
                        'admission_merit_formula_id',
                    ],
                    'adm_merit_score_unique'
                );

                $table->index(['tenant_id', 'admission_session_id'], 'adm_merit_score_session_idx');
                $table->index(['tenant_id', 'offered_program_id'], 'adm_merit_score_program_idx');
                $table->index(['tenant_id', 'final_merit_score'], 'adm_merit_score_final_idx');

                $table->foreign('tenant_id', 'adm_merit_score_tenant_fk')
                    ->references('id')
                    ->on('tenants')
                    ->cascadeOnDelete();

                $table->foreign('admission_merit_formula_id', 'adm_merit_score_formula_fk')
                    ->references('id')
                    ->on('admission_merit_formulas')
                    ->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('admission_applicant_merit_score_components')) {
            Schema::create('admission_applicant_merit_score_components', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('admission_applicant_merit_score_id');
                $table->unsignedBigInteger('admission_merit_formula_component_id');

                $table->string('component_code', 80);
                $table->string('component_title', 200);

                /*
                 | Stored as string lookup codes.
                 */
                $table->string('component_type_code', 80);
                $table->string('source_type_code', 100);
                $table->string('source_key', 120)->nullable();
                $table->string('calculation_method_code', 100);

                $table->decimal('raw_obtained_marks', 12, 4)->nullable();
                $table->decimal('raw_total_marks', 12, 4)->nullable();
                $table->decimal('raw_percentage', 10, 4)->nullable();

                $table->decimal('normalized_score', 10, 4)->default(0);
                $table->decimal('component_weight', 10, 4)->default(0);
                $table->decimal('weighted_score', 10, 4)->default(0);

                $table->boolean('is_required')->default(false);
                $table->boolean('is_component_passed')->default(true);
                $table->boolean('include_in_total')->default(true);

                $table->json('source_record_json')->nullable();
                $table->json('calculation_detail_json')->nullable();

                $table->string('status_code', 80)->default('calculated');

                $table->timestamps();
                $table->softDeletes();

                $table->index(
                    ['tenant_id', 'admission_applicant_merit_score_id'],
                    'adm_merit_score_component_score_idx'
                );

                $table->foreign('tenant_id', 'adm_merit_score_component_tenant_fk')
                    ->references('id')
                    ->on('tenants')
                    ->cascadeOnDelete();

                $table->foreign('admission_applicant_merit_score_id', 'adm_merit_score_component_score_fk')
                    ->references('id')
                    ->on('admission_applicant_merit_scores')
                    ->cascadeOnDelete();

                $table->foreign('admission_merit_formula_component_id', 'adm_merit_score_component_formula_component_fk')
                    ->references('id')
                    ->on('admission_merit_formula_components')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_applicant_merit_score_components');
        Schema::dropIfExists('admission_applicant_merit_scores');
    }
};