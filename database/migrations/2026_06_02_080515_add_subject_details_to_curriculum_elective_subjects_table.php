<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('curriculum_elective_subjects', function (Blueprint $table) {
            if (!Schema::hasColumn('curriculum_elective_subjects', 'subject_code')) {
                $table->string('subject_code')->nullable()->after('subject_id');
            }

            if (!Schema::hasColumn('curriculum_elective_subjects', 'subject_name')) {
                $table->string('subject_name')->nullable()->after('subject_code');
            }

            if (!Schema::hasColumn('curriculum_elective_subjects', 'subject_nature')) {
                $table->string('subject_nature')->default('theory')->after('subject_name');
            }

            if (!Schema::hasColumn('curriculum_elective_subjects', 'credit_hours')) {
                $table->decimal('credit_hours', 5, 2)->default(0)->after('subject_nature');
            }

            if (!Schema::hasColumn('curriculum_elective_subjects', 'theory_hours')) {
                $table->integer('theory_hours')->default(0)->after('credit_hours');
            }

            if (!Schema::hasColumn('curriculum_elective_subjects', 'practical_hours')) {
                $table->integer('practical_hours')->default(0)->after('theory_hours');
            }

            if (!Schema::hasColumn('curriculum_elective_subjects', 'tutorial_hours')) {
                $table->integer('tutorial_hours')->default(0)->after('practical_hours');
            }

            if (!Schema::hasColumn('curriculum_elective_subjects', 'total_marks')) {
                $table->integer('total_marks')->default(100)->after('tutorial_hours');
            }

            if (!Schema::hasColumn('curriculum_elective_subjects', 'passing_marks')) {
                $table->integer('passing_marks')->default(40)->after('total_marks');
            }

            if (!Schema::hasColumn('curriculum_elective_subjects', 'is_compulsory')) {
                $table->boolean('is_compulsory')->default(false)->after('passing_marks');
            }

            if (!Schema::hasColumn('curriculum_elective_subjects', 'is_credit_subject')) {
                $table->boolean('is_credit_subject')->default(true)->after('is_compulsory');
            }
        });
    }

    public function down(): void
    {
        Schema::table('curriculum_elective_subjects', function (Blueprint $table) {
            foreach ([
                'subject_code',
                'subject_name',
                'subject_nature',
                'credit_hours',
                'theory_hours',
                'practical_hours',
                'tutorial_hours',
                'total_marks',
                'passing_marks',
                'is_compulsory',
                'is_credit_subject',
            ] as $column) {
                if (Schema::hasColumn('curriculum_elective_subjects', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};