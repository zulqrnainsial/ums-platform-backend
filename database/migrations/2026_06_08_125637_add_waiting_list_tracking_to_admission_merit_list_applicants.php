<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admission_merit_list_applicants', function (Blueprint $table) {
            if (!Schema::hasColumn('admission_merit_list_applicants', 'waiting_position')) {
                $table->unsignedInteger('waiting_position')->nullable()->after('merit_position');
            }

            if (!Schema::hasColumn('admission_merit_list_applicants', 'waiting_status_code')) {
                $table->string('waiting_status_code', 50)->nullable()->after('selection_status_code');
            }

            if (!Schema::hasColumn('admission_merit_list_applicants', 'promoted_from_waiting_at')) {
                $table->timestamp('promoted_from_waiting_at')->nullable()->after('waiting_status_code');
            }

            if (!Schema::hasColumn('admission_merit_list_applicants', 'promoted_from_waiting_by')) {
                $table->unsignedBigInteger('promoted_from_waiting_by')->nullable()->after('promoted_from_waiting_at');
            }
        });

        if (!Schema::hasTable('admission_waiting_list_movements')) {
            Schema::create('admission_waiting_list_movements', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('tenant_id')->nullable();

                $table->unsignedBigInteger('admission_merit_list_id');
                $table->unsignedBigInteger('from_merit_list_applicant_id')->nullable();
                $table->unsignedBigInteger('to_merit_list_applicant_id')->nullable();

                $table->unsignedBigInteger('from_applicant_id')->nullable();
                $table->unsignedBigInteger('to_applicant_id')->nullable();

                $table->string('movement_type_code', 50);
                $table->string('from_selection_status_code', 50)->nullable();
                $table->string('to_selection_status_code', 50)->nullable();
                $table->string('from_offer_status_code', 50)->nullable();
                $table->string('to_offer_status_code', 50)->nullable();

                $table->unsignedInteger('waiting_position')->nullable();

                $table->text('remarks')->nullable();

                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'admission_merit_list_id'], 'adm_wait_move_tenant_list_idx');
                $table->index('to_merit_list_applicant_id', 'adm_wait_move_to_mla_idx');
                $table->index('from_merit_list_applicant_id', 'adm_wait_move_from_mla_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('admission_waiting_list_movements')) {
            Schema::dropIfExists('admission_waiting_list_movements');
        }

        Schema::table('admission_merit_list_applicants', function (Blueprint $table) {
            foreach ([
                'promoted_from_waiting_by',
                'promoted_from_waiting_at',
                'waiting_status_code',
                'waiting_position',
            ] as $column) {
                if (Schema::hasColumn('admission_merit_list_applicants', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};