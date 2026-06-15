<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('menus')->cascadeOnDelete();
            $table->foreignId('module_id')->nullable()->constrained('modules')->nullOnDelete();

            $table->string('title');
            $table->string('code')->unique();
            $table->string('route')->nullable();
            $table->string('icon')->nullable();

            $table->string('permission_name')->nullable();

            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);

            $table->integer('display_order')->default(0);

            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'parent_id']);
            $table->index(['module_id', 'is_active']);
            $table->index(['permission_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};