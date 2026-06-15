<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applicant_fee_vouchers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();

            $table->foreignId('applicant_id')
                ->constrained('applicants')
                ->cascadeOnDelete();

            $table->foreignId('applicant_program_application_id')
                ->constrained('applicant_program_applications')
                ->cascadeOnDelete();

            /*
             | application_fee / admission_fee / security_fee / hostel_fee etc.
             */
            $table->string('voucher_type_code')->default('application_fee');

            $table->string('voucher_no');

            $table->date('issue_date')->nullable();
            $table->date('due_date')->nullable();

            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('fine_amount', 12, 2)->default(0);
            $table->decimal('payable_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('balance_amount', 12, 2)->default(0);

            /*
             | unpaid / partially_paid / paid / verified / cancelled / expired
             */
            $table->string('status_code')->default('unpaid');

            $table->boolean('is_locked')->default(false);

            $table->text('description')->nullable();
            $table->text('remarks')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'voucher_no'], 'afv_tenant_voucher_unique');

            $table->index(['tenant_id', 'applicant_id'], 'afv_tenant_app_idx');
            $table->index(['tenant_id', 'applicant_program_application_id'], 'afv_tenant_appl_idx');
            $table->index(['tenant_id', 'voucher_type_code'], 'afv_tenant_type_idx');
            $table->index(['tenant_id', 'status_code'], 'afv_tenant_status_idx');
            $table->index(['tenant_id', 'due_date'], 'afv_tenant_due_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applicant_fee_vouchers');
    }
};