<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('code')->unique();

            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            $table->string('logo')->nullable();
            $table->string('theme_color')->nullable();

            $table->string('timezone')->default('Asia/Karachi');
            $table->string('locale')->default('en');

            $table->enum('status', [
                'active',
                'inactive',
                'pending',
                'suspended',
                'archived'
            ])->default('active');

            $table->enum('subscription_status', [
                'trial',
                'active',
                'expired',
                'cancelled',
                'suspended'
            ])->default('trial');

            $table->date('subscription_start_date')->nullable();
            $table->date('subscription_end_date')->nullable();

            $table->json('meta')->nullable();

            //$table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            //$table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'subscription_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};