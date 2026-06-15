<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('assessment_results')) {
            return;
        }

        Schema::table('assessment_results', function (Blueprint $table) {
            if (!Schema::hasColumn('assessment_results', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('assessment_results')) {
            return;
        }

        Schema::table('assessment_results', function (Blueprint $table) {
            if (Schema::hasColumn('assessment_results', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};