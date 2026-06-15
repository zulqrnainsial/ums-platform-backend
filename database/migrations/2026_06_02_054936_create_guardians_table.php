<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guardians', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();

            $table->string('name');
            $table->string('cnic')->nullable();

            $table->string('phone')->nullable();
            $table->string('alternate_phone')->nullable();
            $table->string('email')->nullable();

            $table->string('occupation')->nullable();
            $table->decimal('monthly_income', 12, 2)->nullable();

            $table->text('address')->nullable();

            $table->foreignId('country_id')->nullable()->constrained('lookup_values')->nullOnDelete();
            $table->foreignId('province_id')->nullable()->constrained('lookup_values')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('lookup_values')->nullOnDelete();

            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'cnic']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardians');
    }
};