<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applicant_test_results', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();

            $table->foreignId('applicant_id')->constrained('applicants')->cascadeOnDelete();

            $table->foreignId('applicant_program_application_id')
                ->nullable()
                ->constrained('applicant_program_applications')
                ->nullOnDelete();

            $table->foreignId('test_type_id')->nullable()->constrained('lookup_values')->nullOnDelete();

            /*
             | external / tenant / imported
             */
            $table->string('test_source_code')->default('external');

            /*
             | For now test is code based.
             | Later tenant test module can use this code or add admission_test_id safely.
             */
            $table->string('test_code')->nullable();
            $table->string('test_name')->nullable();

            $table->string('roll_no')->nullable();
            $table->date('test_date')->nullable();

            $table->decimal('total_marks', 10, 2)->nullable();
            $table->decimal('obtained_marks', 10, 2)->nullable();
            $table->decimal('percentage', 6, 2)->nullable();
            $table->decimal('percentile', 6, 2)->nullable();

            $table->string('result_status_code')->default('submitted');

            $table->boolean('is_verified')->default(false);

            $table->foreignId('document_id')
                ->nullable()
                ->constrained('applicant_documents')
                ->nullOnDelete();

            $table->text('remarks')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'applicant_id'], 'atr_tenant_app_idx');
            $table->index(['tenant_id', 'applicant_program_application_id'], 'atr_tenant_appl_idx');
            $table->index(['tenant_id', 'test_type_id'], 'atr_tenant_test_type_idx');
            $table->index(['tenant_id', 'test_code'], 'atr_tenant_test_code_idx');
            $table->index(['tenant_id', 'result_status_code'], 'atr_tenant_result_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applicant_test_results');
    }
};