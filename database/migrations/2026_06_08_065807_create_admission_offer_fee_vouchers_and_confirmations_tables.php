<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('admission_offer_fee_vouchers')) {
            Schema::create('admission_offer_fee_vouchers', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('tenant_id')->nullable();

                $table->string('voucher_no', 100)->unique();

                $table->unsignedBigInteger('applicant_id');
                $table->unsignedBigInteger('admission_merit_list_applicant_id');
                $table->unsignedBigInteger('admission_merit_list_id')->nullable();
                $table->unsignedBigInteger('admission_session_id')->nullable();
                $table->unsignedBigInteger('offered_program_id')->nullable();
                $table->unsignedBigInteger('program_quota_seat_id')->nullable();

                $table->decimal('amount', 12, 2)->default(0);
                $table->string('currency_code', 20)->default('PKR');

                $table->date('due_date')->nullable();

                $table->string('status_code', 50)->default('unpaid');
                $table->decimal('paid_amount', 12, 2)->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->string('payment_reference', 100)->nullable();

                $table->text('remarks')->nullable();

                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();

                $table->timestamps();

                $table->index(['tenant_id', 'applicant_id']);
                $table->index(['tenant_id', 'status_code']);
                $table->index('admission_merit_list_applicant_id', 'adm_offer_voucher_mla_idx');
            });
        }

        if (!Schema::hasTable('admission_confirmations')) {
            Schema::create('admission_confirmations', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('tenant_id')->nullable();

                $table->string('confirmation_no', 100)->unique();

                $table->unsignedBigInteger('applicant_id');
                $table->unsignedBigInteger('admission_merit_list_applicant_id');
                $table->unsignedBigInteger('admission_merit_list_id')->nullable();
                $table->unsignedBigInteger('admission_offer_fee_voucher_id')->nullable();
                $table->unsignedBigInteger('admission_session_id')->nullable();
                $table->unsignedBigInteger('offered_program_id')->nullable();
                $table->unsignedBigInteger('program_quota_seat_id')->nullable();

                $table->string('status_code', 50)->default('confirmed');
                $table->timestamp('confirmed_at')->nullable();

                $table->text('remarks')->nullable();

                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();

                $table->timestamps();

                $table->index(['tenant_id', 'applicant_id']);
                $table->index(['tenant_id', 'status_code']);
                $table->index('admission_merit_list_applicant_id', 'adm_confirmation_mla_idx');
            });
        }

        Schema::table('admission_merit_list_applicants', function (Blueprint $table) {
            if (!Schema::hasColumn('admission_merit_list_applicants', 'voucher_status_code')) {
                $table->string('voucher_status_code', 50)->nullable()->after('offer_status_code');
            }

            if (!Schema::hasColumn('admission_merit_list_applicants', 'admission_confirmation_status_code')) {
                $table->string('admission_confirmation_status_code', 50)->nullable()->after('voucher_status_code');
            }

            if (!Schema::hasColumn('admission_merit_list_applicants', 'admission_confirmed_at')) {
                $table->timestamp('admission_confirmed_at')->nullable()->after('admission_confirmation_status_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('admission_merit_list_applicants', function (Blueprint $table) {
            if (Schema::hasColumn('admission_merit_list_applicants', 'admission_confirmed_at')) {
                $table->dropColumn('admission_confirmed_at');
            }

            if (Schema::hasColumn('admission_merit_list_applicants', 'admission_confirmation_status_code')) {
                $table->dropColumn('admission_confirmation_status_code');
            }

            if (Schema::hasColumn('admission_merit_list_applicants', 'voucher_status_code')) {
                $table->dropColumn('voucher_status_code');
            }
        });

        Schema::dropIfExists('admission_confirmations');
        Schema::dropIfExists('admission_offer_fee_vouchers');
    }
};