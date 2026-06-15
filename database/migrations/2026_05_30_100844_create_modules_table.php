<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('parent_id')->nullable()->constrained('modules')->nullOnDelete();

            $table->string('name');
            $table->string('code')->unique();

            $table->string('description')->nullable();
            $table->string('icon')->nullable();

            $table->boolean('is_core')->default(false);
            $table->boolean('is_active')->default(true);

            $table->integer('display_order')->default(0);

            $table->json('settings_schema')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['code', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};