<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('campus_buildings')) {
            Schema::create('campus_buildings', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('tenant_id')->index();

                $table->unsignedBigInteger('campus_id')->nullable()->index();
                $table->unsignedBigInteger('faculty_id')->nullable()->index();
                $table->unsignedBigInteger('department_id')->nullable()->index();
                $table->unsignedBigInteger('program_id')->nullable()->index();

                $table->string('building_code', 100)->index();
                $table->string('building_name', 255);

                $table->string('building_type_code', 100)->nullable()->index();
                // academic, admin, lab_block, shared, hostel

                $table->string('ownership_scope_code', 100)->default('shared')->index();
                // shared, faculty, department, program

                $table->string('location_description', 500)->nullable();

                $table->string('status_code', 50)->default('active')->index();

                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('updated_by')->nullable()->index();

                $table->timestamps();
                $table->softDeletes();

                $table->unique(['tenant_id', 'building_code'], 'cb_tenant_code_unique');
            });
        }

        if (!Schema::hasTable('campus_floors')) {
            Schema::create('campus_floors', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('campus_building_id')->index();

                $table->string('floor_code', 100)->index();
                $table->string('floor_name', 255);

                $table->integer('floor_number')->nullable()->index();

                $table->string('status_code', 50)->default('active')->index();

                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('updated_by')->nullable()->index();

                $table->timestamps();
                $table->softDeletes();

                $table->unique(
                    ['tenant_id', 'campus_building_id', 'floor_code'],
                    'cf_building_floor_unique'
                );
            });
        }

        if (!Schema::hasTable('room_types')) {
            Schema::create('room_types', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('tenant_id')->nullable()->index();

                $table->string('code', 100)->index();
                $table->string('name', 255);

                $table->string('category_code', 100)->default('teaching')->index();
                // teaching, lab, office, exam, meeting

                $table->boolean('is_teaching_space')->default(true);
                $table->boolean('is_lab_space')->default(false);
                $table->boolean('is_exam_space')->default(false);

                $table->string('status_code', 50)->default('active')->index();

                $table->unsignedInteger('sort_order')->default(0);

                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('updated_by')->nullable()->index();

                $table->timestamps();

                $table->unique(['tenant_id', 'code'], 'rt_tenant_code_unique');
            });
        }

        if (!Schema::hasTable('rooms')) {
            Schema::create('rooms', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('tenant_id')->index();

                $table->unsignedBigInteger('campus_building_id')->nullable()->index();
                $table->unsignedBigInteger('campus_floor_id')->nullable()->index();

                $table->unsignedBigInteger('faculty_id')->nullable()->index();
                $table->unsignedBigInteger('department_id')->nullable()->index();
                $table->unsignedBigInteger('program_id')->nullable()->index();

                $table->unsignedBigInteger('room_type_id')->nullable()->index();
                $table->string('room_type_code', 100)->nullable()->index();

                $table->string('room_code', 100)->index();
                $table->string('room_name', 255);

                $table->unsignedInteger('capacity')->default(0)->index();
                $table->unsignedInteger('exam_capacity')->nullable();

                $table->boolean('has_multimedia')->default(false);
                $table->boolean('has_projector')->default(false);
                $table->boolean('has_smart_board')->default(false);
                $table->boolean('has_computers')->default(false);
                $table->unsignedInteger('computer_count')->nullable();

                $table->boolean('is_shared')->default(true)->index();
                $table->boolean('is_lab')->default(false)->index();
                $table->boolean('is_active_for_timetable')->default(true)->index();

                $table->string('status_code', 50)->default('active')->index();

                $table->text('remarks')->nullable();

                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('updated_by')->nullable()->index();

                $table->timestamps();
                $table->softDeletes();

                $table->unique(['tenant_id', 'room_code'], 'rooms_tenant_code_unique');

                $table->index(
                    [
                        'tenant_id',
                        'campus_building_id',
                        'campus_floor_id',
                        'room_type_code',
                        'capacity',
                        'is_lab',
                        'is_active_for_timetable',
                    ],
                    'rooms_timetable_lookup_idx'
                );
            });
        }

        if (!Schema::hasTable('room_features')) {
            Schema::create('room_features', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('tenant_id')->nullable()->index();

                $table->string('feature_code', 100)->index();
                $table->string('feature_name', 255);

                $table->string('feature_type_code', 100)->default('facility')->index();
                // facility, equipment, accessibility, lab_resource

                $table->string('status_code', 50)->default('active')->index();

                $table->unsignedInteger('sort_order')->default(0);

                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('updated_by')->nullable()->index();

                $table->timestamps();

                $table->unique(['tenant_id', 'feature_code'], 'rf_tenant_code_unique');
            });
        }

        if (!Schema::hasTable('room_feature_assignments')) {
            Schema::create('room_feature_assignments', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('tenant_id')->index();

                $table->unsignedBigInteger('room_id')->index();
                $table->unsignedBigInteger('room_feature_id')->index();

                $table->unsignedInteger('quantity')->nullable();
                $table->string('status_code', 50)->default('active')->index();

                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('updated_by')->nullable()->index();

                $table->timestamps();

                $table->unique(
                    ['room_id', 'room_feature_id'],
                    'rfa_room_feature_unique'
                );
            });
        }

        if (!Schema::hasTable('resource_types')) {
            Schema::create('resource_types', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('tenant_id')->nullable()->index();

                $table->string('code', 100)->index();
                $table->string('name', 255);

                $table->string('category_code', 100)->default('teaching')->index();
                // teaching, lab, exam, multimedia, equipment

                $table->boolean('is_schedulable')->default(false);
                $table->boolean('is_consumable')->default(false);

                $table->string('status_code', 50)->default('active')->index();

                $table->unsignedInteger('sort_order')->default(0);

                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('updated_by')->nullable()->index();

                $table->timestamps();

                $table->unique(['tenant_id', 'code'], 'res_type_tenant_code_unique');
            });
        }

        if (!Schema::hasTable('resources')) {
            Schema::create('resources', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('tenant_id')->index();

                $table->unsignedBigInteger('resource_type_id')->nullable()->index();

                $table->unsignedBigInteger('campus_building_id')->nullable()->index();
                $table->unsignedBigInteger('campus_floor_id')->nullable()->index();
                $table->unsignedBigInteger('room_id')->nullable()->index();

                $table->string('resource_code', 100)->index();
                $table->string('resource_name', 255);

                $table->string('resource_status_code', 100)->default('available')->index();
                // available, maintenance, retired, reserved

                $table->boolean('is_shared')->default(true)->index();
                $table->boolean('is_schedulable')->default(false)->index();

                $table->unsignedInteger('quantity')->default(1);
                $table->string('unit_code', 50)->nullable();

                $table->text('remarks')->nullable();

                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('updated_by')->nullable()->index();

                $table->timestamps();
                $table->softDeletes();

                $table->unique(['tenant_id', 'resource_code'], 'res_tenant_code_unique');
            });
        }

        if (!Schema::hasTable('room_resource_assignments')) {
            Schema::create('room_resource_assignments', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('tenant_id')->index();

                $table->unsignedBigInteger('room_id')->index();
                $table->unsignedBigInteger('resource_id')->index();

                $table->unsignedInteger('quantity')->default(1);

                $table->string('status_code', 50)->default('active')->index();

                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('updated_by')->nullable()->index();

                $table->timestamps();

                $table->unique(
                    ['room_id', 'resource_id'],
                    'rra_room_resource_unique'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('room_resource_assignments');
        Schema::dropIfExists('resources');
        Schema::dropIfExists('resource_types');
        Schema::dropIfExists('room_feature_assignments');
        Schema::dropIfExists('room_features');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('room_types');
        Schema::dropIfExists('campus_floors');
        Schema::dropIfExists('campus_buildings');
    }
};