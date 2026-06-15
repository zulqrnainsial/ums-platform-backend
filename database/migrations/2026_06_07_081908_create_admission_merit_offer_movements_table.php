<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('admission_merit_offer_movements')) {
            return;
        }

        Schema::create('admission_merit_offer_movements', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('admission_merit_list_id');

            $table->unsignedBigInteger('from_merit_list_applicant_id')->nullable();
            $table->unsignedBigInteger('from_applicant_id')->nullable();

            $table->unsignedBigInteger('to_merit_list_applicant_id')->nullable();
            $table->unsignedBigInteger('to_applicant_id')->nullable();

            /*
             | offer_generated, offer_accepted, offer_rejected,
             | offer_expired, waiting_promoted, offer_cancelled
             */
            $table->string('movement_type_code', 80);

            $table->string('from_selection_status_code', 80)->nullable();
            $table->string('to_selection_status_code', 80)->nullable();

            $table->string('from_offer_status_code', 80)->nullable();
            $table->string('to_offer_status_code', 80)->nullable();

            $table->text('remarks')->nullable();
            $table->json('movement_snapshot_json')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'admission_merit_list_id'], 'adm_merit_offer_mov_list_idx');
            $table->index(['tenant_id', 'movement_type_code'], 'adm_merit_offer_mov_type_idx');

            $table->foreign('tenant_id', 'adm_merit_offer_mov_tenant_fk')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->foreign('admission_merit_list_id', 'adm_merit_offer_mov_list_fk')
                ->references('id')
                ->on('admission_merit_lists')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_merit_offer_movements');
    }
};