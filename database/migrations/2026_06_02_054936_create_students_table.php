<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();

            $table->string('student_no')->nullable();
            $table->string('admission_no')->nullable();

            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('full_name')->nullable();

            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();

            $table->string('cnic_bform')->nullable();
            $table->string('passport_no')->nullable();

            $table->date('date_of_birth')->nullable();

            /*
             |--------------------------------------------------------------------------
             | Dynamic lookup fields
             |--------------------------------------------------------------------------
             | gender_id can point to lookup value if later you create GENDER category.
             | For now gender is kept as enum because it is stable and used very often.
             */
            $table->enum('gender', ['male', 'female', 'other'])->nullable();

            $table->foreignId('blood_group_id')->nullable()->constrained('lookup_values')->nullOnDelete();
            $table->foreignId('religion_id')->nullable()->constrained('lookup_values')->nullOnDelete();
            $table->foreignId('nationality_id')->nullable()->constrained('lookup_values')->nullOnDelete();

            $table->string('phone')->nullable();
            $table->string('alternate_phone')->nullable();
            $table->string('email')->nullable();

            $table->text('current_address')->nullable();
            $table->text('permanent_address')->nullable();

            $table->foreignId('country_id')->nullable()->constrained('lookup_values')->nullOnDelete();
            $table->foreignId('province_id')->nullable()->constrained('lookup_values')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('lookup_values')->nullOnDelete();

            $table->string('photo_path')->nullable();

            $table->date('admission_date')->nullable();

            $table->enum('student_status', [
                'applicant',
                'active',
                'inactive',
                'graduated',
                'left',
                'struck_off',
                'suspended',
                'transferred'
            ])->default('active');

            $table->text('remarks')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'student_no']);
            $table->unique(['tenant_id', 'admission_no']);

            $table->index(['tenant_id', 'student_status']);
            $table->index(['tenant_id', 'first_name', 'last_name']);
            $table->index(['tenant_id', 'cnic_bform']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};