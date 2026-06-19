<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_course_registrations', function (Blueprint $table) {
            if (!Schema::hasColumn('student_course_registrations', 'section_id')) {
                $table->unsignedBigInteger('section_id')->nullable()->index()->after('student_batch_id');
            }
        });

        DB::table('student_course_registrations as scr')
            ->join('student_enrollments as se', 'se.id', '=', 'scr.student_enrollment_id')
            ->whereNull('scr.section_id')
            ->update([
                'scr.section_id' => DB::raw('se.section_id'),
            ]);
    }

    public function down(): void
    {
        Schema::table('student_course_registrations', function (Blueprint $table) {
            if (Schema::hasColumn('student_course_registrations', 'section_id')) {
                $table->dropColumn('section_id');
            }
        });
    }
};