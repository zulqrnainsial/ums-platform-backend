<?php

namespace Database\Seeders;

use App\Modules\Admission\Models\EligibilityRuleType;
use App\Modules\Lookup\Models\LookupCategory;
use App\Modules\Lookup\Models\LookupValue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdmissionFoundationLookupSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->seedLookupCategoriesAndValues();
            $this->seedEligibilityRuleTypes();
        });

        $this->command->info('Admission foundation lookups seeded successfully.');
    }

    private function seedLookupCategoriesAndValues(): void
    {
        $categories = [
            'ADMISSION_MODE' => [
                'name' => 'Admission Mode',
                'description' => 'Admission application mode.',
                'display_order' => 100,
                'values' => [
                    ['code' => 'online', 'name' => 'Online', 'display_order' => 1],
                    ['code' => 'offline', 'name' => 'Offline', 'display_order' => 2],
                    ['code' => 'both', 'name' => 'Both Online and Offline', 'display_order' => 3],
                ],
            ],

            'ADMISSION_SESSION_STATUS' => [
                'name' => 'Admission Session Status',
                'description' => 'Admission session workflow statuses.',
                'display_order' => 101,
                'values' => [
                    ['code' => 'draft', 'name' => 'Draft', 'display_order' => 1],
                    ['code' => 'open', 'name' => 'Open', 'display_order' => 2],
                    ['code' => 'closed', 'name' => 'Closed', 'display_order' => 3],
                    ['code' => 'processing', 'name' => 'Processing', 'display_order' => 4],
                    ['code' => 'completed', 'name' => 'Completed', 'display_order' => 5],
                    ['code' => 'archived', 'name' => 'Archived', 'display_order' => 6],
                ],
            ],

            'OFFERED_PROGRAM_STATUS' => [
                'name' => 'Offered Program Status',
                'description' => 'Status of programs offered in admission sessions.',
                'display_order' => 102,
                'values' => [
                    ['code' => 'draft', 'name' => 'Draft', 'display_order' => 1],
                    ['code' => 'open', 'name' => 'Open', 'display_order' => 2],
                    ['code' => 'closed', 'name' => 'Closed', 'display_order' => 3],
                    ['code' => 'suspended', 'name' => 'Suspended', 'display_order' => 4],
                    ['code' => 'completed', 'name' => 'Completed', 'display_order' => 5],
                ],
            ],

            'PROGRAM_SHIFT' => [
                'name' => 'Program Shift',
                'description' => 'Program shift options.',
                'display_order' => 103,
                'values' => [
                    ['code' => 'morning', 'name' => 'Morning', 'display_order' => 1],
                    ['code' => 'evening', 'name' => 'Evening', 'display_order' => 2],
                    ['code' => 'weekend', 'name' => 'Weekend', 'display_order' => 3],
                    ['code' => 'online', 'name' => 'Online', 'display_order' => 4],
                    ['code' => 'other', 'name' => 'Other', 'display_order' => 5],
                ],
            ],

            'QUOTA_TYPE' => [
                'name' => 'Quota Type',
                'description' => 'Admission quota types.',
                'display_order' => 104,
                'values' => [
                    ['code' => 'OPEN_MERIT', 'name' => 'Open Merit', 'display_order' => 1],
                    ['code' => 'SELF_FINANCE', 'name' => 'Self Finance', 'display_order' => 2],
                    ['code' => 'RESERVED', 'name' => 'Reserved Seats', 'display_order' => 3],
                    ['code' => 'DISABLED', 'name' => 'Disabled Quota', 'display_order' => 4],
                    ['code' => 'SPORTS', 'name' => 'Sports Quota', 'display_order' => 5],
                    ['code' => 'EMPLOYEE_WARD', 'name' => 'Employee Ward', 'display_order' => 6],
                    ['code' => 'FOREIGN', 'name' => 'Foreign Student', 'display_order' => 7],
                    ['code' => 'MINORITY', 'name' => 'Minority Quota', 'display_order' => 8],
                    ['code' => 'PROVINCIAL_QUOTA', 'name' => 'Provincial Quota', 'display_order' => 9],
                ],
            ],

            'APPLICATION_STATUS' => [
                'name' => 'Application Status',
                'description' => 'Applicant program application statuses.',
                'display_order' => 105,
                'values' => [
                    ['code' => 'draft', 'name' => 'Draft', 'display_order' => 1],
                    ['code' => 'submitted', 'name' => 'Submitted', 'display_order' => 2],
                    ['code' => 'under_review', 'name' => 'Under Review', 'display_order' => 3],
                    ['code' => 'deficient', 'name' => 'Deficient', 'display_order' => 4],
                    ['code' => 'eligible', 'name' => 'Eligible', 'display_order' => 5],
                    ['code' => 'not_eligible', 'name' => 'Not Eligible', 'display_order' => 6],
                    ['code' => 'test_required', 'name' => 'Test Required', 'display_order' => 7],
                    ['code' => 'test_passed', 'name' => 'Test Passed', 'display_order' => 8],
                    ['code' => 'test_failed', 'name' => 'Test Failed', 'display_order' => 9],
                    ['code' => 'merit_pending', 'name' => 'Merit Pending', 'display_order' => 10],
                    ['code' => 'selected', 'name' => 'Selected', 'display_order' => 11],
                    ['code' => 'waiting', 'name' => 'Waiting', 'display_order' => 12],
                    ['code' => 'rejected', 'name' => 'Rejected', 'display_order' => 13],
                    ['code' => 'offered', 'name' => 'Offered', 'display_order' => 14],
                    ['code' => 'offer_accepted', 'name' => 'Offer Accepted', 'display_order' => 15],
                    ['code' => 'offer_expired', 'name' => 'Offer Expired', 'display_order' => 16],
                    ['code' => 'admission_confirmed', 'name' => 'Admission Confirmed', 'display_order' => 17],
                    ['code' => 'cancelled', 'name' => 'Cancelled', 'display_order' => 18],
                ],
            ],

            'ELIGIBILITY_STATUS' => [
                'name' => 'Eligibility Status',
                'description' => 'Eligibility evaluation statuses.',
                'display_order' => 106,
                'values' => [
                    ['code' => 'pending', 'name' => 'Pending', 'display_order' => 1],
                    ['code' => 'eligible', 'name' => 'Eligible', 'display_order' => 2],
                    ['code' => 'not_eligible', 'name' => 'Not Eligible', 'display_order' => 3],
                    ['code' => 'conditionally_eligible', 'name' => 'Conditionally Eligible', 'display_order' => 4],
                ],
            ],

            'DOCUMENT_STATUS' => [
                'name' => 'Document Status',
                'description' => 'Document submission and verification statuses.',
                'display_order' => 107,
                'values' => [
                    ['code' => 'pending', 'name' => 'Pending', 'display_order' => 1],
                    ['code' => 'not_required', 'name' => 'Not Required', 'display_order' => 2],
                    ['code' => 'submitted', 'name' => 'Submitted', 'display_order' => 3],
                    ['code' => 'verified', 'name' => 'Verified', 'display_order' => 4],
                    ['code' => 'rejected', 'name' => 'Rejected', 'display_order' => 5],
                    ['code' => 'deficient', 'name' => 'Deficient', 'display_order' => 6],
                ],
            ],

            'FEE_STATUS' => [
                'name' => 'Fee Status',
                'description' => 'Application/admission fee statuses.',
                'display_order' => 108,
                'values' => [
                    ['code' => 'not_required', 'name' => 'Not Required', 'display_order' => 1],
                    ['code' => 'unpaid', 'name' => 'Unpaid', 'display_order' => 2],
                    ['code' => 'partially_paid', 'name' => 'Partially Paid', 'display_order' => 3],
                    ['code' => 'paid', 'name' => 'Paid', 'display_order' => 4],
                    ['code' => 'verified', 'name' => 'Verified', 'display_order' => 5],
                    ['code' => 'refunded', 'name' => 'Refunded', 'display_order' => 6],
                ],
            ],

            'TEST_STATUS' => [
                'name' => 'Test Status',
                'description' => 'Applicant admission test statuses.',
                'display_order' => 109,
                'values' => [
                    ['code' => 'not_required', 'name' => 'Not Required', 'display_order' => 1],
                    ['code' => 'required', 'name' => 'Required', 'display_order' => 2],
                    ['code' => 'scheduled', 'name' => 'Scheduled', 'display_order' => 3],
                    ['code' => 'appeared', 'name' => 'Appeared', 'display_order' => 4],
                    ['code' => 'passed', 'name' => 'Passed', 'display_order' => 5],
                    ['code' => 'failed', 'name' => 'Failed', 'display_order' => 6],
                    ['code' => 'absent', 'name' => 'Absent', 'display_order' => 7],
                ],
            ],

            'PROFILE_STATUS' => [
                'name' => 'Applicant Profile Status',
                'description' => 'Applicant profile completion and review statuses.',
                'display_order' => 110,
                'values' => [
                    ['code' => 'draft', 'name' => 'Draft', 'display_order' => 1],
                    ['code' => 'incomplete', 'name' => 'Incomplete', 'display_order' => 2],
                    ['code' => 'completed', 'name' => 'Completed', 'display_order' => 3],
                    ['code' => 'submitted', 'name' => 'Submitted', 'display_order' => 4],
                    ['code' => 'under_review', 'name' => 'Under Review', 'display_order' => 5],
                    ['code' => 'verified', 'name' => 'Verified', 'display_order' => 6],
                    ['code' => 'rejected', 'name' => 'Rejected', 'display_order' => 7],
                ],
            ],

            'APPLICANT_STATUS' => [
                'name' => 'Applicant Status',
                'description' => 'Applicant account/status codes.',
                'display_order' => 111,
                'values' => [
                    ['code' => 'active', 'name' => 'Active', 'display_order' => 1],
                    ['code' => 'inactive', 'name' => 'Inactive', 'display_order' => 2],
                    ['code' => 'blocked', 'name' => 'Blocked', 'display_order' => 3],
                    ['code' => 'converted_to_student', 'name' => 'Converted to Student', 'display_order' => 4],
                ],
            ],
        ];

        foreach ($categories as $categoryCode => $categoryData) {
            $category = $this->upsertCategory(
                $categoryCode,
                $categoryData['name'],
                $categoryData['description'],
                $categoryData['display_order']
            );

            foreach ($categoryData['values'] as $valueData) {
                $this->upsertValue(
                    $category->id,
                    $valueData['code'],
                    $valueData['name'],
                    $valueData['display_order']
                );
            }
        }
    }

    private function upsertCategory(
        string $code,
        string $name,
        string $description,
        int $displayOrder
    ): LookupCategory {
        $payload = [
            'name' => $name,
            'description' => $description,
            'is_system' => true,
            'is_tenant_editable' => true,
            'display_order' => $displayOrder,
            'status' => 'active',
        ];

        $category = LookupCategory::withoutGlobalScopes()->updateOrCreate(
            ['code' => $code],
            $payload
        );

        if (Schema::hasColumn('lookup_categories', 'tenant_id')) {
            DB::table('lookup_categories')
                ->where('id', $category->id)
                ->update(['tenant_id' => null]);
        }

        return $category->refresh();
    }

    private function upsertValue(
        int $categoryId,
        string $code,
        string $name,
        int $displayOrder
    ): LookupValue {
        return LookupValue::withoutGlobalScopes()->updateOrCreate(
            [
                'tenant_id' => null,
                'lookup_category_id' => $categoryId,
                'code' => $code,
            ],
            [
                'parent_id' => null,
                'name' => $name,
                'display_order' => $displayOrder,
                'status' => 'active',
            ]
        );
    }

    private function seedEligibilityRuleTypes(): void
    {
        $ruleTypes = [
            [
                'code' => 'QUALIFICATION_LEVEL_REQUIRED',
                'name' => 'Qualification Level Required',
                'source_area' => 'qualification',
                'source_collection' => 'applicant_qualifications',
                'source_field' => 'qualification_level_id',
                'expected_value_type' => 'lookup',
                'evaluator_key' => 'qualification_exists',
                'description' => 'Checks whether applicant has the required qualification level.',
                'display_order' => 1,
            ],
            [
                'code' => 'MINIMUM_PERCENTAGE',
                'name' => 'Minimum Percentage',
                'source_area' => 'qualification',
                'source_collection' => 'applicant_qualifications',
                'source_field' => 'percentage',
                'expected_value_type' => 'number',
                'evaluator_key' => 'qualification_numeric_compare',
                'description' => 'Checks whether applicant qualification percentage meets minimum requirement.',
                'display_order' => 2,
            ],
            [
                'code' => 'MINIMUM_MARKS',
                'name' => 'Minimum Marks',
                'source_area' => 'qualification',
                'source_collection' => 'applicant_qualifications',
                'source_field' => 'obtained_marks',
                'expected_value_type' => 'number',
                'evaluator_key' => 'qualification_numeric_compare',
                'description' => 'Checks obtained marks in required qualification.',
                'display_order' => 3,
            ],
            [
                'code' => 'MINIMUM_CGPA',
                'name' => 'Minimum CGPA',
                'source_area' => 'qualification',
                'source_collection' => 'applicant_qualifications',
                'source_field' => 'cgpa',
                'expected_value_type' => 'number',
                'evaluator_key' => 'qualification_numeric_compare',
                'description' => 'Checks applicant qualification CGPA.',
                'display_order' => 4,
            ],
            [
                'code' => 'REQUIRED_SUBJECT_GROUP',
                'name' => 'Required Subject Group',
                'source_area' => 'qualification',
                'source_collection' => 'applicant_qualifications',
                'source_field' => 'subject_group_id',
                'expected_value_type' => 'lookup',
                'evaluator_key' => 'qualification_lookup_match',
                'description' => 'Checks required subject group in previous qualification.',
                'display_order' => 5,
            ],
            [
                'code' => 'MAXIMUM_AGE',
                'name' => 'Maximum Age',
                'source_area' => 'applicant',
                'source_collection' => 'applicants',
                'source_field' => 'date_of_birth',
                'expected_value_type' => 'number',
                'evaluator_key' => 'age_compare',
                'description' => 'Checks maximum applicant age.',
                'display_order' => 6,
            ],
            [
                'code' => 'MINIMUM_AGE',
                'name' => 'Minimum Age',
                'source_area' => 'applicant',
                'source_collection' => 'applicants',
                'source_field' => 'date_of_birth',
                'expected_value_type' => 'number',
                'evaluator_key' => 'age_compare',
                'description' => 'Checks minimum applicant age.',
                'display_order' => 7,
            ],
            [
                'code' => 'GENDER_ALLOWED',
                'name' => 'Gender Allowed',
                'source_area' => 'applicant',
                'source_collection' => 'applicants',
                'source_field' => 'gender',
                'expected_value_type' => 'string',
                'evaluator_key' => 'applicant_field_match',
                'description' => 'Checks allowed gender for a program or quota.',
                'display_order' => 8,
            ],
            [
                'code' => 'NATIONALITY_ALLOWED',
                'name' => 'Nationality Allowed',
                'source_area' => 'applicant',
                'source_collection' => 'applicants',
                'source_field' => 'nationality_id',
                'expected_value_type' => 'lookup',
                'evaluator_key' => 'applicant_lookup_match',
                'description' => 'Checks applicant nationality.',
                'display_order' => 9,
            ],
            [
                'code' => 'DOMICILE_REQUIRED',
                'name' => 'Domicile Required',
                'source_area' => 'applicant',
                'source_collection' => 'applicants',
                'source_field' => 'domicile_district_id',
                'expected_value_type' => 'lookup',
                'evaluator_key' => 'applicant_lookup_match',
                'description' => 'Checks applicant domicile requirement.',
                'display_order' => 10,
            ],
            [
                'code' => 'TEST_REQUIRED',
                'name' => 'Test Required',
                'source_area' => 'test',
                'source_collection' => 'applicant_test_results',
                'source_field' => 'test_code',
                'expected_value_type' => 'string',
                'evaluator_key' => 'test_exists',
                'description' => 'Checks whether an applicant has required test result.',
                'display_order' => 11,
            ],
            [
                'code' => 'MINIMUM_TEST_SCORE',
                'name' => 'Minimum Test Score',
                'source_area' => 'test',
                'source_collection' => 'applicant_test_results',
                'source_field' => 'score',
                'expected_value_type' => 'number',
                'evaluator_key' => 'test_numeric_compare',
                'description' => 'Checks minimum score in admission or external test.',
                'display_order' => 12,
            ],
            [
                'code' => 'DOCUMENT_REQUIRED',
                'name' => 'Document Required',
                'source_area' => 'document',
                'source_collection' => 'applicant_documents',
                'source_field' => 'document_type_id',
                'expected_value_type' => 'lookup',
                'evaluator_key' => 'document_exists',
                'description' => 'Checks whether required document is uploaded.',
                'display_order' => 13,
            ],
            [
                'code' => 'EXPERIENCE_REQUIRED',
                'name' => 'Experience Required',
                'source_area' => 'experience',
                'source_collection' => 'applicant_experiences',
                'source_field' => 'total_months',
                'expected_value_type' => 'number',
                'evaluator_key' => 'experience_compare',
                'description' => 'Checks applicant work experience.',
                'display_order' => 14,
            ],
            [
                'code' => 'RESEARCH_PROFILE_REQUIRED',
                'name' => 'Research Profile Required',
                'source_area' => 'research',
                'source_collection' => 'applicant_research_profiles',
                'source_field' => 'id',
                'expected_value_type' => 'boolean',
                'evaluator_key' => 'research_profile_exists',
                'description' => 'Checks whether applicant has completed research profile.',
                'display_order' => 15,
            ],
            [
                'code' => 'PUBLICATION_REQUIRED',
                'name' => 'Publication Required',
                'source_area' => 'research',
                'source_collection' => 'applicant_publications',
                'source_field' => 'id',
                'expected_value_type' => 'boolean',
                'evaluator_key' => 'publication_exists',
                'description' => 'Checks whether applicant has publication information.',
                'display_order' => 16,
            ],
        ];

        foreach ($ruleTypes as $ruleType) {
            EligibilityRuleType::withoutGlobalScopes()->updateOrCreate(
                [
                    'tenant_id' => null,
                    'code' => $ruleType['code'],
                ],
                [
                    'name' => $ruleType['name'],
                    'source_area' => $ruleType['source_area'],
                    'source_collection' => $ruleType['source_collection'],
                    'source_field' => $ruleType['source_field'],
                    'expected_value_type' => $ruleType['expected_value_type'],
                    'evaluator_key' => $ruleType['evaluator_key'],
                    'is_system' => true,
                    'is_active' => true,
                    'display_order' => $ruleType['display_order'],
                    'description' => $ruleType['description'],
                ]
            );
        }
    }
}