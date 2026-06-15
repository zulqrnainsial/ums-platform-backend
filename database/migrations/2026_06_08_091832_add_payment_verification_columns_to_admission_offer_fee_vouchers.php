<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admission_offer_fee_vouchers', function (Blueprint $table) {
            if (!Schema::hasColumn('admission_offer_fee_vouchers', 'payment_method_code')) {
                $table->string('payment_method_code', 50)->nullable()->after('payment_reference');
            }

            if (!Schema::hasColumn('admission_offer_fee_vouchers', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('paid_at');
            }

            if (!Schema::hasColumn('admission_offer_fee_vouchers', 'verified_by')) {
                $table->unsignedBigInteger('verified_by')->nullable()->after('verified_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('admission_offer_fee_vouchers', function (Blueprint $table) {
            if (Schema::hasColumn('admission_offer_fee_vouchers', 'verified_by')) {
                $table->dropColumn('verified_by');
            }

            if (Schema::hasColumn('admission_offer_fee_vouchers', 'verified_at')) {
                $table->dropColumn('verified_at');
            }

            if (Schema::hasColumn('admission_offer_fee_vouchers', 'payment_method_code')) {
                $table->dropColumn('payment_method_code');
            }
        });
    }
};