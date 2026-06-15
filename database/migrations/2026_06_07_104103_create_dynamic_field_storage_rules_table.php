<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('dynamic_field_storage_rules')) {
            return;
        }

        Schema::create('dynamic_field_storage_rules', function (Blueprint $table) {
            $table->id();

            $table->string('module_code', 80);
            $table->string('entity_key', 120);
            $table->string('field_name', 120);
            $table->string('field_label', 180)->nullable();

            /*
             | id         = store numeric ID
             | code       = store stable code
             | raw        = store entered raw value
             | json_ids   = store array of numeric IDs
             | json_codes = store array of stable codes
             */
            $table->string('storage_mode', 40);

            /*
             | Example:
             | admission-sessions
             | qualification-levels
             | assessment-question-types
             | merit-source-keys
             */
            $table->string('option_source_key', 120)->nullable();

            /*
             | master_data = tenant/admin configurable data
             | system_enum = fixed workflow/status/type codes
             | user_input  = applicant/user raw input
             */
            $table->string('value_category', 60)->default('master_data');

            $table->boolean('is_business_critical')->default(true);
            $table->boolean('is_required_for_rules')->default(false);
            $table->boolean('is_system_locked')->default(false);

            $table->string('status_code', 60)->default('active');
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(
                ['module_code', 'entity_key', 'field_name'],
                'dyn_field_storage_unique'
            );

            $table->index(['module_code', 'entity_key'], 'dyn_field_storage_entity_idx');
            $table->index(['field_name'], 'dyn_field_storage_field_idx');
            $table->index(['storage_mode'], 'dyn_field_storage_mode_idx');
            $table->index(['option_source_key'], 'dyn_field_storage_source_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dynamic_field_storage_rules');
    }
};