<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dynamic_fields', function (Blueprint $table) {
            $table->id();

            $table->foreignId('dynamic_entity_id')
                ->constrained('dynamic_entities')
                ->cascadeOnDelete();

            $table->string('field_name');
            $table->string('label');

            $table->string('control_type')->default('text');
            $table->string('data_type')->default('string');

            $table->string('placeholder')->nullable();
            $table->text('help_text')->nullable();

            $table->boolean('is_required')->default(false);
            $table->boolean('is_visible_in_table')->default(true);
            $table->boolean('is_visible_in_form')->default(true);
            $table->boolean('is_visible_in_view')->default(true);
            $table->boolean('is_filterable')->default(false);
            $table->boolean('is_sortable')->default(false);
            $table->boolean('is_readonly')->default(false);
            $table->boolean('is_unique')->default(false);

            $table->string('options_source_type')->nullable();
            $table->string('options_source_url')->nullable();
            $table->json('options_static_json')->nullable();

            $table->json('validation_rules')->nullable();
            $table->json('display_rules')->nullable();

            $table->integer('display_order')->default(0);
            $table->integer('table_width')->nullable();

            $table->string('relation_name')->nullable();
            $table->string('relation_label_field')->nullable();
            $table->string('relation_value_field')->nullable();

            $table->string('default_value')->nullable();

            $table->json('meta')->nullable();

            $table->timestamps();

            $table->unique(['dynamic_entity_id', 'field_name']);
            $table->index(['dynamic_entity_id', 'display_order']);
            $table->index(['control_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dynamic_fields');
    }
};