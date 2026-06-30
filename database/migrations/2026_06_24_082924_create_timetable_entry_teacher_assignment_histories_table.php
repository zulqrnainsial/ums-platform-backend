<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('timetable_entry_teacher_assignment_histories')) {
            return;
        }

        Schema::create('timetable_entry_teacher_assignment_histories', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id')
                ->index('teth_tenant_idx');

            $table->unsignedBigInteger('timetable_entry_id')
                ->index('teth_entry_idx');

            $table->unsignedBigInteger('old_faculty_member_id')
                ->nullable()
                ->index('teth_old_faculty_idx');

            $table->unsignedBigInteger('new_faculty_member_id')
                ->nullable()
                ->index('teth_new_faculty_idx');

            $table->unsignedBigInteger('old_course_teacher_allocation_id')
                ->nullable()
                ->index('teth_old_alloc_idx');

            $table->unsignedBigInteger('new_course_teacher_allocation_id')
                ->nullable()
                ->index('teth_new_alloc_idx');

            $table->string('change_type_code', 50)
                ->default('teacher_replaced')
                ->index('teth_change_type_idx');

            $table->text('reason')->nullable();

            $table->unsignedBigInteger('changed_by')
                ->nullable()
                ->index('teth_changed_by_idx');

            $table->timestamp('changed_at')->nullable();

            $table->timestamps();

            $table->index(
                ['tenant_id', 'timetable_entry_id', 'changed_at'],
                'teth_entry_changed_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable_entry_teacher_assignment_histories');
    }
};