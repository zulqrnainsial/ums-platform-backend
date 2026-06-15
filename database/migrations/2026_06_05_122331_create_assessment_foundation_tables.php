<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('assessment_categories')) {
            Schema::create('assessment_categories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->string('code', 80);
                $table->string('name', 150);
                $table->text('description')->nullable();
                $table->string('status_code', 50)->default('active');
                $table->unsignedInteger('display_order')->default(0);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['tenant_id', 'code'], 'as_cat_tenant_code_uq');
                $table->index(['tenant_id'], 'as_cat_tenant_idx');
                $table->index(['status_code'], 'as_cat_status_idx');
            });
        }

        if (!Schema::hasTable('assessment_subjects')) {
            Schema::create('assessment_subjects', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->string('code', 80);
                $table->string('name', 150);
                $table->text('description')->nullable();
                $table->string('status_code', 50)->default('active');
                $table->unsignedInteger('display_order')->default(0);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['tenant_id', 'code'], 'as_sub_tenant_code_uq');
                $table->index(['tenant_id'], 'as_sub_tenant_idx');
                $table->index(['status_code'], 'as_sub_status_idx');
            });
        }

        if (!Schema::hasTable('assessment_topics')) {
            Schema::create('assessment_topics', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('assessment_subject_id');
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->string('code', 80);
                $table->string('name', 150);
                $table->text('description')->nullable();
                $table->string('status_code', 50)->default('active');
                $table->unsignedInteger('display_order')->default(0);
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['tenant_id', 'assessment_subject_id', 'code'], 'as_topic_sub_code_uq');
                $table->index(['tenant_id'], 'as_topic_tenant_idx');
                $table->index(['assessment_subject_id'], 'as_topic_sub_idx');
                $table->index(['parent_id'], 'as_topic_parent_idx');
            });
        }

        if (!Schema::hasTable('question_banks')) {
            Schema::create('question_banks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('assessment_category_id')->nullable();
                $table->unsignedBigInteger('assessment_subject_id')->nullable();
                $table->string('code', 80);
                $table->string('name', 180);
                $table->text('description')->nullable();
                $table->string('status_code', 50)->default('active');
                $table->unsignedInteger('display_order')->default(0);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['tenant_id', 'code'], 'qb_tenant_code_uq');
                $table->index(['tenant_id'], 'qb_tenant_idx');
                $table->index(['assessment_category_id'], 'qb_cat_idx');
                $table->index(['assessment_subject_id'], 'qb_sub_idx');
                $table->index(['status_code'], 'qb_status_idx');
            });
        }

        if (!Schema::hasTable('questions')) {
            Schema::create('questions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('question_bank_id');
                $table->unsignedBigInteger('assessment_subject_id')->nullable();
                $table->unsignedBigInteger('assessment_topic_id')->nullable();

                $table->string('question_type_code', 80);
                $table->string('difficulty_code', 80)->nullable();
                $table->string('cognitive_level_code', 80)->nullable();

                $table->text('question_text');
                $table->longText('question_html')->nullable();

                $table->string('question_image_path')->nullable();
                $table->string('question_audio_path')->nullable();
                $table->string('question_video_path')->nullable();

                $table->decimal('default_marks', 10, 2)->default(1);
                $table->decimal('default_negative_marks', 10, 2)->default(0);
                $table->unsignedInteger('default_time_seconds')->nullable();

                $table->text('explanation')->nullable();
                $table->longText('explanation_html')->nullable();

                $table->string('approval_status_code', 50)->default('draft');
                $table->boolean('is_active')->default(true);

                $table->string('source_code', 80)->nullable();
                $table->string('import_batch_no', 100)->nullable();
                $table->string('external_ref_no', 100)->nullable();

                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('reviewed_by')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id'], 'q_tenant_idx');
                $table->index(['question_bank_id'], 'q_bank_idx');
                $table->index(['assessment_subject_id'], 'q_sub_idx');
                $table->index(['assessment_topic_id'], 'q_topic_idx');
                $table->index(['question_type_code'], 'q_type_idx');
                $table->index(['difficulty_code'], 'q_diff_idx');
                $table->index(['cognitive_level_code'], 'q_cog_idx');
                $table->index(['approval_status_code'], 'q_approval_idx');
                $table->index(['import_batch_no'], 'q_import_idx');
            });
        }

        if (!Schema::hasTable('question_options')) {
            Schema::create('question_options', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('question_id');

                $table->string('option_key', 20)->nullable();
                $table->text('option_text')->nullable();
                $table->longText('option_html')->nullable();
                $table->string('option_image_path')->nullable();

                $table->boolean('is_correct')->default(false);
                $table->unsignedInteger('correct_order')->nullable();
                $table->string('match_key', 80)->nullable();
                $table->string('correct_match_key', 80)->nullable();

                $table->decimal('marks_percentage', 8, 2)->nullable();
                $table->unsignedInteger('display_order')->default(0);

                $table->string('source_code', 80)->nullable();
                $table->string('import_batch_no', 100)->nullable();
                $table->string('external_ref_no', 100)->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id'], 'qopt_tenant_idx');
                $table->index(['question_id'], 'qopt_question_idx');
                $table->index(['is_correct'], 'qopt_correct_idx');
                $table->index(['import_batch_no'], 'qopt_import_idx');
            });
        }

        if (!Schema::hasTable('question_answer_keys')) {
            Schema::create('question_answer_keys', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('question_id');

                $table->text('answer_text')->nullable();
                $table->decimal('answer_number', 18, 6)->nullable();
                $table->json('accepted_variants_json')->nullable();

                $table->boolean('case_sensitive')->default(false);
                $table->decimal('numeric_tolerance', 18, 6)->nullable();
                $table->decimal('marks_percentage', 8, 2)->nullable();

                $table->string('status_code', 50)->default('active');

                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id'], 'qak_tenant_idx');
                $table->index(['question_id'], 'qak_question_idx');
                $table->index(['status_code'], 'qak_status_idx');
            });
        }

        if (!Schema::hasTable('assessments')) {
            Schema::create('assessments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');

                $table->unsignedBigInteger('assessment_category_id')->nullable();
                $table->unsignedBigInteger('assessment_type_id')->nullable();
                $table->unsignedBigInteger('admission_session_id')->nullable();

                $table->string('purpose_code', 80);
                $table->string('mode_code', 80);

                $table->string('code', 100);
                $table->string('title', 180);
                $table->text('description')->nullable();
                $table->longText('instructions_html')->nullable();

                $table->decimal('total_marks', 10, 2)->default(0);
                $table->decimal('passing_marks', 10, 2)->nullable();
                $table->unsignedInteger('duration_minutes')->nullable();

                $table->boolean('allow_negative_marking')->default(false);
                $table->string('negative_marking_type_code', 80)->nullable();

                $table->unsignedInteger('attempt_limit')->default(1);
                $table->boolean('shuffle_questions')->default(false);
                $table->boolean('shuffle_options')->default(false);
                $table->boolean('show_result_immediately')->default(false);
                $table->boolean('show_correct_answers')->default(false);
                $table->boolean('allow_review_before_submit')->default(true);

                $table->string('status_code', 50)->default('draft');

                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->unique(['tenant_id', 'code'], 'assess_tenant_code_uq');
                $table->index(['tenant_id'], 'assess_tenant_idx');
                $table->index(['purpose_code'], 'assess_purpose_idx');
                $table->index(['mode_code'], 'assess_mode_idx');
                $table->index(['admission_session_id'], 'assess_adm_session_idx');
                $table->index(['status_code'], 'assess_status_idx');
            });
        }

        if (!Schema::hasTable('assessment_sections')) {
            Schema::create('assessment_sections', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('assessment_id');
                $table->unsignedBigInteger('assessment_subject_id')->nullable();

                $table->string('section_code', 80);
                $table->string('section_title', 150);
                $table->text('instructions')->nullable();

                $table->unsignedInteger('total_questions')->nullable();
                $table->decimal('total_marks', 10, 2)->default(0);
                $table->decimal('passing_marks', 10, 2)->nullable();
                $table->unsignedInteger('duration_minutes')->nullable();

                $table->string('question_selection_mode_code', 80)->default('manual');
                $table->boolean('shuffle_questions')->default(false);
                $table->unsignedInteger('display_order')->default(0);
                $table->string('status_code', 50)->default('active');

                $table->timestamps();
                $table->softDeletes();

                $table->unique(['tenant_id', 'assessment_id', 'section_code'], 'as_sec_assess_code_uq');
                $table->index(['tenant_id'], 'as_sec_tenant_idx');
                $table->index(['assessment_id'], 'as_sec_assess_idx');
                $table->index(['assessment_subject_id'], 'as_sec_sub_idx');
            });
        }

        if (!Schema::hasTable('assessment_questions')) {
            Schema::create('assessment_questions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('assessment_id');
                $table->unsignedBigInteger('assessment_section_id');
                $table->unsignedBigInteger('question_id');

                $table->decimal('marks', 10, 2)->default(1);
                $table->decimal('negative_marks', 10, 2)->default(0);
                $table->unsignedInteger('time_seconds')->nullable();
                $table->unsignedInteger('display_order')->default(0);
                $table->boolean('is_mandatory')->default(true);
                $table->string('status_code', 50)->default('active');

                $table->timestamps();
                $table->softDeletes();

                $table->unique(['tenant_id', 'assessment_id', 'question_id'], 'as_q_assess_question_uq');
                $table->index(['tenant_id'], 'as_q_tenant_idx');
                $table->index(['assessment_id'], 'as_q_assess_idx');
                $table->index(['assessment_section_id'], 'as_q_section_idx');
                $table->index(['question_id'], 'as_q_question_idx');
            });
        }

        if (!Schema::hasTable('assessment_schedules')) {
            Schema::create('assessment_schedules', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('assessment_id');

                $table->string('schedule_code', 100);
                $table->string('title', 180);
                $table->dateTime('start_at');
                $table->dateTime('end_at');
                $table->dateTime('reporting_time')->nullable();

                $table->string('timezone', 80)->default('Asia/Karachi');
                $table->string('mode_code', 80);

                $table->string('venue_name')->nullable();
                $table->unsignedBigInteger('campus_id')->nullable();
                $table->unsignedBigInteger('building_id')->nullable();
                $table->unsignedBigInteger('room_id')->nullable();

                $table->unsignedInteger('max_candidates')->nullable();
                $table->string('status_code', 50)->default('scheduled');
                $table->text('instructions')->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->unique(['tenant_id', 'schedule_code'], 'as_sch_tenant_code_uq');
                $table->index(['tenant_id'], 'as_sch_tenant_idx');
                $table->index(['assessment_id'], 'as_sch_assess_idx');
                $table->index(['start_at', 'end_at'], 'as_sch_dates_idx');
                $table->index(['status_code'], 'as_sch_status_idx');
            });
        }

        if (!Schema::hasTable('assessment_participants')) {
            Schema::create('assessment_participants', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('assessment_id');
                $table->unsignedBigInteger('assessment_schedule_id')->nullable();

                $table->string('participant_type_code', 80);
                $table->unsignedBigInteger('participant_id');

                $table->unsignedBigInteger('applicant_id')->nullable();
                $table->unsignedBigInteger('student_id')->nullable();
                $table->unsignedBigInteger('employee_id')->nullable();
                $table->unsignedBigInteger('hr_candidate_id')->nullable();

                $table->string('roll_no', 100)->nullable();
                $table->string('seat_no', 100)->nullable();

                $table->string('attendance_status_code', 50)->default('pending');
                $table->string('attempt_status_code', 50)->default('not_started');
                $table->string('result_status_code', 50)->default('pending');

                $table->dateTime('assigned_at')->nullable();
                $table->dateTime('started_at')->nullable();
                $table->dateTime('submitted_at')->nullable();
                $table->dateTime('evaluated_at')->nullable();

                $table->decimal('obtained_marks', 10, 2)->nullable();
                $table->decimal('percentage', 8, 2)->nullable();
                $table->string('grade_code', 50)->nullable();
                $table->text('remarks')->nullable();

                $table->string('import_batch_no', 100)->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->unique(['tenant_id', 'assessment_id', 'participant_type_code', 'participant_id'], 'as_part_unique');
                $table->index(['tenant_id'], 'as_part_tenant_idx');
                $table->index(['assessment_id'], 'as_part_assess_idx');
                $table->index(['assessment_schedule_id'], 'as_part_sch_idx');
                $table->index(['participant_type_code', 'participant_id'], 'as_part_type_id_idx');
                $table->index(['applicant_id'], 'as_part_applicant_idx');
                $table->index(['roll_no'], 'as_part_roll_idx');
                $table->index(['import_batch_no'], 'as_part_import_idx');
            });
        }

        if (!Schema::hasTable('assessment_attempts')) {
            Schema::create('assessment_attempts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('assessment_participant_id');
                $table->unsignedInteger('attempt_no')->default(1);

                $table->dateTime('started_at')->nullable();
                $table->dateTime('submitted_at')->nullable();
                $table->dateTime('auto_submitted_at')->nullable();
                $table->unsignedInteger('duration_seconds')->nullable();

                $table->string('ip_address', 80)->nullable();
                $table->text('user_agent')->nullable();

                $table->unsignedInteger('tab_switch_count')->default(0);
                $table->unsignedInteger('warning_count')->default(0);

                $table->string('status_code', 50)->default('in_progress');

                $table->decimal('obtained_marks', 10, 2)->default(0);
                $table->decimal('negative_marks', 10, 2)->default(0);
                $table->decimal('final_marks', 10, 2)->default(0);
                $table->decimal('percentage', 8, 2)->default(0);

                $table->timestamps();
                $table->softDeletes();

                $table->unique(['tenant_id', 'assessment_participant_id', 'attempt_no'], 'as_attempt_unique');
                $table->index(['tenant_id'], 'as_attempt_tenant_idx');
                $table->index(['assessment_participant_id'], 'as_attempt_part_idx');
                $table->index(['status_code'], 'as_attempt_status_idx');
            });
        }

        if (!Schema::hasTable('assessment_attempt_answers')) {
            Schema::create('assessment_attempt_answers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('assessment_attempt_id');
                $table->unsignedBigInteger('assessment_question_id');
                $table->unsignedBigInteger('question_id');

                $table->json('selected_option_ids_json')->nullable();
                $table->text('answer_text')->nullable();
                $table->decimal('answer_number', 18, 6)->nullable();
                $table->string('uploaded_file_path')->nullable();

                $table->boolean('is_correct')->nullable();
                $table->decimal('marks_awarded', 10, 2)->default(0);
                $table->decimal('negative_marks_applied', 10, 2)->default(0);
                $table->decimal('manual_marks', 10, 2)->nullable();

                $table->dateTime('answered_at')->nullable();
                $table->unsignedInteger('time_spent_seconds')->nullable();

                $table->unsignedBigInteger('marked_by')->nullable();
                $table->dateTime('marked_at')->nullable();
                $table->text('marking_remarks')->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->unique(['tenant_id', 'assessment_attempt_id', 'assessment_question_id'], 'as_ans_unique');
                $table->index(['tenant_id'], 'as_ans_tenant_idx');
                $table->index(['assessment_attempt_id'], 'as_ans_attempt_idx');
                $table->index(['question_id'], 'as_ans_question_idx');
                $table->index(['is_correct'], 'as_ans_correct_idx');
            });
        }

        if (!Schema::hasTable('assessment_results')) {
            Schema::create('assessment_results', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('assessment_id');
                $table->unsignedBigInteger('assessment_participant_id');
                $table->unsignedBigInteger('assessment_attempt_id')->nullable();

                $table->decimal('total_marks', 10, 2)->default(0);
                $table->decimal('obtained_marks', 10, 2)->default(0);
                $table->decimal('negative_marks', 10, 2)->default(0);
                $table->decimal('final_marks', 10, 2)->default(0);
                $table->decimal('percentage', 8, 2)->default(0);
                $table->decimal('passing_marks', 10, 2)->nullable();
                $table->boolean('is_passed')->default(false);

                $table->unsignedInteger('rank')->nullable();
                $table->decimal('percentile', 8, 2)->nullable();
                $table->string('grade_code', 50)->nullable();

                $table->json('strengths_json')->nullable();
                $table->json('weaknesses_json')->nullable();
                $table->json('analysis_json')->nullable();

                $table->string('result_status_code', 50)->default('generated');

                $table->dateTime('generated_at')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->dateTime('approved_at')->nullable();
                $table->dateTime('published_at')->nullable();

                $table->text('remarks')->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->unique(['tenant_id', 'assessment_participant_id', 'assessment_attempt_id'], 'as_result_unique');
                $table->index(['tenant_id'], 'as_result_tenant_idx');
                $table->index(['assessment_id'], 'as_result_assess_idx');
                $table->index(['assessment_participant_id'], 'as_result_part_idx');
                $table->index(['result_status_code'], 'as_result_status_idx');
                $table->index(['rank'], 'as_result_rank_idx');
            });
        }

        if (!Schema::hasTable('assessment_section_results')) {
            Schema::create('assessment_section_results', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('assessment_result_id');
                $table->unsignedBigInteger('assessment_section_id');
                $table->unsignedBigInteger('assessment_subject_id')->nullable();

                $table->decimal('total_marks', 10, 2)->default(0);
                $table->decimal('obtained_marks', 10, 2)->default(0);
                $table->decimal('negative_marks', 10, 2)->default(0);
                $table->decimal('final_marks', 10, 2)->default(0);
                $table->decimal('percentage', 8, 2)->default(0);
                $table->boolean('is_passed')->default(false);

                $table->json('topic_analysis_json')->nullable();
                $table->json('difficulty_analysis_json')->nullable();
                $table->json('question_type_analysis_json')->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id'], 'as_sec_res_tenant_idx');
                $table->index(['assessment_result_id'], 'as_sec_res_result_idx');
                $table->index(['assessment_section_id'], 'as_sec_res_section_idx');
                $table->index(['assessment_subject_id'], 'as_sec_res_subject_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_section_results');
        Schema::dropIfExists('assessment_results');
        Schema::dropIfExists('assessment_attempt_answers');
        Schema::dropIfExists('assessment_attempts');
        Schema::dropIfExists('assessment_participants');
        Schema::dropIfExists('assessment_schedules');
        Schema::dropIfExists('assessment_questions');
        Schema::dropIfExists('assessment_sections');
        Schema::dropIfExists('assessments');
        Schema::dropIfExists('question_answer_keys');
        Schema::dropIfExists('question_options');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('question_banks');
        Schema::dropIfExists('assessment_topics');
        Schema::dropIfExists('assessment_subjects');
        Schema::dropIfExists('assessment_categories');
    }
};