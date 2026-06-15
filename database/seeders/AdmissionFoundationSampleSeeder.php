<?php

namespace Database\Seeders;

use App\Core\Tenant\Models\Tenant;
use App\Modules\Academic\Models\AcademicSession;
use App\Modules\Academic\Models\Program;
use App\Modules\Admission\Models\AdmissionSession;
use App\Modules\Admission\Models\Applicant;
use App\Modules\Admission\Models\ApplicantProgramApplication;
use App\Modules\Admission\Models\EligibilityRuleType;
use App\Modules\Admission\Models\OfferedProgram;
use App\Modules\Admission\Models\ProgramEligibilityRule;
use App\Modules\Admission\Models\ProgramQuotaSeat;
use App\Modules\Lookup\Models\LookupCategory;
use App\Modules\Lookup\Models\LookupValue;
use App\Modules\Student\Models\StudentBatch;
use App\Modules\Subject\Models\Curriculum;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdmissionFoundationSampleSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            Tenant::query()
                ->where('status', 'active')
                ->orderBy('id')
                ->each(function (Tenant $tenant) {
                    $this->seedForTenant($tenant);
                });
        });
    }

    private function seedForTenant(Tenant $tenant): void
    {
        $academicSession = AcademicSession::query()
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('id')
            ->first();

        if (!$academicSession) {
            $this->command->warn("Skipped {$tenant->name}: no academic session found.");
            return;
        }

        $program = Program::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('id')
            ->first();

        if (!$program) {
            $this->command->warn("Skipped {$tenant->name}: no program found.");
            return;
        }

        $curriculum = Curriculum::query()
            ->where('tenant_id', $tenant->id)
            ->where('program_id', $program->id)
            ->orderByDesc('id')
            ->first();

        $studentBatch = StudentBatch::query()
            ->where('tenant_id', $tenant->id)
            ->where('program_id', $program->id)
            ->where('academic_session_id', $academicSession->id)
            ->orderByDesc('id')
            ->first();

        $this->seedAdmissionFoundation(
            tenant: $tenant,
            academicSession: $academicSession,
            program: $program,
            curriculum: $curriculum,
            studentBatch: $studentBatch
        );
    }

    private function seedAdmissionFoundation(
        Tenant $tenant,
        AcademicSession $academicSession,
        Program $program,
        ?Curriculum $curriculum,
        ?StudentBatch $studentBatch
    ): void {
        $admissionSessionCode = 'ADM-' . ($academicSession->code ?? $academicSession->id);

        $admissionSession = AdmissionSession::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'code' => $admissionSessionCode,
            ],
            [
                'academic_session_id' => $academicSession->id,
                'name' => 'Admission ' . ($academicSession->name ?? $admissionSessionCode),
                'application_start_date' => now()->toDateString(),
                'application_end_date' => now()->addDays(45)->toDateString(),
                'document_submission_deadline' => now()->addDays(50)->toDateString(),
                'test_start_date' => now()->addDays(20)->toDateString(),
                'test_end_date' => now()->addDays(30)->toDateString(),
                'merit_list_start_date' => now()->addDays(55)->toDateString(),
                'is_current' => true,
                'admission_mode_code' => 'online',
                'status_code' => 'open',
                'description' => 'Sample admission session generated from existing academic setup.',
                'remarks' => 'Safe sample data. No existing academic data changed.',
            ]
        );

        $shift = $this->lookupValue('PROGRAM_SHIFT', 'morning');

        $offeredProgramCode = $admissionSessionCode . '-' . ($program->code ?? ('PROGRAM-' . $program->id));

        $offeredProgram = OfferedProgram::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'admission_session_id' => $admissionSession->id,
                'program_id' => $program->id,
                'shift_code' => 'morning',
            ],
            [
                'academic_session_id' => $academicSession->id,
                'campus_id' => null,
                'faculty_id' => $program->faculty_id ?? null,
                'institute_id' => $program->institute_id ?? null,
                'department_id' => $program->department_id ?? null,
                'program_level_id' => $program->program_level_id ?? null,
                'curriculum_id' => $curriculum?->id,
                'student_batch_id' => $studentBatch?->id,
                'code' => $offeredProgramCode,
                'title' => ($program->name ?? 'Program') . ' - Morning',
                'shift_id' => $shift?->id,
                'application_fee' => 1500,
                'admission_fee' => 25000,
                'requires_test' => true,
                'requires_interview' => false,
                'requires_experience' => false,
                'requires_research_profile' => false,
                'is_published' => true,
                'application_start_date' => $admissionSession->application_start_date,
                'application_end_date' => $admissionSession->application_end_date,
                'status_code' => 'open',
                'description' => 'Sample offered program linked with existing academic program.',
                'remarks' => $studentBatch
                    ? 'Linked with existing student batch.'
                    : 'No student batch found. It can be linked later.',
            ]
        );

        $openMeritQuota = $this->seedQuotaSeats($tenant, $offeredProgram);
        $this->seedEligibilityRules($tenant, $offeredProgram, $openMeritQuota);
        $this->seedSampleApplicantAndApplication($tenant, $admissionSession, $offeredProgram, $openMeritQuota);

        $this->command->info("Admission sample seeded for tenant: {$tenant->name}");
    }

    private function seedQuotaSeats(Tenant $tenant, OfferedProgram $offeredProgram): ProgramQuotaSeat
    {
        $quotaRows = [
            [
                'quota_code' => 'OPEN_MERIT',
                'quota_name' => 'Open Merit',
                'allocated_seats' => 45,
                'is_default' => true,
                'display_order' => 1,
            ],
            [
                'quota_code' => 'SELF_FINANCE',
                'quota_name' => 'Self Finance',
                'allocated_seats' => 10,
                'is_default' => false,
                'display_order' => 2,
            ],
            [
                'quota_code' => 'RESERVED',
                'quota_name' => 'Reserved Seats',
                'allocated_seats' => 5,
                'is_default' => false,
                'display_order' => 3,
            ],
        ];

        $openMerit = null;

        foreach ($quotaRows as $row) {
            $quotaType = $this->lookupValue('QUOTA_TYPE', $row['quota_code']);

            if (!$quotaType) {
                continue;
            }

            $quotaSeat = ProgramQuotaSeat::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'offered_program_id' => $offeredProgram->id,
                    'quota_type_id' => $quotaType->id,
                ],
                [
                    'quota_code' => $row['quota_code'],
                    'quota_name' => $row['quota_name'],
                    'allocated_seats' => $row['allocated_seats'],
                    'filled_seats' => 0,
                    'available_seats' => $row['allocated_seats'],
                    'application_fee' => $offeredProgram->application_fee,
                    'admission_fee' => $offeredProgram->admission_fee,
                    'is_default' => $row['is_default'],
                    'is_active' => true,
                    'display_order' => $row['display_order'],
                    'eligibility_notes' => null,
                    'remarks' => 'Sample quota seat setup.',
                ]
            );

            if ($row['quota_code'] === 'OPEN_MERIT') {
                $openMerit = $quotaSeat;
            }
        }

        return $openMerit ?? ProgramQuotaSeat::where('tenant_id', $tenant->id)
            ->where('offered_program_id', $offeredProgram->id)
            ->orderBy('display_order')
            ->firstOrFail();
    }

    private function seedEligibilityRules(
        Tenant $tenant,
        OfferedProgram $offeredProgram,
        ProgramQuotaSeat $quotaSeat
    ): void {
        $intermediate = $this->lookupValue('QUALIFICATION_LEVEL', 'INTERMEDIATE');

        $rules = [
            [
                'type_code' => 'QUALIFICATION_LEVEL_REQUIRED',
                'rule_code' => 'REQUIRE_INTERMEDIATE',
                'rule_title' => 'Intermediate Required',
                'operator' => 'exists',
                'value_text' => null,
                'value_number' => null,
                'target_qualification_level_id' => $intermediate?->id,
                'failure_message' => 'Intermediate or equivalent qualification is required.',
                'display_order' => 1,
            ],
            [
                'type_code' => 'MINIMUM_PERCENTAGE',
                'rule_code' => 'INTERMEDIATE_MIN_50_PERCENT',
                'rule_title' => 'Minimum 50% in Intermediate',
                'operator' => '>=',
                'value_text' => null,
                'value_number' => 50,
                'target_qualification_level_id' => $intermediate?->id,
                'failure_message' => 'Minimum 50% marks are required in Intermediate or equivalent qualification.',
                'display_order' => 2,
            ],
            [
                'type_code' => 'MAXIMUM_AGE',
                'rule_code' => 'MAX_AGE_25',
                'rule_title' => 'Maximum Age 25 Years',
                'operator' => '<=',
                'value_text' => null,
                'value_number' => 25,
                'target_qualification_level_id' => null,
                'failure_message' => 'Maximum age limit is 25 years.',
                'display_order' => 3,
            ],
            [
                'type_code' => 'TEST_REQUIRED',
                'rule_code' => 'ADMISSION_TEST_REQUIRED',
                'rule_title' => 'Admission Test Required',
                'operator' => 'exists',
                'value_text' => 'TENANT_ADMISSION_TEST',
                'value_number' => null,
                'target_qualification_level_id' => null,
                'failure_message' => 'Admission test is required for this program.',
                'display_order' => 4,
            ],
        ];

        foreach ($rules as $row) {
            $type = EligibilityRuleType::withoutGlobalScopes()
                ->whereNull('tenant_id')
                ->where('code', $row['type_code'])
                ->where('is_active', true)
                ->first();

            if (!$type) {
                continue;
            }

            ProgramEligibilityRule::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'offered_program_id' => $offeredProgram->id,
                    'program_quota_seat_id' => null,
                    'rule_code' => $row['rule_code'],
                ],
                [
                    'eligibility_rule_type_id' => $type->id,
                    'rule_group' => 'basic',
                    'rule_title' => $row['rule_title'],
                    'operator' => $row['operator'],
                    'value_text' => $row['value_text'],
                    'value_number' => $row['value_number'],
                    'value_date' => null,
                    'value_lookup_id' => null,
                    'value_json' => null,
                    'target_qualification_level_id' => $row['target_qualification_level_id'],
                    'target_subject_group_id' => null,
                    'target_document_type_id' => null,
                    'target_test_code' => $row['type_code'] === 'TEST_REQUIRED' ? 'TENANT_ADMISSION_TEST' : null,
                    'is_mandatory' => true,
                    'is_active' => true,
                    'failure_message' => $row['failure_message'],
                    'description' => 'Sample eligibility rule for admission foundation.',
                    'display_order' => $row['display_order'],
                ]
            );
        }

        $this->seedQuotaSpecificRule($tenant, $offeredProgram, $quotaSeat);
    }

    private function seedQuotaSpecificRule(
        Tenant $tenant,
        OfferedProgram $offeredProgram,
        ProgramQuotaSeat $quotaSeat
    ): void {
        $type = EligibilityRuleType::withoutGlobalScopes()
            ->whereNull('tenant_id')
            ->where('code', 'QUALIFICATION_LEVEL_REQUIRED')
            ->where('is_active', true)
            ->first();

        $intermediate = $this->lookupValue('QUALIFICATION_LEVEL', 'INTERMEDIATE');

        if (!$type || !$intermediate) {
            return;
        }

        ProgramEligibilityRule::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'offered_program_id' => $offeredProgram->id,
                'program_quota_seat_id' => $quotaSeat->id,
                'rule_code' => 'OPEN_MERIT_REQUIRE_INTERMEDIATE',
            ],
            [
                'eligibility_rule_type_id' => $type->id,
                'rule_group' => 'quota',
                'rule_title' => 'Open Merit Intermediate Required',
                'operator' => 'exists',
                'value_text' => null,
                'value_number' => null,
                'value_date' => null,
                'value_lookup_id' => null,
                'value_json' => null,
                'target_qualification_level_id' => $intermediate->id,
                'target_subject_group_id' => null,
                'target_document_type_id' => null,
                'target_test_code' => null,
                'is_mandatory' => true,
                'is_active' => true,
                'failure_message' => 'Intermediate qualification is required for Open Merit quota.',
                'description' => 'Sample quota-specific eligibility rule.',
                'display_order' => 1,
            ]
        );
    }

    private function seedSampleApplicantAndApplication(
        Tenant $tenant,
        AdmissionSession $admissionSession,
        OfferedProgram $offeredProgram,
        ProgramQuotaSeat $quotaSeat
    ): void {
        $country = $this->lookupValue('COUNTRY', 'PAKISTAN');
        $province = $this->lookupValue('PROVINCE', 'PUNJAB');
        $city = $this->lookupValue('CITY', 'MULTAN');
        $nationality = $this->lookupValue('NATIONALITY', 'PAKISTANI');
        $religion = $this->lookupValue('RELIGION', 'ISLAM');
        $bloodGroup = $this->lookupValue('BLOOD_GROUP', 'A_POS');

        $applicant = Applicant::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'applicant_no' => 'APP-0001',
            ],
            [
                'user_id' => null,
                'application_account_no' => 'ACC-APP-0001',
                'first_name' => 'Ahmed',
                'last_name' => 'Ali',
                'father_name' => 'Muhammad Ali',
                'mother_name' => 'Fatima',
                'cnic_bform' => '31203-1111111-1',
                'passport_no' => null,
                'date_of_birth' => now()->subYears(19)->toDateString(),
                'gender' => 'male',
                'nationality_id' => $nationality?->id,
                'religion_id' => $religion?->id,
                'blood_group_id' => $bloodGroup?->id,
                'email' => 'ahmed.ali.applicant@example.com',
                'phone' => '03001111111',
                'alternate_phone' => '03002222222',
                'current_address' => 'Multan, Punjab',
                'permanent_address' => 'Multan, Punjab',
                'country_id' => $country?->id,
                'province_id' => $province?->id,
                'city_id' => $city?->id,
                'domicile_province_id' => $province?->id,
                'domicile_district_id' => $city?->id,
                'has_disability' => false,
                'disability_type_id' => null,
                'has_experience' => false,
                'has_research_profile' => false,
                'has_publications' => false,
                'photo_path' => null,
                'profile_status_code' => 'completed',
                'applicant_status_code' => 'active',
                'profile_completed_at' => now(),
                'submitted_at' => now(),
                'remarks' => 'Sample applicant for admission foundation testing.',
            ]
        );

        ApplicantProgramApplication::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'applicant_id' => $applicant->id,
                'offered_program_id' => $offeredProgram->id,
                'program_quota_seat_id' => $quotaSeat->id,
            ],
            [
                'admission_session_id' => $admissionSession->id,
                'application_no' => 'APPL-0001',
                'preference_order' => 1,
                'eligibility_status_code' => 'pending',
                'eligibility_result_json' => null,
                'eligibility_remarks' => null,
                'application_status_code' => 'submitted',
                'document_status_code' => 'pending',
                'fee_status_code' => 'unpaid',
                'test_status_code' => $offeredProgram->requires_test ? 'required' : 'not_required',
                'merit_score' => null,
                'merit_rank' => null,
                'submitted_at' => now(),
                'reviewed_at' => null,
                'reviewed_by' => null,
                'confirmed_at' => null,
                'remarks' => 'Sample program application.',
            ]
        );
    }

    private function lookupValue(string $categoryCode, string $valueCode): ?LookupValue
    {
        $category = LookupCategory::withoutGlobalScopes()
            ->where('code', $categoryCode)
            ->where('status', 'active')
            ->first();

        if (!$category) {
            return null;
        }

        return LookupValue::withoutGlobalScopes()
            ->where('lookup_category_id', $category->id)
            ->where('code', $valueCode)
            ->where('status', 'active')
            ->first();
    }
}