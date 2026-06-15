<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('curriculum_subjects', function (Blueprint $table) {
            if (!Schema::hasColumn('curriculum_subjects', 'curriculum_subject_type')) {
                $table->enum('curriculum_subject_type', [
                    'regular',
                    'elective_placeholder',
                ])->default('regular')->after('subject_id');
            }

            if (!Schema::hasColumn('curriculum_subjects', 'elective_group_code')) {
                $table->string('elective_group_code')->nullable()->after('curriculum_subject_type');
            }

            if (!Schema::hasColumn('curriculum_subjects', 'elective_group_name')) {
                $table->string('elective_group_name')->nullable()->after('elective_group_code');
            }

            if (!Schema::hasColumn('curriculum_subjects', 'elective_required_count')) {
                $table->integer('elective_required_count')->nullable()->after('elective_group_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('curriculum_subjects', function (Blueprint $table) {
            if (Schema::hasColumn('curriculum_subjects', 'elective_required_count')) {
                $table->dropColumn('elective_required_count');
            }

            if (Schema::hasColumn('curriculum_subjects', 'elective_group_name')) {
                $table->dropColumn('elective_group_name');
            }

            if (Schema::hasColumn('curriculum_subjects', 'elective_group_code')) {
                $table->dropColumn('elective_group_code');
            }

            if (Schema::hasColumn('curriculum_subjects', 'curriculum_subject_type')) {
                $table->dropColumn('curriculum_subject_type');
            }
        });
    }
};