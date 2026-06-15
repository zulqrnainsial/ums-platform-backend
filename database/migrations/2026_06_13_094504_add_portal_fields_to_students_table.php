<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('students')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->index()->after('tenant_id');
            }

            if (!Schema::hasColumn('students', 'portal_access_enabled')) {
                $table->boolean('portal_access_enabled')->default(true)->index()->after('user_id');
            }

            if (!Schema::hasColumn('students', 'portal_activated_at')) {
                $table->timestamp('portal_activated_at')->nullable()->after('portal_access_enabled');
            }

            if (!Schema::hasColumn('students', 'last_portal_login_at')) {
                $table->timestamp('last_portal_login_at')->nullable()->after('portal_activated_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('students')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            foreach ([
                'last_portal_login_at',
                'portal_activated_at',
                'portal_access_enabled',
                'user_id',
            ] as $column) {
                if (Schema::hasColumn('students', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};