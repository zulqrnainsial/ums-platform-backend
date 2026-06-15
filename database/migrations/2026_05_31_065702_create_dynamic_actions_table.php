<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dynamic_actions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('dynamic_entity_id')
                ->constrained('dynamic_entities')
                ->cascadeOnDelete();

            $table->string('action_name');
            $table->string('label');

            $table->string('action_type')->default('button');
            $table->string('placement')->default('row');

            $table->string('permission_name')->nullable();

            $table->string('http_method')->nullable();
            $table->string('api_endpoint')->nullable();
            $table->string('frontend_route')->nullable();

            $table->string('icon')->nullable();
            $table->string('color')->nullable();

            $table->boolean('confirmation_required')->default(false);
            $table->string('confirmation_title')->nullable();
            $table->string('confirmation_message')->nullable();

            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);

            $table->json('visible_when')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->unique(['dynamic_entity_id', 'action_name']);
            $table->index(['dynamic_entity_id', 'placement']);
            $table->index(['permission_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dynamic_actions');
    }
};