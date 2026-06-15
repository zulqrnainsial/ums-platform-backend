<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_sessions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();

            $table->foreignId('academic_session_id')
                ->nullable()
                ->constrained('academic_sessions')
                ->nullOnDelete();

            $table->string('code');
            $table->string('name');

            $table->date('application_start_date')->nullable();
            $table->date('application_end_date')->nullable();
            $table->date('document_submission_deadline')->nullable();
            $table->date('test_start_date')->nullable();
            $table->date('test_end_date')->nullable();
            $table->date('merit_list_start_date')->nullable();

            $table->boolean('is_current')->default(false);

            /*
             | No ENUM.
             | Later these can be managed through lookup/system settings.
             */
            $table->string('admission_mode_code')->default('online'); // online/offline/both
            $table->string('status_code')->default('draft'); // draft/open/closed/processing/completed/archived

            $table->text('description')->nullable();
            $table->text('remarks')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'academic_session_id']);
            $table->index(['tenant_id', 'status_code']);
            $table->index(['tenant_id', 'is_current']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_sessions');
    }
};