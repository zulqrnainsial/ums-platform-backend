<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('curriculum_subjects', function (Blueprint $table) {
            if (!Schema::hasColumn('curriculum_subjects', 'subject_nature')) {
                $table->enum('subject_nature', [
                    'theory',
                    'practical',
                    'theory_practical',
                    'viva',
                    'project',
                    'internship',
                    'other',
                ])->default('theory')->after('subject_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('curriculum_subjects', function (Blueprint $table) {
            if (Schema::hasColumn('curriculum_subjects', 'subject_nature')) {
                $table->dropColumn('subject_nature');
            }
        });
    }
};