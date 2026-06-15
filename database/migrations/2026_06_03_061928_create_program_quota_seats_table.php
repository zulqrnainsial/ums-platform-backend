<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_quota_seats', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();

            $table->foreignId('offered_program_id')
                ->constrained('offered_programs')
                ->cascadeOnDelete();

            $table->foreignId('quota_type_id')
                ->constrained('lookup_values')
                ->restrictOnDelete();

            $table->string('quota_code');
            $table->string('quota_name');

            $table->integer('allocated_seats')->default(0);
            $table->integer('filled_seats')->default(0);
            $table->integer('available_seats')->default(0);

            $table->decimal('application_fee', 12, 2)->nullable();
            $table->decimal('admission_fee', 12, 2)->nullable();

            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);

            $table->integer('display_order')->default(0);

            $table->text('eligibility_notes')->nullable();
            $table->text('remarks')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['tenant_id', 'offered_program_id', 'quota_type_id'],
                'program_quota_unique'
            );

            $table->index(['tenant_id', 'offered_program_id']);
            $table->index(['tenant_id', 'quota_type_id']);
            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_quota_seats');
    }
};