<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applicants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('applicant_no')->nullable();
            $table->string('application_account_no')->nullable();

            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('full_name')->nullable();

            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();

            $table->string('cnic_bform')->nullable();
            $table->string('passport_no')->nullable();

            $table->date('date_of_birth')->nullable();

            $table->string('gender')->nullable();

            $table->foreignId('nationality_id')->nullable()->constrained('lookup_values')->nullOnDelete();
            $table->foreignId('religion_id')->nullable()->constrained('lookup_values')->nullOnDelete();
            $table->foreignId('blood_group_id')->nullable()->constrained('lookup_values')->nullOnDelete();

            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('alternate_phone')->nullable();

            $table->text('current_address')->nullable();
            $table->text('permanent_address')->nullable();

            $table->foreignId('country_id')->nullable()->constrained('lookup_values')->nullOnDelete();
            $table->foreignId('province_id')->nullable()->constrained('lookup_values')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('lookup_values')->nullOnDelete();

            $table->foreignId('domicile_province_id')->nullable()->constrained('lookup_values')->nullOnDelete();
            $table->foreignId('domicile_district_id')->nullable()->constrained('lookup_values')->nullOnDelete();

            $table->boolean('has_disability')->default(false);
            $table->foreignId('disability_type_id')->nullable()->constrained('lookup_values')->nullOnDelete();

            $table->boolean('has_experience')->default(false);
            $table->boolean('has_research_profile')->default(false);
            $table->boolean('has_publications')->default(false);

            $table->string('photo_path')->nullable();

            $table->string('profile_status_code')->default('draft');
            $table->string('applicant_status_code')->default('active');

            $table->timestamp('profile_completed_at')->nullable();
            $table->timestamp('submitted_at')->nullable();

            $table->text('remarks')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'applicant_no']);
            $table->unique(['tenant_id', 'cnic_bform']);
            $table->unique(['tenant_id', 'email']);

            $table->index(['tenant_id', 'profile_status_code']);
            $table->index(['tenant_id', 'applicant_status_code']);
            $table->index(['tenant_id', 'gender']);
            $table->index(['tenant_id', 'city_id']);
            $table->index(['tenant_id', 'domicile_province_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applicants');
    }
};