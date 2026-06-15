<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applicant_profile_step_statuses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('applicant_id')->constrained('applicants')->cascadeOnDelete();

            $table->string('step_code');
            $table->string('step_title');

            $table->string('status_code')->default('pending');

            $table->integer('display_order')->default(0);

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('verified_at')->nullable();

            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();

            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'applicant_id', 'step_code'], 'apss_unique_step');

            $table->index(['tenant_id', 'applicant_id'], 'apss_tenant_app_idx');
            $table->index(['tenant_id', 'status_code'], 'apss_tenant_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applicant_profile_step_statuses');
    }
};