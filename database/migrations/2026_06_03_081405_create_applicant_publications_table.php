<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applicant_publications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('applicant_id')->constrained('applicants')->cascadeOnDelete();

            $table->foreignId('publication_type_id')->nullable()->constrained('lookup_values')->nullOnDelete();
            $table->foreignId('indexing_type_id')->nullable()->constrained('lookup_values')->nullOnDelete();

            $table->string('title');
            $table->string('journal_conference_name')->nullable();
            $table->string('publisher')->nullable();
            $table->string('publication_year')->nullable();

            $table->string('doi')->nullable();
            $table->string('url')->nullable();

            $table->string('status_code')->default('claimed');

            $table->boolean('is_verified')->default(false);

            $table->text('remarks')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'applicant_id'], 'apub_tenant_app_idx');
            $table->index(['tenant_id', 'publication_type_id'], 'apub_tenant_pub_type_idx');
            $table->index(['tenant_id', 'indexing_type_id'], 'apub_tenant_index_idx');
            $table->index(['tenant_id', 'status_code'], 'apub_tenant_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applicant_publications');
    }
};