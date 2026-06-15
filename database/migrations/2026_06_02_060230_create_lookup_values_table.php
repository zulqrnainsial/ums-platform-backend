<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lookup_values', function (Blueprint $table) {
            $table->id();

            /*
             |--------------------------------------------------------------------------
             | tenant_id nullable
             |--------------------------------------------------------------------------
             | null = global/system value
             | tenant_id = tenant-specific value
             */
            $table->foreignId('tenant_id')
                ->nullable()
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignId('lookup_category_id')
                ->constrained('lookup_categories')
                ->cascadeOnDelete();

            /*
             |--------------------------------------------------------------------------
             | parent_id
             |--------------------------------------------------------------------------
             | Used for:
             | Country → Province → City
             | Board → Institution
             | Qualification Level → Qualification/Class
             */
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('lookup_values')
                ->nullOnDelete();

            $table->string('code');
            $table->string('name');
            $table->string('short_name')->nullable();

            $table->json('extra_json')->nullable();

            $table->integer('display_order')->default(0);

            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'lookup_category_id', 'code'], 'lookup_value_unique_code');

            $table->index(['tenant_id', 'lookup_category_id', 'status'], 'lookup_value_category_status_idx');
            $table->index(['tenant_id', 'parent_id', 'status'], 'lookup_value_parent_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lookup_values');
    }
};