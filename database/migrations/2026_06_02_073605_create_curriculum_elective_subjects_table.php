<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curriculum_elective_subjects', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();

            $table->foreignId('curriculum_id')->constrained('curriculums')->cascadeOnDelete();
            $table->foreignId('program_id')->constrained('programs')->cascadeOnDelete();
            $table->foreignId('academic_term_id')->nullable()->constrained('academic_terms')->nullOnDelete();

            $table->string('elective_group_code');
            $table->string('elective_group_name')->nullable();

            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();

            $table->integer('display_order')->default(0);
            $table->text('remarks')->nullable();

            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['tenant_id', 'curriculum_id', 'elective_group_code', 'subject_id'],
                'curriculum_elective_unique'
            );

            $table->index(
                ['tenant_id', 'curriculum_id', 'program_id', 'academic_term_id'],
                'curriculum_elective_scope_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curriculum_elective_subjects');
    }
};