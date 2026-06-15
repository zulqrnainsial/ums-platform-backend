<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('admission_merit_lists')) {
            Schema::create('admission_merit_lists', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('tenant_id');

                $table->unsignedBigInteger('admission_session_id')->nullable();
                $table->unsignedBigInteger('admission_preference_group_id')->nullable();
                $table->unsignedBigInteger('offered_program_id')->nullable();
                $table->unsignedBigInteger('program_quota_seat_id')->nullable();
                $table->unsignedBigInteger('admission_merit_formula_id')->nullable();

                $table->string('list_no', 80);
                $table->string('title', 200)->nullable();

                /*
                 | draft, generated, published, cancelled, locked
                 */
                $table->string('status_code', 80)->default('draft');

                /*
                 | merit, waiting, quota, self_finance, reserved
                 */
                $table->string('list_type_code', 80)->default('merit');

                $table->unsignedInteger('total_candidates')->default(0);
                $table->unsignedInteger('selected_candidates')->default(0);
                $table->unsignedInteger('waiting_candidates')->default(0);
                $table->unsignedInteger('available_seats')->default(0);

                $table->decimal('highest_merit_score', 12, 4)->nullable();
                $table->decimal('lowest_merit_score', 12, 4)->nullable();

                $table->json('generation_filters_json')->nullable();
                $table->json('generation_summary_json')->nullable();

                $table->dateTime('generated_at')->nullable();
                $table->unsignedBigInteger('generated_by')->nullable();

                $table->dateTime('published_at')->nullable();
                $table->unsignedBigInteger('published_by')->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->unique(
                    [
                        'tenant_id',
                        'admission_session_id',
                        'admission_preference_group_id',
                        'offered_program_id',
                        'program_quota_seat_id',
                        'list_no',
                    ],
                    'adm_merit_list_unique'
                );

                $table->index(['tenant_id', 'admission_session_id'], 'adm_merit_list_session_idx');
                $table->index(['tenant_id', 'offered_program_id'], 'adm_merit_list_program_idx');
                $table->index(['tenant_id', 'status_code'], 'adm_merit_list_status_idx');

                $table->foreign('tenant_id', 'adm_merit_list_tenant_fk')
                    ->references('id')
                    ->on('tenants')
                    ->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('admission_merit_list_applicants')) {
            Schema::create('admission_merit_list_applicants', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('admission_merit_list_id');

                $table->unsignedBigInteger('applicant_id');
                $table->unsignedBigInteger('admission_application_id')->nullable();
                $table->unsignedBigInteger('admission_applicant_merit_score_id')->nullable();

                $table->unsignedBigInteger('admission_session_id')->nullable();
                $table->unsignedBigInteger('admission_preference_group_id')->nullable();
                $table->unsignedBigInteger('offered_program_id')->nullable();
                $table->unsignedBigInteger('program_quota_seat_id')->nullable();

                $table->unsignedInteger('merit_position');
                $table->unsignedInteger('preference_order')->nullable();

                $table->decimal('final_merit_score', 12, 4)->default(0);
                $table->boolean('is_eligible_for_merit')->default(true);

                /*
                 | selected, waiting, not_selected, cancelled
                 */
                $table->string('selection_status_code', 80)->default('waiting');

                /*
                 | pending, offered, accepted, rejected, expired, cancelled
                 */
                $table->string('offer_status_code', 80)->default('pending');

                $table->dateTime('offer_generated_at')->nullable();
                $table->dateTime('offer_expiry_at')->nullable();

                $table->json('score_snapshot_json')->nullable();
                $table->json('selection_reason_json')->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->unique(
                    [
                        'tenant_id',
                        'admission_merit_list_id',
                        'applicant_id',
                        'offered_program_id',
                        'program_quota_seat_id',
                    ],
                    'adm_merit_list_app_unique'
                );

                $table->index(['tenant_id', 'admission_merit_list_id'], 'adm_merit_list_app_list_idx');
                $table->index(['tenant_id', 'applicant_id'], 'adm_merit_list_app_applicant_idx');
                $table->index(['tenant_id', 'selection_status_code'], 'adm_merit_list_app_selection_idx');

                $table->foreign('tenant_id', 'adm_merit_list_app_tenant_fk')
                    ->references('id')
                    ->on('tenants')
                    ->cascadeOnDelete();

                $table->foreign('admission_merit_list_id', 'adm_merit_list_app_list_fk')
                    ->references('id')
                    ->on('admission_merit_lists')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_merit_list_applicants');
        Schema::dropIfExists('admission_merit_lists');
    }
};