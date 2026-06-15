<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('applicants', 'user_id')) {
            Schema::table('applicants', function (Blueprint $table) {
                $table->foreignId('user_id')
                    ->nullable()
                    ->after('tenant_id')
                    ->constrained('users')
                    ->nullOnDelete();

                $table->index(['tenant_id', 'user_id'], 'app_tenant_user_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('applicants', 'user_id')) {
            Schema::table('applicants', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropIndex('app_tenant_user_idx');
                $table->dropColumn('user_id');
            });
        }
    }
};