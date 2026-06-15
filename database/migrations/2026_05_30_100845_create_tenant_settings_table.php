<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_settings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();

            $table->string('group')->default('general');
            $table->string('key');
            $table->longText('value')->nullable();

            $table->string('data_type')->default('string');

            $table->boolean('is_public')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['tenant_id', 'group', 'key']);
            $table->index(['tenant_id', 'group', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_settings');
    }
};