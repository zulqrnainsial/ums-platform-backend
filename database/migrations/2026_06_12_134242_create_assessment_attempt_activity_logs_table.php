<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_attempt_activity_logs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('assessment_attempt_id')->index();
            $table->unsignedBigInteger('assessment_participant_id')->nullable()->index();
            $table->unsignedBigInteger('assessment_id')->nullable()->index();
            $table->unsignedBigInteger('assessment_schedule_id')->nullable()->index();
            $table->unsignedBigInteger('applicant_id')->nullable()->index();

            $table->string('event_code', 80)->index();
            $table->string('severity_code', 30)->default('info')->index();

            $table->unsignedBigInteger('assessment_question_id')->nullable()->index();
            $table->unsignedBigInteger('question_id')->nullable()->index();

            $table->json('event_payload_json')->nullable();

            $table->string('ip_address', 80)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamp('occurred_at')->nullable()->index();

            $table->timestamps();

            $table->index(['tenant_id', 'assessment_attempt_id', 'event_code'], 'aatal_tenant_attempt_event_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_attempt_activity_logs');
    }
};