<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasTable('admission_merit_lists') &&
            !Schema::hasColumn('admission_merit_lists', 'deleted_at')
        ) {
            Schema::table('admission_merit_lists', function (Blueprint $table) {
                $table->timestamp('deleted_at')->nullable()->after('updated_at');
            });
        }

        if (
            Schema::hasTable('admission_merit_list_applicants') &&
            !Schema::hasColumn('admission_merit_list_applicants', 'deleted_at')
        ) {
            Schema::table('admission_merit_list_applicants', function (Blueprint $table) {
                $table->timestamp('deleted_at')->nullable()->after('updated_at');
            });
        }
    }

    public function down(): void
    {
        if (
            Schema::hasTable('admission_merit_lists') &&
            Schema::hasColumn('admission_merit_lists', 'deleted_at')
        ) {
            Schema::table('admission_merit_lists', function (Blueprint $table) {
                $table->dropColumn('deleted_at');
            });
        }

        if (
            Schema::hasTable('admission_merit_list_applicants') &&
            Schema::hasColumn('admission_merit_list_applicants', 'deleted_at')
        ) {
            Schema::table('admission_merit_list_applicants', function (Blueprint $table) {
                $table->dropColumn('deleted_at');
            });
        }
    }
};