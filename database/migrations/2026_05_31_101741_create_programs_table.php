<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();

            $table->foreignId('faculty_id')->nullable()->constrained('faculties')->nullOnDelete();
            $table->foreignId('institute_id')->nullable()->constrained('institutes')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('program_level_id')->nullable()->constrained('program_levels')->nullOnDelete();

            $table->string('code');
            $table->string('name');
            $table->string('short_name')->nullable();

            $table->enum('program_type', [
                'annual',
                'semester',
                'term',
                'class_based',
                'level_based'
            ])->default('semester');

            $table->integer('duration_value')->default(4);
            $table->enum('duration_unit', [
                'years',
                'semesters',
                'terms',
                'months'
            ])->default('years');

            $table->integer('total_terms')->default(8);

            $table->text('description')->nullable();

            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'department_id', 'program_level_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programs');
    }
};