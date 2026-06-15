<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_terms', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();

            $table->foreignId('program_id')->nullable()->constrained('programs')->nullOnDelete();

            $table->string('code');
            $table->string('name');

            $table->integer('term_number')->default(1);

            $table->enum('term_type', [
                'semester',
                'year',
                'term',
                'class',
                'level'
            ])->default('semester');

            $table->text('description')->nullable();

            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'program_id', 'code']);
            $table->index(['tenant_id', 'program_id', 'term_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_terms');
    }
};