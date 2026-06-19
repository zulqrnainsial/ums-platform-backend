<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_enrollments', function (Blueprint $table) {
            if (!Schema::hasColumn('student_enrollments', 'section_id')) {
                $table->unsignedBigInteger('section_id')->nullable()->index()->after('student_batch_id');
            }
        });

        if (Schema::hasTable('sections')) {
            DB::table('student_enrollments as se')
                ->join('sections as sec', function ($join) {
                    $join->on('sec.tenant_id', '=', 'se.tenant_id')
                        ->where(function ($q) {
                            $q->whereColumn('sec.code', 'se.section')
                                ->orWhereColumn('sec.name', 'se.section');
                        });
                })
                ->whereNull('se.section_id')
                ->update([
                    'se.section_id' => DB::raw('sec.id'),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('student_enrollments', function (Blueprint $table) {
            if (Schema::hasColumn('student_enrollments', 'section_id')) {
                $table->dropColumn('section_id');
            }
        });
    }
};
