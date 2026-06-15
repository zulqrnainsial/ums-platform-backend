<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eligibility_rule_types', function (Blueprint $table) {
            $table->id();

            /*
             | null tenant_id = system/global rule type.
             | tenant_id = tenant-specific additional rule type if needed later.
             */
            $table->foreignId('tenant_id')
                ->nullable()
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->string('code');
            $table->string('name');

            /*
             | Examples:
             | applicant
             | qualification
             | test
             | document
             | experience
             | research
             */
            $table->string('source_area');

            /*
             | Examples:
             | applicants
             | applicant_qualifications
             | applicant_test_results
             | applicant_documents
             */
            $table->string('source_collection')->nullable();

            /*
             | Examples:
             | date_of_birth
             | percentage
             | qualification_level_id
             | score
             | document_type_id
             */
            $table->string('source_field')->nullable();

            /*
             | Examples:
             | string
             | number
             | date
             | lookup
             | boolean
             | json
             */
            $table->string('expected_value_type')->default('string');

            /*
             | This is used by backend service to route logic.
             | Examples:
             | applicant_field_match
             | age_compare
             | qualification_exists
             | qualification_numeric_compare
             | test_numeric_compare
             | document_exists
             */
            $table->string('evaluator_key');

            $table->boolean('is_system')->default(true);
            $table->boolean('is_active')->default(true);

            $table->integer('display_order')->default(0);

            $table->text('description')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);

            $table->index(['tenant_id', 'is_active']);
            $table->index(['source_area', 'evaluator_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eligibility_rule_types');
    }
};