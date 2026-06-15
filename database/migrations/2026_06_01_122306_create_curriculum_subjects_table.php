<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curriculum_subjects', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();

            $table->foreignId('curriculum_id')->constrained('curriculums')->cascadeOnDelete();
            $table->foreignId('program_id')->constrained('programs')->cascadeOnDelete();
            $table->foreignId('academic_term_id')->nullable()->constrained('academic_terms')->nullOnDelete();

            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();

            $table->string('subject_code')->nullable();
            $table->string('subject_name')->nullable();

            $table->integer('term_number')->default(1);

            $table->decimal('credit_hours', 5, 2)->default(0);
            $table->integer('theory_hours')->default(0);
            $table->integer('practical_hours')->default(0);
            $table->integer('tutorial_hours')->default(0);

            $table->integer('total_marks')->default(100);
            $table->integer('passing_marks')->default(40);

            $table->boolean('is_compulsory')->default(true);
            $table->boolean('is_credit_subject')->default(true);

            $table->integer('display_order')->default(0);

            $table->text('remarks')->nullable();

            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['tenant_id', 'curriculum_id', 'academic_term_id', 'subject_id'],
                'curriculum_subject_unique'
            );

            $table->index(
    ['tenant_id', 'curriculum_id', 'program_id', 'academic_term_id'],
    'cs_tenant_curr_prog_term_idx'
);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curriculum_subjects');
    }
};