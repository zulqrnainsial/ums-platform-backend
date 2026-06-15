<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();

            $table->foreignId('subject_type_id')->nullable()->constrained('subject_types')->nullOnDelete();
            $table->foreignId('subject_group_id')->nullable()->constrained('subject_groups')->nullOnDelete();

            $table->string('code');
            $table->string('name');
            $table->string('short_name')->nullable();

            $table->decimal('credit_hours', 5, 2)->default(0);
            $table->integer('theory_hours')->default(0);
            $table->integer('practical_hours')->default(0);
            $table->integer('tutorial_hours')->default(0);

            $table->enum('subject_nature', [
                'theory',
                'practical',
                'theory_practical',
                'viva',
                'project',
                'internship',
                'other'
            ])->default('theory');

            $table->enum('grading_method', [
                'marks',
                'grade',
                'pass_fail',
                'attendance_only'
            ])->default('marks');

            $table->integer('total_marks')->default(100);
            $table->integer('passing_marks')->default(40);

            $table->boolean('is_credit_subject')->default(true);
            $table->boolean('is_compulsory')->default(true);

            $table->text('description')->nullable();

            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'subject_type_id', 'subject_group_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};