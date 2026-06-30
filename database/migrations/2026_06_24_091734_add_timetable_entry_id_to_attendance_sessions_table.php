<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('attendance_sessions', 'timetable_entry_id')) {
            Schema::table('attendance_sessions', function (Blueprint $table) {
                $table->unsignedBigInteger('timetable_entry_id')
                    ->nullable()
                    ->after('tenant_id')
                    ->index('attendance_sessions_timetable_entry_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('attendance_sessions', 'timetable_entry_id')) {
            Schema::table('attendance_sessions', function (Blueprint $table) {
                $table->dropIndex('attendance_sessions_timetable_entry_idx');
                $table->dropColumn('timetable_entry_id');
            });
        }
    }
};