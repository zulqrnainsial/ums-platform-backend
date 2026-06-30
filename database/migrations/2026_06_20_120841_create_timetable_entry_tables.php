<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('timetable_entries')) {
            Schema::create('timetable_entries', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('tenant_id')->index();

                /*
                 |------------------------------------------------------------------
                 | Academic scope
                 |------------------------------------------------------------------
                 */
                $table->unsignedBigInteger('academic_session_id')->index();
                $table->unsignedBigInteger('academic_term_id')->index();

                /*
                 | Optional special timing period.
                 | Example:
                 | Normal timetable period
                 | Ramadan timetable period
                 */
                $table->unsignedBigInteger('timetable_calendar_period_id')
                    ->nullable()
                    ->index();

                /*
                 | Existing schedulable teaching unit.
                 | This already knows course, section/group, credit/contact hours.
                 */
                $table->unsignedBigInteger('course_offering_id')->index();

                /*
                 | Teacher allocation selected for this timetable entry.
                 */
                $table->unsignedBigInteger('course_teacher_allocation_id')
                    ->nullable()
                    ->index();

                $table->unsignedBigInteger('faculty_member_id')
                    ->nullable()
                    ->index();

                /*
                 | Stored as resolved values from the offering.
                 | These are not user-entered; backend will populate them later.
                 | They make conflict checks and timetable reports fast.
                 */
                $table->unsignedBigInteger('section_id')
                    ->nullable()
                    ->index();

                $table->unsignedBigInteger('academic_teaching_group_id')
                    ->nullable()
                    ->index();

                /*
                 | Existing room.
                 */
                $table->unsignedBigInteger('room_id')
                    ->nullable()
                    ->index();

                /*
                 | Day is stored because timetable_slots are day-specific.
                 | 1 = Monday ... 7 = Sunday
                 */
                $table->unsignedTinyInteger('day_of_week')->index();

                /*
                 | manual, generated, imported
                 */
                $table->string('entry_source_code', 50)
                    ->default('manual')
                    ->index();

                /*
                 | draft, valid, conflicted, approved, published, cancelled
                 */
                $table->string('status_code', 50)
                    ->default('draft')
                    ->index();

                /*
                 | A manual disable flag for later cases:
                 | temporary cancellation, room maintenance, emergency change.
                 */
                $table->boolean('is_active')->default(true)->index();

                $table->text('remarks')->nullable();

                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('updated_by')->nullable()->index();

                $table->timestamps();
                $table->softDeletes();

                $table->index([
                    'tenant_id',
                    'academic_session_id',
                    'academic_term_id',
                    'day_of_week',
                    'status_code',
                ], 'tt_entry_scope_day_status_idx');

                $table->index([
                    'tenant_id',
                    'faculty_member_id',
                    'day_of_week',
                    'is_active',
                ], 'tt_entry_faculty_day_idx');

                $table->index([
                    'tenant_id',
                    'room_id',
                    'day_of_week',
                    'is_active',
                ], 'tt_entry_room_day_idx');

                $table->index([
                    'tenant_id',
                    'section_id',
                    'day_of_week',
                    'is_active',
                ], 'tt_entry_section_day_idx');

                $table->index([
                    'tenant_id',
                    'academic_teaching_group_id',
                    'day_of_week',
                    'is_active',
                ], 'tt_entry_group_day_idx');
            });
        }

        /*
         |----------------------------------------------------------------------
         | One timetable entry can occupy multiple consecutive slots.
         |
         | Example:
         | Practical = 2 contact hours
         | Entry uses Slot 3 + Slot 4
         |----------------------------------------------------------------------
         */
        if (!Schema::hasTable('timetable_entry_slots')) {
            Schema::create('timetable_entry_slots', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('tenant_id')->index();

                $table->unsignedBigInteger('timetable_entry_id')->index();
                $table->unsignedBigInteger('timetable_slot_id')->index();

                $table->unsignedInteger('sort_order')->default(1);

                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('updated_by')->nullable()->index();

                $table->timestamps();

                $table->unique(
                    ['timetable_entry_id', 'timetable_slot_id'],
                    'tt_entry_slot_unique'
                );

                $table->index([
                    'tenant_id',
                    'timetable_slot_id',
                ], 'tt_entry_slot_lookup_idx');
            });
        }

        /*
         |----------------------------------------------------------------------
         | Conflict log
         |
         | Every validation can write one or more records here.
         | Conflicts can remain open, be overridden, or be resolved.
         |----------------------------------------------------------------------
         */
        if (!Schema::hasTable('timetable_conflicts')) {
            Schema::create('timetable_conflicts', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('tenant_id')->index();

                $table->unsignedBigInteger('timetable_entry_id')
                    ->nullable()
                    ->index();

                /*
                 | Existing entry that conflicts with this proposed/saved entry.
                 */
                $table->unsignedBigInteger('conflicting_timetable_entry_id')
                    ->nullable()
                    ->index();

                $table->unsignedBigInteger('course_offering_id')
                    ->nullable()
                    ->index();

                $table->unsignedBigInteger('faculty_member_id')
                    ->nullable()
                    ->index();

                $table->unsignedBigInteger('room_id')
                    ->nullable()
                    ->index();

                $table->unsignedBigInteger('section_id')
                    ->nullable()
                    ->index();

                $table->unsignedBigInteger('academic_teaching_group_id')
                    ->nullable()
                    ->index();

                $table->unsignedBigInteger('timetable_slot_id')
                    ->nullable()
                    ->index();

                /*
                 | Examples:
                 | TEACHER_SLOT_CONFLICT
                 | ROOM_SLOT_CONFLICT
                 | SECTION_SLOT_CONFLICT
                 | TEACHING_GROUP_SLOT_CONFLICT
                 | ROOM_CAPACITY_INSUFFICIENT
                 | ROOM_TYPE_MISMATCH
                 | ROOM_NOT_TIMETABLE_ACTIVE
                 | TEACHER_ALLOCATION_MISSING
                 | OFFERING_WEEKLY_HOURS_INCOMPLETE
                 */
                $table->string('conflict_code', 150)->index();

                /*
                 | error, warning, info
                 */
                $table->string('conflict_severity', 50)
                    ->default('error')
                    ->index();

                $table->string('conflict_message', 1000);

                $table->json('conflict_context')->nullable();

                /*
                 | open, overridden, resolved, ignored
                 */
                $table->string('status_code', 50)
                    ->default('open')
                    ->index();

                $table->text('resolution_note')->nullable();

                $table->unsignedBigInteger('resolved_by')->nullable()->index();
                $table->timestamp('resolved_at')->nullable();

                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('updated_by')->nullable()->index();

                $table->timestamps();

                $table->index([
                    'tenant_id',
                    'status_code',
                    'conflict_severity',
                ], 'tt_conflict_scope_status_idx');

                $table->index([
                    'tenant_id',
                    'faculty_member_id',
                    'room_id',
                    'section_id',
                    'academic_teaching_group_id',
                ], 'tt_conflict_entity_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable_conflicts');
        Schema::dropIfExists('timetable_entry_slots');
        Schema::dropIfExists('timetable_entries');
    }
};