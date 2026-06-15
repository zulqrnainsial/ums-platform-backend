<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applicant_program_applications', function (Blueprint $table) {
            if (!Schema::hasColumn('applicant_program_applications', 'applicant_application_group_id')) {
                $table->foreignId('applicant_application_group_id')
                    ->nullable()
                    ->after('applicant_id')
                    ->constrained('applicant_application_groups')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('applicant_program_applications', 'preference_order')) {
                $table->unsignedInteger('preference_order')->default(1)->after('application_no');
            }
        });

        Schema::table('applicant_program_applications', function (Blueprint $table) {
            $table->index(
                ['tenant_id', 'applicant_application_group_id'],
                'apa_tenant_group_idx'
            );

            $table->unique(
                ['tenant_id', 'applicant_application_group_id', 'preference_order'],
                'apa_group_pref_unique'
            );

            $table->unique(
                [
                    'tenant_id',
                    'applicant_application_group_id',
                    'offered_program_id',
                    'program_quota_seat_id',
                ],
                'apa_group_program_quota_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('applicant_program_applications', function (Blueprint $table) {
            $table->dropUnique('apa_group_program_quota_unique');
            $table->dropUnique('apa_group_pref_unique');
            $table->dropIndex('apa_tenant_group_idx');

            if (Schema::hasColumn('applicant_program_applications', 'applicant_application_group_id')) {
                $table->dropForeign(['applicant_application_group_id']);
                $table->dropColumn('applicant_application_group_id');
            }

            /*
             | Do not drop preference_order here if it already existed before this migration.
             | Keep it safe because earlier admission code already uses it.
             */
        });
    }
};
