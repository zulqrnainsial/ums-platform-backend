<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('admission_preference_groups')) {
            Schema::create('admission_preference_groups', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('admission_session_id');

                $table->string('code', 80);
                $table->string('name', 150);
                $table->text('description')->nullable();

                $table->unsignedInteger('min_preferences')->default(1);
                $table->unsignedInteger('max_preferences')->nullable();

                $table->boolean('is_default')->default(false);
                $table->string('status_code', 50)->default('active');
                $table->unsignedInteger('display_order')->default(0);

                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->unique(
                    ['tenant_id', 'admission_session_id', 'code'],
                    'adm_pg_tenant_session_code_uq'
                );

                $table->index(['tenant_id'], 'adm_pg_tenant_idx');
                $table->index(['admission_session_id'], 'adm_pg_session_idx');
                $table->index(['status_code'], 'adm_pg_status_idx');
            });
        }

        if (!Schema::hasTable('admission_preference_group_programs')) {
            Schema::create('admission_preference_group_programs', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('admission_preference_group_id');
                $table->unsignedBigInteger('offered_program_id');

                $table->unsignedInteger('display_order')->default(0);
                $table->string('status_code', 50)->default('active');

                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->unique(
                    ['tenant_id', 'admission_preference_group_id', 'offered_program_id'],
                    'adm_pgp_group_program_uq'
                );

                $table->index(['tenant_id'], 'adm_pgp_tenant_idx');
                $table->index(['admission_preference_group_id'], 'adm_pgp_group_idx');
                $table->index(['offered_program_id'], 'adm_pgp_offered_idx');
                $table->index(['status_code'], 'adm_pgp_status_idx');
            });
        }

        if (
            Schema::hasTable('applicant_application_groups') &&
            !Schema::hasColumn('applicant_application_groups', 'admission_preference_group_id')
        ) {
            Schema::table('applicant_application_groups', function (Blueprint $table) {
                $table->unsignedBigInteger('admission_preference_group_id')
                    ->nullable()
                    ->after('admission_session_id');

                $table->index(
                    ['tenant_id', 'admission_preference_group_id'],
                    'appgrp_prefgrp_idx'
                );
            });
        }

        if (
            Schema::hasTable('applicant_program_applications') &&
            Schema::hasColumn('applicant_program_applications', 'program_quota_seat_id')
        ) {
            DB::statement(
                'ALTER TABLE applicant_program_applications MODIFY program_quota_seat_id BIGINT UNSIGNED NULL'
            );
        }
    }

    public function down(): void
    {
        if (
            Schema::hasTable('applicant_application_groups') &&
            Schema::hasColumn('applicant_application_groups', 'admission_preference_group_id')
        ) {
            Schema::table('applicant_application_groups', function (Blueprint $table) {
                $table->dropIndex('appgrp_prefgrp_idx');
                $table->dropColumn('admission_preference_group_id');
            });
        }

        Schema::dropIfExists('admission_preference_group_programs');
        Schema::dropIfExists('admission_preference_groups');

        if (
            Schema::hasTable('applicant_program_applications') &&
            Schema::hasColumn('applicant_program_applications', 'program_quota_seat_id')
        ) {
            DB::statement(
                'ALTER TABLE applicant_program_applications MODIFY program_quota_seat_id BIGINT UNSIGNED NOT NULL'
            );
        }
    }
};