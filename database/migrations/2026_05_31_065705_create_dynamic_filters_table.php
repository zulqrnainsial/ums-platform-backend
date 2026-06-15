<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dynamic_filters', function (Blueprint $table) {
            $table->id();

            $table->foreignId('dynamic_entity_id')
                ->constrained('dynamic_entities')
                ->cascadeOnDelete();

            $table->string('field_name');
            $table->string('label');

            $table->string('control_type')->default('text');
            $table->string('operator')->default('=');

            $table->string('placeholder')->nullable();

            $table->string('options_source_type')->nullable();
            $table->string('options_source_url')->nullable();
            $table->json('options_static_json')->nullable();

            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['dynamic_entity_id', 'field_name']);
            $table->index(['dynamic_entity_id', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dynamic_filters');
    }
};