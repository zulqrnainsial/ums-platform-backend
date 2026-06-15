<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applicant_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();

            $table->foreignId('applicant_id')
                ->constrained('applicants')
                ->cascadeOnDelete();

            $table->foreignId('applicant_program_application_id')
                ->constrained('applicant_program_applications')
                ->cascadeOnDelete();

            $table->foreignId('applicant_fee_voucher_id')
                ->constrained('applicant_fee_vouchers')
                ->cascadeOnDelete();

            /*
             | bank_deposit / online / cash / manual / adjustment
             */
            $table->string('payment_method_code')->default('bank_deposit');

            $table->string('payment_reference_no')->nullable();
            $table->date('payment_date')->nullable();

            $table->decimal('amount', 12, 2)->default(0);

            /*
             | submitted / verified / rejected / cancelled / refunded
             */
            $table->string('status_code')->default('submitted');

            /*
             | Payment proof document can be uploaded through applicant_documents.
             */
            $table->foreignId('payment_proof_document_id')
                ->nullable()
                ->constrained('applicant_documents')
                ->nullOnDelete();

            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();

            $table->text('rejection_reason')->nullable();
            $table->text('remarks')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'applicant_id'], 'apay_tenant_app_idx');
            $table->index(['tenant_id', 'applicant_program_application_id'], 'apay_tenant_appl_idx');
            $table->index(['tenant_id', 'applicant_fee_voucher_id'], 'apay_tenant_voucher_idx');
            $table->index(['tenant_id', 'payment_method_code'], 'apay_tenant_method_idx');
            $table->index(['tenant_id', 'status_code'], 'apay_tenant_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applicant_payments');
    }
};