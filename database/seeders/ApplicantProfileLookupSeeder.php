<?php

namespace Database\Seeders;

use App\Modules\Lookup\Models\LookupCategory;
use App\Modules\Lookup\Models\LookupValue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ApplicantProfileLookupSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->seedLookups();
        });

        $this->command->info('Applicant profile lookup data seeded successfully.');
    }

    private function seedLookups(): void
    {
        $categories = [
            'RESULT_STATUS' => [
                'name' => 'Result Status',
                'description' => 'Academic/test result statuses.',
                'display_order' => 120,
                'values' => [
                    ['code' => 'passed', 'name' => 'Passed', 'display_order' => 1],
                    ['code' => 'failed', 'name' => 'Failed', 'display_order' => 2],
                    ['code' => 'awaiting_result', 'name' => 'Awaiting Result', 'display_order' => 3],
                    ['code' => 'supplementary', 'name' => 'Supplementary', 'display_order' => 4],
                    ['code' => 'submitted', 'name' => 'Submitted', 'display_order' => 5],
                    ['code' => 'verified', 'name' => 'Verified', 'display_order' => 6],
                    ['code' => 'rejected', 'name' => 'Rejected', 'display_order' => 7],
                ],
            ],

            'SUBJECT_GROUP' => [
                'name' => 'Subject Group',
                'description' => 'Previous qualification subject groups.',
                'display_order' => 121,
                'values' => [
                    ['code' => 'SCIENCE', 'name' => 'Science', 'display_order' => 1],
                    ['code' => 'ARTS', 'name' => 'Arts', 'display_order' => 2],
                    ['code' => 'COMMERCE', 'name' => 'Commerce', 'display_order' => 3],
                    ['code' => 'ICS', 'name' => 'ICS', 'display_order' => 4],
                    ['code' => 'PRE_ENGINEERING', 'name' => 'Pre-Engineering', 'display_order' => 5],
                    ['code' => 'PRE_MEDICAL', 'name' => 'Pre-Medical', 'display_order' => 6],
                    ['code' => 'GENERAL_SCIENCE', 'name' => 'General Science', 'display_order' => 7],
                    ['code' => 'COMPUTER_SCIENCE', 'name' => 'Computer Science', 'display_order' => 8],
                    ['code' => 'OTHER', 'name' => 'Other', 'display_order' => 99],
                ],
            ],

            'EMPLOYMENT_TYPE' => [
                'name' => 'Employment Type',
                'description' => 'Applicant employment types.',
                'display_order' => 122,
                'values' => [
                    ['code' => 'full_time', 'name' => 'Full Time', 'display_order' => 1],
                    ['code' => 'part_time', 'name' => 'Part Time', 'display_order' => 2],
                    ['code' => 'contract', 'name' => 'Contract', 'display_order' => 3],
                    ['code' => 'internship', 'name' => 'Internship', 'display_order' => 4],
                    ['code' => 'self_employed', 'name' => 'Self Employed', 'display_order' => 5],
                    ['code' => 'other', 'name' => 'Other', 'display_order' => 99],
                ],
            ],

            'EXPERIENCE_AREA' => [
                'name' => 'Experience Area',
                'description' => 'Applicant work experience areas.',
                'display_order' => 123,
                'values' => [
                    ['code' => 'teaching', 'name' => 'Teaching', 'display_order' => 1],
                    ['code' => 'software_development', 'name' => 'Software Development', 'display_order' => 2],
                    ['code' => 'research', 'name' => 'Research', 'display_order' => 3],
                    ['code' => 'administration', 'name' => 'Administration', 'display_order' => 4],
                    ['code' => 'healthcare', 'name' => 'Healthcare', 'display_order' => 5],
                    ['code' => 'industry', 'name' => 'Industry', 'display_order' => 6],
                    ['code' => 'other', 'name' => 'Other', 'display_order' => 99],
                ],
            ],

            'EMPLOYMENT_STATUS' => [
                'name' => 'Employment Status',
                'description' => 'Experience record status.',
                'display_order' => 124,
                'values' => [
                    ['code' => 'active', 'name' => 'Active', 'display_order' => 1],
                    ['code' => 'inactive', 'name' => 'Inactive', 'display_order' => 2],
                    ['code' => 'verified', 'name' => 'Verified', 'display_order' => 3],
                    ['code' => 'rejected', 'name' => 'Rejected', 'display_order' => 4],
                ],
            ],

            'RESEARCH_AREA' => [
                'name' => 'Research Area',
                'description' => 'Applicant research areas.',
                'display_order' => 125,
                'values' => [
                    ['code' => 'artificial_intelligence', 'name' => 'Artificial Intelligence', 'display_order' => 1],
                    ['code' => 'data_science', 'name' => 'Data Science', 'display_order' => 2],
                    ['code' => 'software_engineering', 'name' => 'Software Engineering', 'display_order' => 3],
                    ['code' => 'networks', 'name' => 'Computer Networks', 'display_order' => 4],
                    ['code' => 'cyber_security', 'name' => 'Cyber Security', 'display_order' => 5],
                    ['code' => 'database_systems', 'name' => 'Database Systems', 'display_order' => 6],
                    ['code' => 'natural_language_processing', 'name' => 'Natural Language Processing', 'display_order' => 7],
                    ['code' => 'other', 'name' => 'Other', 'display_order' => 99],
                ],
            ],

            'PUBLICATION_TYPE' => [
                'name' => 'Publication Type',
                'description' => 'Applicant publication types.',
                'display_order' => 126,
                'values' => [
                    ['code' => 'journal_article', 'name' => 'Journal Article', 'display_order' => 1],
                    ['code' => 'conference_paper', 'name' => 'Conference Paper', 'display_order' => 2],
                    ['code' => 'book_chapter', 'name' => 'Book Chapter', 'display_order' => 3],
                    ['code' => 'book', 'name' => 'Book', 'display_order' => 4],
                    ['code' => 'poster', 'name' => 'Poster', 'display_order' => 5],
                    ['code' => 'other', 'name' => 'Other', 'display_order' => 99],
                ],
            ],

            'INDEXING_TYPE' => [
                'name' => 'Indexing Type',
                'description' => 'Publication indexing types.',
                'display_order' => 127,
                'values' => [
                    ['code' => 'wos', 'name' => 'Web of Science', 'display_order' => 1],
                    ['code' => 'scopus', 'name' => 'Scopus', 'display_order' => 2],
                    ['code' => 'hec_x', 'name' => 'HEC X Category', 'display_order' => 3],
                    ['code' => 'hec_y', 'name' => 'HEC Y Category', 'display_order' => 4],
                    ['code' => 'hec_w', 'name' => 'HEC W Category', 'display_order' => 5],
                    ['code' => 'google_scholar', 'name' => 'Google Scholar', 'display_order' => 6],
                    ['code' => 'other', 'name' => 'Other', 'display_order' => 99],
                ],
            ],

            'PUBLICATION_STATUS' => [
                'name' => 'Publication Status',
                'description' => 'Applicant publication verification statuses.',
                'display_order' => 128,
                'values' => [
                    ['code' => 'claimed', 'name' => 'Claimed', 'display_order' => 1],
                    ['code' => 'submitted', 'name' => 'Submitted', 'display_order' => 2],
                    ['code' => 'verified', 'name' => 'Verified', 'display_order' => 3],
                    ['code' => 'rejected', 'name' => 'Rejected', 'display_order' => 4],
                ],
            ],

            'TEST_TYPE' => [
                'name' => 'Test Type',
                'description' => 'Admission/external test types.',
                'display_order' => 129,
                'values' => [
                    ['code' => 'tenant_admission_test', 'name' => 'Tenant Admission Test', 'display_order' => 1],
                    ['code' => 'nat', 'name' => 'NAT', 'display_order' => 2],
                    ['code' => 'gat_general', 'name' => 'GAT General', 'display_order' => 3],
                    ['code' => 'gat_subject', 'name' => 'GAT Subject', 'display_order' => 4],
                    ['code' => 'gre', 'name' => 'GRE', 'display_order' => 5],
                    ['code' => 'sat', 'name' => 'SAT', 'display_order' => 6],
                    ['code' => 'other', 'name' => 'Other', 'display_order' => 99],
                ],
            ],

            'TEST_SOURCE' => [
                'name' => 'Test Source',
                'description' => 'Test result source.',
                'display_order' => 130,
                'values' => [
                    ['code' => 'tenant', 'name' => 'Tenant Conducted', 'display_order' => 1],
                    ['code' => 'external', 'name' => 'External Body', 'display_order' => 2],
                    ['code' => 'imported', 'name' => 'Imported', 'display_order' => 3],
                    ['code' => 'manual', 'name' => 'Manual Entry', 'display_order' => 4],
                ],
            ],

            'PROFILE_STEP_STATUS' => [
                'name' => 'Profile Step Status',
                'description' => 'Applicant wizard step statuses.',
                'display_order' => 131,
                'values' => [
                    ['code' => 'pending', 'name' => 'Pending', 'display_order' => 1],
                    ['code' => 'in_progress', 'name' => 'In Progress', 'display_order' => 2],
                    ['code' => 'completed', 'name' => 'Completed', 'display_order' => 3],
                    ['code' => 'verified', 'name' => 'Verified', 'display_order' => 4],
                    ['code' => 'rejected', 'name' => 'Rejected', 'display_order' => 5],
                    ['code' => 'skipped', 'name' => 'Skipped', 'display_order' => 6],
                ],
            ],

            'DISABILITY_TYPE' => [
                'name' => 'Disability Type',
                'description' => 'Applicant disability types.',
                'display_order' => 132,
                'values' => [
                    ['code' => 'physical', 'name' => 'Physical Disability', 'display_order' => 1],
                    ['code' => 'visual', 'name' => 'Visual Impairment', 'display_order' => 2],
                    ['code' => 'hearing', 'name' => 'Hearing Impairment', 'display_order' => 3],
                    ['code' => 'learning', 'name' => 'Learning Disability', 'display_order' => 4],
                    ['code' => 'other', 'name' => 'Other', 'display_order' => 99],
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
        $category = LookupCategory::withoutGlobalScopes()->updateOrCreate(
            ['code' => $code],
            [
                'name' => $name,
                'description' => $description,
                'is_system' => true,
                'is_tenant_editable' => true,
                'display_order' => $displayOrder,
                'status' => 'active',
            ]
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
}