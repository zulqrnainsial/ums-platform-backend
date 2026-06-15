<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('admission_applicant_merit_scores')
            && !Schema::hasColumn('admission_applicant_merit_scores', 'deleted_at')) {
            Schema::table('admission_applicant_merit_scores', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (Schema::hasTable('admission_applicant_merit_score_components')
            && !Schema::hasColumn('admission_applicant_merit_score_components', 'deleted_at')) {
            Schema::table('admission_applicant_merit_score_components', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('admission_applicant_merit_scores')
            && Schema::hasColumn('admission_applicant_merit_scores', 'deleted_at')) {
            Schema::table('admission_applicant_merit_scores', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasTable('admission_applicant_merit_score_components')
            && Schema::hasColumn('admission_applicant_merit_score_components', 'deleted_at')) {
            Schema::table('admission_applicant_merit_score_components', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};