<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applicant_research_profiles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('applicant_id')->constrained('applicants')->cascadeOnDelete();

            $table->foreignId('research_area_id')->nullable()->constrained('lookup_values')->nullOnDelete();

            $table->string('proposed_research_title')->nullable();
            $table->text('statement_of_purpose')->nullable();
            $table->text('research_interests')->nullable();

            $table->string('preferred_supervisor_name')->nullable();
            $table->string('preferred_supervisor_email')->nullable();

            $table->string('status_code')->default('draft');

            $table->text('remarks')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'applicant_id'], 'arp_tenant_app_idx');
            $table->index(['tenant_id', 'research_area_id'], 'arp_tenant_area_idx');
            $table->index(['tenant_id', 'status_code'], 'arp_tenant_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applicant_research_profiles');
    }
};