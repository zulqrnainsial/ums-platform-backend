<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('applicant_test_results')) {
            return;
        }

        Schema::table('applicant_test_results', function (Blueprint $table) {
            if (!Schema::hasColumn('applicant_test_results', 'assessment_id')) {
                $table->unsignedBigInteger('assessment_id')
                    ->nullable()
                    ->after('applicant_id');

                $table->index(['assessment_id'], 'atr_assessment_idx');
            }

            if (!Schema::hasColumn('applicant_test_results', 'assessment_participant_id')) {
                $table->unsignedBigInteger('assessment_participant_id')
                    ->nullable()
                    ->after('assessment_id');

                $table->index(['assessment_participant_id'], 'atr_participant_idx');
            }

            if (!Schema::hasColumn('applicant_test_results', 'assessment_attempt_id')) {
                $table->unsignedBigInteger('assessment_attempt_id')
                    ->nullable()
                    ->after('assessment_participant_id');

                $table->index(['assessment_attempt_id'], 'atr_attempt_idx');
            }

            if (!Schema::hasColumn('applicant_test_results', 'assessment_result_id')) {
                $table->unsignedBigInteger('assessment_result_id')
                    ->nullable()
                    ->after('assessment_attempt_id');

                $table->index(['assessment_result_id'], 'atr_result_idx');
            }

            if (!Schema::hasColumn('applicant_test_results', 'result_source_code')) {
                $table->string('result_source_code', 50)
                    ->default('manual')
                    ->after('assessment_result_id');

                $table->index(['result_source_code'], 'atr_source_idx');
            }

            if (!Schema::hasColumn('applicant_test_results', 'synced_at')) {
                $table->dateTime('synced_at')
                    ->nullable()
                    ->after('result_source_code');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('applicant_test_results')) {
            return;
        }

        Schema::table('applicant_test_results', function (Blueprint $table) {
            if (Schema::hasColumn('applicant_test_results', 'assessment_id')) {
                $table->dropIndex('atr_assessment_idx');
                $table->dropColumn('assessment_id');
            }

            if (Schema::hasColumn('applicant_test_results', 'assessment_participant_id')) {
                $table->dropIndex('atr_participant_idx');
                $table->dropColumn('assessment_participant_id');
            }

            if (Schema::hasColumn('applicant_test_results', 'assessment_attempt_id')) {
                $table->dropIndex('atr_attempt_idx');
                $table->dropColumn('assessment_attempt_id');
            }

            if (Schema::hasColumn('applicant_test_results', 'assessment_result_id')) {
                $table->dropIndex('atr_result_idx');
                $table->dropColumn('assessment_result_id');
            }

            if (Schema::hasColumn('applicant_test_results', 'result_source_code')) {
                $table->dropIndex('atr_source_idx');
                $table->dropColumn('result_source_code');
            }

            if (Schema::hasColumn('applicant_test_results', 'synced_at')) {
                $table->dropColumn('synced_at');
            }
        });
    }
};