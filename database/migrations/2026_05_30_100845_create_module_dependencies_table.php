<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_dependencies', function (Blueprint $table) {
            $table->id();

            $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
            $table->foreignId('depends_on_module_id')->constrained('modules')->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['module_id', 'depends_on_module_id'], 'module_dependency_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_dependencies');
    }
};