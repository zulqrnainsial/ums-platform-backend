<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('dynamic_backfill_mappings')) {
            return;
        }

        Schema::create('dynamic_backfill_mappings', function (Blueprint $table) {
            $table->id();

            $table->string('module_code', 80)->default('admission');

            $table->string('source_table', 120);
            $table->string('source_column', 120);
            $table->string('source_value', 255);

            $table->string('target_table', 120);
            $table->string('target_column', 120);
            $table->unsignedBigInteger('target_id');

            $table->string('target_label', 255)->nullable();

            $table->boolean('is_approved')->default(false);
            $table->string('status_code', 60)->default('active');

            $table->text('notes')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(
                [
                    'source_table',
                    'source_column',
                    'source_value',
                    'target_table',
                    'target_column',
                ],
                'dyn_backfill_mapping_unique'
            );

            $table->index(['source_table', 'source_column'], 'dyn_backfill_source_idx');
            $table->index(['target_table', 'target_column'], 'dyn_backfill_target_idx');
            $table->index(['is_approved', 'status_code'], 'dyn_backfill_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dynamic_backfill_mappings');
    }
};