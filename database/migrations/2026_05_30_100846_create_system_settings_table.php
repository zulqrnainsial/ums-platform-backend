<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();

            $table->string('group')->default('general');
            $table->string('key');
            $table->longText('value')->nullable();

            $table->string('data_type')->default('string');

            $table->boolean('is_public')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['group', 'key']);
            $table->index(['group', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};