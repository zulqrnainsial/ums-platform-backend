<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('course_registration_settings')) {
            Schema::create('course_registration_settings', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('academic_session_id')->nullable()->index();
                $table->unsignedBigInteger('program_id')->nullable()->index();
                $table->unsignedBigInteger('academic_term_id')->nullable()->index();

                $table->boolean('student_self_registration_enabled')->default(false);
                $table->timestamp('registration_start_at')->nullable();
                $table->timestamp('registration_end_at')->nullable();

                $table->boolean('requires_admin_approval')->default(true);
                $table->boolean('allow_add_drop')->default(false);
                $table->timestamp('add_drop_start_at')->nullable();
                $table->timestamp('add_drop_end_at')->nullable();

                $table->decimal('min_credit_hours', 5, 2)->nullable();
                $table->decimal('max_credit_hours', 5, 2)->nullable();

                $table->string('status_code', 50)->default('active')->index();

                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('updated_by')->nullable()->index();

                $table->timestamps();

                $table->unique(
                    ['tenant_id', 'academic_session_id', 'program_id', 'academic_term_id'],
                    'crs_unique_scope'
                );
            });
        }

        if (Schema::hasTable('student_course_registrations')) {
            Schema::table('student_course_registrations', function (Blueprint $table) {
                if (!Schema::hasColumn('student_course_registrations', 'student_batch_id')) {
                    $table->unsignedBigInteger('student_batch_id')->nullable()->index()->after('academic_term_id');
                }

                if (!Schema::hasColumn('student_course_registrations', 'section')) {
                    $table->string('section', 80)->nullable()->index()->after('student_batch_id');
                }

                if (!Schema::hasColumn('student_course_registrations', 'registration_source')) {
                    $table->string('registration_source', 50)->default('admin_manual')->index()->after('registration_type');
                }

                if (!Schema::hasColumn('student_course_registrations', 'requested_by')) {
                    $table->unsignedBigInteger('requested_by')->nullable()->index()->after('registered_at');
                }

                if (!Schema::hasColumn('student_course_registrations', 'approved_by')) {
                    $table->unsignedBigInteger('approved_by')->nullable()->index()->after('requested_by');
                }

                if (!Schema::hasColumn('student_course_registrations', 'approved_at')) {
                    $table->timestamp('approved_at')->nullable()->after('approved_by');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('student_course_registrations')) {
            Schema::table('student_course_registrations', function (Blueprint $table) {
                foreach ([
                    'approved_at',
                    'approved_by',
                    'requested_by',
                    'registration_source',
                    'section',
                    'student_batch_id',
                ] as $column) {
                    if (Schema::hasColumn('student_course_registrations', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        Schema::dropIfExists('course_registration_settings');
    }
};