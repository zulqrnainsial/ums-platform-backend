<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_eligibility_rules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();

            $table->foreignId('offered_program_id')
                ->constrained('offered_programs')
                ->cascadeOnDelete();

            /*
             | Nullable means rule applies to whole offered program.
             | Filled means rule applies only to specific quota.
             */
            $table->foreignId('program_quota_seat_id')
                ->nullable()
                ->constrained('program_quota_seats')
                ->cascadeOnDelete();

            $table->foreignId('eligibility_rule_type_id')
                ->constrained('eligibility_rule_types')
                ->restrictOnDelete();

            $table->string('rule_code');
            $table->string('rule_group')->nullable();
            $table->string('rule_title');

            /*
             | Operators are strings for flexibility:
             | =, !=, >, >=, <, <=, in, not_in, between, exists, not_exists
             */
            $table->string('operator')->default('=');

            $table->text('value_text')->nullable();
            $table->decimal('value_number', 12, 4)->nullable();
            $table->date('value_date')->nullable();
            $table->foreignId('value_lookup_id')
                ->nullable()
                ->constrained('lookup_values')
                ->nullOnDelete();
            $table->json('value_json')->nullable();

            /*
             | These target fields help evaluator map rules easily.
             */
            $table->foreignId('target_qualification_level_id')
                ->nullable()
                ->constrained('lookup_values')
                ->nullOnDelete();

            $table->foreignId('target_subject_group_id')
                ->nullable()
                ->constrained('lookup_values')
                ->nullOnDelete();

            $table->foreignId('target_document_type_id')
                ->nullable()
                ->constrained('lookup_values')
                ->nullOnDelete();

            /*
             | Test Management is not created yet, so keep test as code for now.
             | Later we can add test_id when test module exists.
             */
            $table->string('target_test_code')->nullable();

            $table->boolean('is_mandatory')->default(true);
            $table->boolean('is_active')->default(true);

            $table->string('failure_message')->nullable();
            $table->text('description')->nullable();

            $table->integer('display_order')->default(0);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'offered_program_id']);
            $table->index(['tenant_id', 'program_quota_seat_id']);
            $table->index(['tenant_id', 'eligibility_rule_type_id'], 'prog_eligi_rul_ten_rul_t_id');
            $table->index(['tenant_id', 'is_active']);
            $table->index(['rule_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_eligibility_rules');
    }
};