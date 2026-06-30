<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('timetable_generation_runs')) {
            Schema::create('timetable_generation_runs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index('ttgr_tenant_idx');

                $table->unsignedBigInteger('academic_session_id')->index('ttgr_session_idx');
                $table->unsignedBigInteger('academic_term_id')->index('ttgr_term_idx');

                $table->unsignedBigInteger('timetable_calendar_period_id')
                    ->index('ttgr_period_idx');

                $table->unsignedBigInteger('timetable_slot_set_id')
                    ->index('ttgr_slot_set_idx');

                $table->string('status_code', 50)
                    ->default('draft')
                    ->index('ttgr_status_idx');

                $table->string('generation_scope_code', 50)
                    ->default('all_offerings')
                    ->index('ttgr_scope_idx');

                $table->json('generation_filters')->nullable();

                $table->unsignedInteger('total_offerings')->default(0);
                $table->unsignedInteger('scheduled_offerings')->default(0);
                $table->unsignedInteger('unscheduled_offerings')->default(0);
                $table->unsignedInteger('generated_entries')->default(0);
                $table->unsignedInteger('conflict_count')->default(0);

                $table->text('summary_note')->nullable();

                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();

                $table->unsignedBigInteger('created_by')->nullable()->index('ttgr_created_by_idx');
                $table->unsignedBigInteger('updated_by')->nullable()->index('ttgr_updated_by_idx');

                $table->timestamps();
                $table->softDeletes();

                $table->index(
                    ['tenant_id', 'academic_session_id', 'academic_term_id', 'status_code'],
                    'ttgr_scope_status_idx'
                );
            });
        }

        if (!Schema::hasTable('timetable_generation_run_items')) {
            Schema::create('timetable_generation_run_items', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('tenant_id')->index('ttgri_tenant_idx');

                $table->unsignedBigInteger('timetable_generation_run_id')
                    ->index('ttgri_run_idx');

                $table->unsignedBigInteger('course_offering_id')
                    ->index('ttgri_offering_idx');

                $table->unsignedBigInteger('course_teacher_allocation_id')
                    ->nullable()
                    ->index('ttgri_teacher_alloc_idx');

                $table->unsignedBigInteger('faculty_member_id')
                    ->nullable()
                    ->index('ttgri_faculty_idx');

                $table->unsignedBigInteger('section_id')
                    ->nullable()
                    ->index('ttgri_section_idx');

                $table->unsignedBigInteger('academic_teaching_group_id')
                    ->nullable()
                    ->index('ttgri_group_idx');

                $table->string('status_code', 50)
                    ->default('pending')
                    ->index('ttgri_status_idx');

                $table->unsignedInteger('required_minutes')->default(0);
                $table->unsignedInteger('scheduled_minutes')->default(0);

                $table->unsignedInteger('required_capacity')->nullable();
                $table->string('required_room_type_code', 100)->nullable();

                $table->unsignedBigInteger('generated_timetable_entry_id')
                    ->nullable()
                    ->index('ttgri_entry_idx');

                $table->string('failure_code', 150)
                    ->nullable()
                    ->index('ttgri_failure_idx');

                $table->string('failure_message', 1000)->nullable();
                $table->json('diagnostic_context')->nullable();

                $table->unsignedInteger('priority_score')
                    ->default(0)
                    ->index('ttgri_priority_idx');

                $table->unsignedBigInteger('created_by')->nullable()->index('ttgri_created_by_idx');
                $table->unsignedBigInteger('updated_by')->nullable()->index('ttgri_updated_by_idx');

                $table->timestamps();

                $table->unique(
                    [
                        'timetable_generation_run_id',
                        'course_offering_id',
                        'course_teacher_allocation_id',
                    ],
                    'ttgri_run_offer_alloc_uq'
                );

                $table->index(
                    ['tenant_id', 'timetable_generation_run_id', 'status_code'],
                    'ttgri_run_status_idx'
                );
            });
        }

        if (!Schema::hasTable('timetable_generation_diagnostics')) {
            Schema::create('timetable_generation_diagnostics', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('tenant_id')->index('ttgd_tenant_idx');

                $table->unsignedBigInteger('timetable_generation_run_id')
                    ->index('ttgd_run_idx');

                $table->unsignedBigInteger('timetable_generation_run_item_id')
                    ->nullable()
                    ->index('ttgd_item_idx');

                $table->unsignedBigInteger('course_offering_id')
                    ->nullable()
                    ->index('ttgd_offering_idx');

                $table->string('severity_code', 50)
                    ->default('error')
                    ->index('ttgd_severity_idx');

                $table->string('diagnostic_code', 150)
                    ->index('ttgd_code_idx');

                $table->string('diagnostic_message', 1000);
                $table->json('diagnostic_context')->nullable();

                $table->unsignedBigInteger('created_by')->nullable()->index('ttgd_created_by_idx');
                $table->unsignedBigInteger('updated_by')->nullable()->index('ttgd_updated_by_idx');

                $table->timestamps();

                $table->index(
                    ['tenant_id', 'timetable_generation_run_id', 'severity_code'],
                    'ttgd_run_severity_idx'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable_generation_diagnostics');
        Schema::dropIfExists('timetable_generation_run_items');
        Schema::dropIfExists('timetable_generation_runs');
    }
};