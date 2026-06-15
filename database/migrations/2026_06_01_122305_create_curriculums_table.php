<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curriculums', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();

            $table->foreignId('faculty_id')->nullable()->constrained('faculties')->nullOnDelete();
            $table->foreignId('institute_id')->nullable()->constrained('institutes')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('program_id')->constrained('programs')->cascadeOnDelete();

            $table->string('code');
            $table->string('name');

            $table->string('version')->nullable();

            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();

            $table->boolean('is_current')->default(false);

            $table->text('description')->nullable();

            $table->enum('status', [
                'draft',
                'active',
                'inactive',
                'archived'
            ])->default('draft');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'program_id', 'code']);
            $table->index(['tenant_id', 'program_id', 'is_current', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curriculums');
    }
};