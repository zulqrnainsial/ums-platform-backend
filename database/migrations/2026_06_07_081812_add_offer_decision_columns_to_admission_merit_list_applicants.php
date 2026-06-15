<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('admission_merit_list_applicants')) {
            return;
        }

        Schema::table('admission_merit_list_applicants', function (Blueprint $table) {
            if (!Schema::hasColumn('admission_merit_list_applicants', 'accepted_at')) {
                $table->dateTime('accepted_at')->nullable()->after('offer_expiry_at');
            }

            if (!Schema::hasColumn('admission_merit_list_applicants', 'rejected_at')) {
                $table->dateTime('rejected_at')->nullable()->after('accepted_at');
            }

            if (!Schema::hasColumn('admission_merit_list_applicants', 'expired_at')) {
                $table->dateTime('expired_at')->nullable()->after('rejected_at');
            }

            if (!Schema::hasColumn('admission_merit_list_applicants', 'decision_by')) {
                $table->unsignedBigInteger('decision_by')->nullable()->after('expired_at');
            }

            if (!Schema::hasColumn('admission_merit_list_applicants', 'decision_remarks')) {
                $table->text('decision_remarks')->nullable()->after('decision_by');
            }

            if (!Schema::hasColumn('admission_merit_list_applicants', 'moved_from_waiting_at')) {
                $table->dateTime('moved_from_waiting_at')->nullable()->after('decision_remarks');
            }

            if (!Schema::hasColumn('admission_merit_list_applicants', 'movement_source_applicant_id')) {
                $table->unsignedBigInteger('movement_source_applicant_id')->nullable()->after('moved_from_waiting_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('admission_merit_list_applicants')) {
            return;
        }

        Schema::table('admission_merit_list_applicants', function (Blueprint $table) {
            foreach ([
                'accepted_at',
                'rejected_at',
                'expired_at',
                'decision_by',
                'decision_remarks',
                'moved_from_waiting_at',
                'movement_source_applicant_id',
            ] as $column) {
                if (Schema::hasColumn('admission_merit_list_applicants', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};