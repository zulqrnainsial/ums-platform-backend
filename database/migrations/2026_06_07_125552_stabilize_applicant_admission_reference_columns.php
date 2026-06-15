<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('applicant_qualifications')) {
            Schema::table('applicant_qualifications', function (Blueprint $table) {
                if (!Schema::hasColumn('applicant_qualifications', 'qualification_level_id')) {
                    $table->unsignedBigInteger('qualification_level_id')->nullable()->after('applicant_id');
                }

                if (!Schema::hasColumn('applicant_qualifications', 'subject_group_id')) {
                    $table->unsignedBigInteger('subject_group_id')->nullable()->after('qualification_level_id');
                }
            });
        }

        if (Schema::hasTable('applicant_documents')) {
            Schema::table('applicant_documents', function (Blueprint $table) {
                if (!Schema::hasColumn('applicant_documents', 'document_type_id')) {
                    $table->unsignedBigInteger('document_type_id')->nullable()->after('applicant_id');
                }
            });
        }

        if (Schema::hasTable('applicant_test_results')) {
            Schema::table('applicant_test_results', function (Blueprint $table) {
                if (!Schema::hasColumn('applicant_test_results', 'assessment_id')) {
                    $table->unsignedBigInteger('assessment_id')->nullable()->after('applicant_id');
                }

                if (!Schema::hasColumn('applicant_test_results', 'assessment_schedule_id')) {
                    $table->unsignedBigInteger('assessment_schedule_id')->nullable()->after('assessment_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('applicant_test_results')) {
            Schema::table('applicant_test_results', function (Blueprint $table) {
                if (Schema::hasColumn('applicant_test_results', 'assessment_schedule_id')) {
                    $table->dropColumn('assessment_schedule_id');
                }

                if (Schema::hasColumn('applicant_test_results', 'assessment_id')) {
                    $table->dropColumn('assessment_id');
                }
            });
        }

        if (Schema::hasTable('applicant_documents')) {
            Schema::table('applicant_documents', function (Blueprint $table) {
                if (Schema::hasColumn('applicant_documents', 'document_type_id')) {
                    $table->dropColumn('document_type_id');
                }
            });
        }

        if (Schema::hasTable('applicant_qualifications')) {
            Schema::table('applicant_qualifications', function (Blueprint $table) {
                if (Schema::hasColumn('applicant_qualifications', 'subject_group_id')) {
                    $table->dropColumn('subject_group_id');
                }

                if (Schema::hasColumn('applicant_qualifications', 'qualification_level_id')) {
                    $table->dropColumn('qualification_level_id');
                }
            });
        }
    }
};