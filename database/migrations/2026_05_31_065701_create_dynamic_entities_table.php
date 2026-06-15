<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dynamic_entities', function (Blueprint $table) {
            $table->id();

            $table->string('module_name');
            $table->string('entity_name');
            $table->string('entity_code')->unique();

            $table->string('table_name');
            $table->string('model_class')->nullable();

            $table->string('api_endpoint');
            $table->string('title');
            $table->string('subtitle')->nullable();

            $table->boolean('is_tenant_scoped')->default(true);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);

            $table->json('default_sort')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['module_name', 'entity_code']);
            $table->index(['table_name']);
            $table->index(['is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dynamic_entities');
    }
};