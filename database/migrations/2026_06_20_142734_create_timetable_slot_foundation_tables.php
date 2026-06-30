<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    if (!Schema::hasTable('timetable_slot_sets')) {
        Schema::create('timetable_slot_sets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();

            $table->string('slot_set_code', 50);
            $table->string('slot_set_name', 150);
            $table->string('description', 500)->nullable();

            $table->boolean('is_default')->default(false)->index();
            $table->string('status_code', 50)->default('active')->index();

            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['tenant_id', 'slot_set_code'],
                'tt_slot_set_tenant_code_unique'
            );
        });
    }

    if (!Schema::hasTable('timetable_calendar_periods')) {
        Schema::create('timetable_calendar_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();

            $table->unsignedBigInteger('academic_session_id')->index();
            $table->unsignedBigInteger('academic_term_id')->index();
            $table->unsignedBigInteger('timetable_slot_set_id')->index();

            $table->string('period_code', 50);
            $table->string('period_name', 150);

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->unsignedInteger('priority')->default(1);
            $table->boolean('is_default')->default(true)->index();
            $table->string('status_code', 50)->default('active')->index();

            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                [
                    'tenant_id',
                    'academic_session_id',
                    'academic_term_id',
                    'period_code',
                ],
                'tt_calendar_period_scope_code_unique'
            );
        });
    }

    if (!Schema::hasTable('timetable_slots')) {
        Schema::create('timetable_slots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();

            $table->unsignedBigInteger('timetable_slot_set_id')->index();
            $table->unsignedTinyInteger('day_of_week')->index();

            $table->string('slot_code', 50);
            $table->string('slot_name', 150)->nullable();

            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedInteger('duration_minutes');
            $table->unsignedInteger('sort_order');

            $table->boolean('is_teaching_slot')->default(true)->index();
            $table->boolean('is_break')->default(false)->index();

            $table->string('status_code', 50)->default('active')->index();

            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                [
                    'tenant_id',
                    'timetable_slot_set_id',
                    'day_of_week',
                    'slot_code',
                ],
                'tt_slot_set_day_code_unique'
            );

            $table->unique(
                [
                    'tenant_id',
                    'timetable_slot_set_id',
                    'day_of_week',
                    'sort_order',
                ],
                'tt_slot_set_day_sort_order_unique'
            );
        });
    }
}

    public function down(): void
    {
        Schema::dropIfExists('timetable_slots');
        Schema::dropIfExists('timetable_calendar_periods');
        Schema::dropIfExists('timetable_slot_sets');
    }
};