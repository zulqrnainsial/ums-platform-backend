<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applicant_qualification_subjects', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();

            $table->unsignedBigInteger('applicant_qualification_id');
            $table->foreign('applicant_qualification_id', 'aqs_qual_fk')
                ->references('id')
                ->on('applicant_qualifications')
                ->cascadeOnDelete();

            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();

            $table->string('subject_code')->nullable();
            $table->string('subject_name');

            $table->decimal('total_marks', 10, 2)->nullable();
            $table->decimal('obtained_marks', 10, 2)->nullable();
            $table->decimal('percentage', 6, 2)->nullable();

            $table->string('grade')->nullable();
            $table->string('result_status_code')->default('passed');

            $table->text('remarks')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'applicant_qualification_id'], 'aqs_tenant_qual_idx');
            $table->index(['tenant_id', 'subject_id'], 'aqs_tenant_subject_idx');
            $table->index(['tenant_id', 'result_status_code'], 'aqs_tenant_result_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applicant_qualification_subjects');
    }
};