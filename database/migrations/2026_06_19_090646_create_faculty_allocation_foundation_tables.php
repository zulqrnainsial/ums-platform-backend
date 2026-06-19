<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('faculty_members')) {
            Schema::create('faculty_members', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('tenant_id')->index();

                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->unsignedBigInteger('department_id')->nullable()->index();
                $table->unsignedBigInteger('faculty_id')->nullable()->index();

                $table->string('employee_no', 100)->nullable()->index();
                $table->string('full_name', 255);
                $table->string('email', 255)->nullable()->index();
                $table->string('phone', 100)->nullable();

                $table->string('employment_type_code', 100)->default('permanent')->index();
                $table->string('designation_code', 100)->nullable()->index();

                $table->string('faculty_type_code', 100)->nullable()->index();
                $table->string('status_code', 50)->default('active')->index();

                $table->date('joining_date')->nullable();
                $table->date('leaving_date')->nullable();

                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('updated_by')->nullable()->index();

                $table->timestamps();
                $table->softDeletes();

                $table->unique(['tenant_id', 'employee_no'], 'fm_tenant_emp_unique');
            });
        }

        if (!Schema::hasTable('faculty_load_policies')) {
            Schema::create('faculty_load_policies', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('tenant_id')->index();

                $table->string('employment_type_code', 100)->nullable()->index();
                $table->string('designation_code', 100)->nullable()->index();
                $table->string('faculty_type_code', 100)->nullable()->index();

                $table->decimal('max_weekly_credit_hours', 8, 2)->nullable();
                $table->decimal('max_weekly_contact_hours', 8, 2)->nullable();
                $table->decimal('max_daily_contact_hours', 8, 2)->nullable();

                $table->unsignedInteger('max_consecutive_slots')->nullable();
                $table->boolean('allow_theory')->default(true);
                $table->boolean('allow_practical')->default(true);
                $table->boolean('allow_lab')->default(true);
                $table->boolean('allow_tutorial')->default(true);

                $table->string('status_code', 50)->default('active')->index();

                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('updated_by')->nullable()->index();

                $table->timestamps();

                $table->index(
                    ['tenant_id', 'employment_type_code', 'designation_code', 'faculty_type_code'],
                    'flp_scope_idx'
                );
            });
        }

        if (!Schema::hasTable('faculty_availability')) {
            Schema::create('faculty_availability', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('faculty_member_id')->index();

                $table->unsignedBigInteger('academic_session_id')->nullable()->index();
                $table->unsignedBigInteger('academic_term_id')->nullable()->index();

                $table->unsignedTinyInteger('day_of_week')->index(); // 1=Monday, 7=Sunday
                $table->time('start_time');
                $table->time('end_time');

                $table->string('availability_type', 50)->default('available')->index();
                // available, unavailable, preferred, restricted

                $table->string('reason', 255)->nullable();
                $table->string('status_code', 50)->default('active')->index();

                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('updated_by')->nullable()->index();

                $table->timestamps();

                $table->index(
                    ['tenant_id', 'faculty_member_id', 'academic_session_id', 'academic_term_id', 'day_of_week'],
                    'fa_scope_idx'
                );
            });
        }

        if (!Schema::hasTable('faculty_subject_expertise')) {
            Schema::create('faculty_subject_expertise', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('faculty_member_id')->index();

                $table->unsignedBigInteger('subject_id')->nullable()->index();
                $table->unsignedBigInteger('curriculum_subject_id')->nullable()->index();

                $table->string('subject_type_code', 100)->nullable()->index();
                // theory, practical, lab, tutorial

                $table->string('expertise_level_code', 100)->nullable()->index();
                // primary, secondary, assistant

                $table->boolean('can_teach')->default(true);
                $table->string('status_code', 50)->default('active')->index();

                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('updated_by')->nullable()->index();

                $table->timestamps();

                $table->index(
                    ['tenant_id', 'faculty_member_id', 'subject_id', 'curriculum_subject_id'],
                    'fse_scope_idx'
                );
            });
        }

        if (!Schema::hasTable('academic_teaching_groups')) {
            Schema::create('academic_teaching_groups', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('tenant_id')->index();

                $table->unsignedBigInteger('academic_session_id')->nullable()->index();
                $table->unsignedBigInteger('academic_term_id')->nullable()->index();
                $table->unsignedBigInteger('program_id')->nullable()->index();
                $table->unsignedBigInteger('student_batch_id')->nullable()->index();
                $table->unsignedBigInteger('section_id')->nullable()->index();

                $table->string('group_code', 100)->index();
                $table->string('group_name', 255);

                $table->string('group_type_code', 100)->index();
                // theory_section, practical_group, lab_group, tutorial_group

                $table->unsignedInteger('capacity')->nullable();
                $table->unsignedInteger('actual_strength')->default(0);

                $table->string('status_code', 50)->default('active')->index();

                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('updated_by')->nullable()->index();

                $table->timestamps();
                $table->softDeletes();

                $table->unique(
                    ['tenant_id', 'academic_session_id', 'academic_term_id', 'student_batch_id', 'group_code'],
                    'atg_scope_code_unique'
                );
            });
        }

        if (!Schema::hasTable('academic_teaching_group_members')) {
            Schema::create('academic_teaching_group_members', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('academic_teaching_group_id')->index();

                $table->unsignedBigInteger('student_id')->index();
                $table->unsignedBigInteger('student_enrollment_id')->nullable()->index();

                $table->string('status_code', 50)->default('active')->index();

                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('updated_by')->nullable()->index();

                $table->timestamps();

                $table->unique(
                    ['academic_teaching_group_id', 'student_id'],
                    'atgm_group_student_unique'
                );
            });
        }

        if (!Schema::hasTable('course_offerings')) {
            Schema::create('course_offerings', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('tenant_id')->index();

                $table->unsignedBigInteger('academic_session_id')->nullable()->index();
                $table->unsignedBigInteger('academic_term_id')->nullable()->index();
                $table->unsignedBigInteger('program_id')->nullable()->index();
                $table->unsignedBigInteger('student_batch_id')->nullable()->index();
                $table->unsignedBigInteger('section_id')->nullable()->index();
                $table->unsignedBigInteger('academic_teaching_group_id')->nullable()->index();

                $table->unsignedBigInteger('curriculum_subject_id')->nullable()->index();
                $table->unsignedBigInteger('subject_id')->nullable()->index();

                $table->string('course_code', 100)->nullable()->index();
                $table->string('course_title', 255)->nullable();

                $table->string('subject_type_code', 100)->default('theory')->index();
                // theory, practical, lab, tutorial

                $table->decimal('credit_hours', 8, 2)->default(0);
                $table->decimal('contact_hours_per_week', 8, 2)->default(0);

                $table->unsignedInteger('required_sessions_per_week')->nullable();
                $table->decimal('required_hours_per_session', 8, 2)->nullable();

                $table->string('required_room_type_code', 100)->nullable()->index();
                $table->unsignedInteger('required_capacity')->nullable();

                $table->boolean('requires_multimedia')->default(false);
                $table->boolean('requires_lab')->default(false);

                $table->string('status_code', 50)->default('planned')->index();
                // planned, offered, allocated, scheduled, cancelled

                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('updated_by')->nullable()->index();

                $table->timestamps();
                $table->softDeletes();

                $table->index(
                    [
                        'tenant_id',
                        'academic_session_id',
                        'academic_term_id',
                        'program_id',
                        'student_batch_id',
                        'section_id',
                        'academic_teaching_group_id',
                        'curriculum_subject_id',
                        'subject_type_code',
                    ],
                    'co_scope_idx'
                );
            });
        }

        if (!Schema::hasTable('course_teacher_allocations')) {
            Schema::create('course_teacher_allocations', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('tenant_id')->index();

                $table->unsignedBigInteger('course_offering_id')->index();
                $table->unsignedBigInteger('faculty_member_id')->index();

                $table->string('allocation_role_code', 100)->default('primary')->index();
                // primary, co_teacher, lab_instructor, teaching_assistant

                $table->decimal('allocated_credit_hours', 8, 2)->default(0);
                $table->decimal('allocated_contact_hours', 8, 2)->default(0);

                $table->string('allocation_status_code', 50)->default('draft')->index();
                // draft, valid, conflicted, approved, rejected, cancelled

                $table->text('remarks')->nullable();

                $table->unsignedBigInteger('approved_by')->nullable()->index();
                $table->timestamp('approved_at')->nullable();

                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('updated_by')->nullable()->index();

                $table->timestamps();
                $table->softDeletes();

                $table->unique(
                    ['course_offering_id', 'faculty_member_id', 'allocation_role_code'],
                    'cta_course_faculty_role_unique'
                );

                $table->index(
                    ['tenant_id', 'faculty_member_id', 'allocation_status_code'],
                    'cta_faculty_status_idx'
                );
            });
        }

        if (!Schema::hasTable('faculty_allocation_conflicts')) {
            Schema::create('faculty_allocation_conflicts', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('tenant_id')->index();

                $table->unsignedBigInteger('course_teacher_allocation_id')->nullable()->index();
                $table->unsignedBigInteger('course_offering_id')->nullable()->index();
                $table->unsignedBigInteger('faculty_member_id')->nullable()->index();

                $table->string('conflict_code', 100)->index();
                $table->string('conflict_severity', 50)->default('error')->index();
                // error, warning, info

                $table->string('conflict_message', 500);
                $table->json('conflict_context')->nullable();

                $table->string('status_code', 50)->default('open')->index();
                // open, ignored, resolved

                $table->unsignedBigInteger('resolved_by')->nullable()->index();
                $table->timestamp('resolved_at')->nullable();

                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('updated_by')->nullable()->index();

                $table->timestamps();

                $table->index(
                    ['tenant_id', 'faculty_member_id', 'conflict_code', 'status_code'],
                    'fac_conflict_scope_idx'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('faculty_allocation_conflicts');
        Schema::dropIfExists('course_teacher_allocations');
        Schema::dropIfExists('course_offerings');
        Schema::dropIfExists('academic_teaching_group_members');
        Schema::dropIfExists('academic_teaching_groups');
        Schema::dropIfExists('faculty_subject_expertise');
        Schema::dropIfExists('faculty_availability');
        Schema::dropIfExists('faculty_load_policies');
        Schema::dropIfExists('faculty_members');
    }
};