<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            if (!Schema::hasColumn('questions', 'bloom_level_code')) {
                $table->string('bloom_level_code', 80)->nullable()->after('cognitive_level_code');
            }

            if (!Schema::hasColumn('questions', 'obe_level_code')) {
                $table->string('obe_level_code', 80)->nullable()->after('bloom_level_code');
            }

            if (!Schema::hasColumn('questions', 'learning_outcome_code')) {
                $table->string('learning_outcome_code', 100)->nullable()->after('obe_level_code');
            }

            if (!Schema::hasColumn('questions', 'course_outcome_code')) {
                $table->string('course_outcome_code', 100)->nullable()->after('learning_outcome_code');
            }

            if (!Schema::hasColumn('questions', 'metadata_json')) {
                $table->json('metadata_json')->nullable()->after('course_outcome_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            if (Schema::hasColumn('questions', 'metadata_json')) {
                $table->dropColumn('metadata_json');
            }

            if (Schema::hasColumn('questions', 'course_outcome_code')) {
                $table->dropColumn('course_outcome_code');
            }

            if (Schema::hasColumn('questions', 'learning_outcome_code')) {
                $table->dropColumn('learning_outcome_code');
            }

            if (Schema::hasColumn('questions', 'obe_level_code')) {
                $table->dropColumn('obe_level_code');
            }

            if (Schema::hasColumn('questions', 'bloom_level_code')) {
                $table->dropColumn('bloom_level_code');
            }
        });
    }
};