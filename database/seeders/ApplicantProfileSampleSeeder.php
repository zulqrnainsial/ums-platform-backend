<?php

namespace Database\Seeders;

use App\Core\Tenant\Models\Tenant;
use App\Modules\Admission\Models\Applicant;
use App\Modules\Admission\Models\ApplicantDocument;
use App\Modules\Admission\Models\ApplicantExperience;
use App\Modules\Admission\Models\ApplicantProfileStepStatus;
use App\Modules\Admission\Models\ApplicantPublication;
use App\Modules\Admission\Models\ApplicantQualification;
use App\Modules\Admission\Models\ApplicantQualificationSubject;
use App\Modules\Admission\Models\ApplicantResearchProfile;
use App\Modules\Admission\Models\ApplicantTestResult;
use App\Modules\Lookup\Models\LookupCategory;
use App\Modules\Lookup\Models\LookupValue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ApplicantProfileSampleSeeder extends Seeder
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
        $applicant = Applicant::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('id')
            ->first();

        if (!$applicant) {
            $this->command->warn("Skipped {$tenant->name}: no applicant found. Run AdmissionFoundationSampleSeeder first.");
            return;
        }

        $matric = $this->seedQualification(
            tenant: $tenant,
            applicant: $applicant,
            levelCode: 'MATRIC',
            subjectGroupCode: 'SCIENCE',
            degreeName: 'Matric Science',
            passingYear: '2024',
            totalMarks: 1100,
            obtainedMarks: 935,
            percentage: 85.00,
            grade: 'A'
        );

        $intermediate = $this->seedQualification(
            tenant: $tenant,
            applicant: $applicant,
            levelCode: 'INTERMEDIATE',
            subjectGroupCode: 'ICS',
            degreeName: 'Intermediate Computer Science',
            passingYear: '2026',
            totalMarks: 1100,
            obtainedMarks: 880,
            percentage: 80.00,
            grade: 'A'
        );

        $this->seedQualificationSubjects($tenant, $matric, [
            ['code' => 'MATH', 'name' => 'Mathematics', 'total' => 150, 'obtained' => 130, 'grade' => 'A'],
            ['code' => 'PHY', 'name' => 'Physics', 'total' => 150, 'obtained' => 125, 'grade' => 'A'],
            ['code' => 'CHEM', 'name' => 'Chemistry', 'total' => 150, 'obtained' => 120, 'grade' => 'A'],
            ['code' => 'BIO', 'name' => 'Biology', 'total' => 150, 'obtained' => 128, 'grade' => 'A'],
        ]);

        $this->seedQualificationSubjects($tenant, $intermediate, [
            ['code' => 'MATH', 'name' => 'Mathematics', 'total' => 200, 'obtained' => 165, 'grade' => 'A'],
            ['code' => 'PHY', 'name' => 'Physics', 'total' => 200, 'obtained' => 160, 'grade' => 'A'],
            ['code' => 'CS', 'name' => 'Computer Science', 'total' => 200, 'obtained' => 175, 'grade' => 'A'],
        ]);

        $this->seedExperience($tenant, $applicant);
        $this->seedResearchProfile($tenant, $applicant);
        $this->seedPublication($tenant, $applicant);
        $this->seedDocumentMetadata($tenant, $applicant, $intermediate);
        $this->seedTestResult($tenant, $applicant);
        $this->seedStepStatuses($tenant, $applicant);

        $this->command->info("Applicant profile sample data seeded for tenant: {$tenant->name}");
    }

    private function seedQualification(
        Tenant $tenant,
        Applicant $applicant,
        string $levelCode,
        string $subjectGroupCode,
        string $degreeName,
        string $passingYear,
        float $totalMarks,
        float $obtainedMarks,
        float $percentage,
        string $grade
    ): ApplicantQualification {
        $qualificationLevel = $this->lookupValue('QUALIFICATION_LEVEL', $levelCode);
        $board = $this->lookupValue('BOARD', 'BISE_MULTAN');
        $institution = $this->lookupValue('EXTERNAL_INSTITUTION', 'GOVT_COLLEGE');
        $subjectGroup = $this->lookupValue('SUBJECT_GROUP', $subjectGroupCode);

        return ApplicantQualification::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'applicant_id' => $applicant->id,
                'qualification_level_id' => $qualificationLevel?->id,
            ],
            [
                'education_board_id' => $board?->id,
                'external_institution_id' => $institution?->id,
                'subject_group_id' => $subjectGroup?->id,
                'degree_class_name' => $degreeName,
                'roll_no' => $levelCode === 'MATRIC' ? 'MAT-123456' : 'INT-789012',
                'registration_no' => $levelCode === 'MATRIC' ? 'REG-MAT-001' : 'REG-INT-001',
                'passing_year' => $passingYear,
                'result_status_code' => 'passed',
                'total_marks' => $totalMarks,
                'obtained_marks' => $obtainedMarks,
                'percentage' => $percentage,
                'cgpa' => null,
                'cgpa_scale' => null,
                'grade' => $grade,
                'equivalence_required' => false,
                'equivalence_status_code' => null,
                'is_final_result' => true,
                'is_verified' => true,
                'remarks' => 'Sample qualification record.',
            ]
        );
    }

    private function seedQualificationSubjects(
        Tenant $tenant,
        ApplicantQualification $qualification,
        array $subjects
    ): void {
        foreach ($subjects as $subject) {
            $percentage = $subject['total'] > 0
                ? round(($subject['obtained'] / $subject['total']) * 100, 2)
                : null;

            ApplicantQualificationSubject::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'applicant_qualification_id' => $qualification->id,
                    'subject_code' => $subject['code'],
                ],
                [
                    'subject_id' => null,
                    'subject_name' => $subject['name'],
                    'total_marks' => $subject['total'],
                    'obtained_marks' => $subject['obtained'],
                    'percentage' => $percentage,
                    'grade' => $subject['grade'],
                    'result_status_code' => 'passed',
                    'remarks' => 'Sample subject-wise marks.',
                ]
            );
        }
    }

    private function seedExperience(Tenant $tenant, Applicant $applicant): void
    {
        $employmentType = $this->lookupValue('EMPLOYMENT_TYPE', 'internship');
        $experienceArea = $this->lookupValue('EXPERIENCE_AREA', 'software_development');

        ApplicantExperience::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'applicant_id' => $applicant->id,
                'organization_name' => 'ABC Software House',
            ],
            [
                'designation' => 'Intern Developer',
                'employment_type_id' => $employmentType?->id,
                'experience_area_id' => $experienceArea?->id,
                'from_date' => now()->subMonths(6)->toDateString(),
                'to_date' => now()->subMonths(3)->toDateString(),
                'currently_working' => false,
                'total_months' => 3,
                'status_code' => 'active',
                'job_description' => 'Worked on basic web development tasks.',
                'remarks' => 'Sample experience record.',
            ]
        );
    }

    private function seedResearchProfile(Tenant $tenant, Applicant $applicant): void
    {
        $researchArea = $this->lookupValue('RESEARCH_AREA', 'artificial_intelligence');

        ApplicantResearchProfile::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'applicant_id' => $applicant->id,
            ],
            [
                'research_area_id' => $researchArea?->id,
                'proposed_research_title' => 'AI-Based Academic Recommendation System',
                'statement_of_purpose' => 'I want to work on AI-based recommendation systems for higher education.',
                'research_interests' => 'Machine Learning, Natural Language Processing, Educational Data Mining',
                'preferred_supervisor_name' => null,
                'preferred_supervisor_email' => null,
                'status_code' => 'completed',
                'remarks' => 'Sample research profile.',
            ]
        );
    }

    private function seedPublication(Tenant $tenant, Applicant $applicant): void
    {
        $publicationType = $this->lookupValue('PUBLICATION_TYPE', 'conference_paper');
        $indexingType = $this->lookupValue('INDEXING_TYPE', 'google_scholar');

        ApplicantPublication::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'applicant_id' => $applicant->id,
                'title' => 'A Review of AI in Education',
            ],
            [
                'publication_type_id' => $publicationType?->id,
                'indexing_type_id' => $indexingType?->id,
                'journal_conference_name' => 'Student Research Conference',
                'publisher' => 'University Research Cell',
                'publication_year' => '2025',
                'doi' => null,
                'url' => null,
                'status_code' => 'claimed',
                'is_verified' => false,
                'remarks' => 'Sample publication claim.',
            ]
        );
    }

    private function seedDocumentMetadata(
        Tenant $tenant,
        Applicant $applicant,
        ApplicantQualification $qualification
    ): void {
        $documentType = $this->lookupValue('DOCUMENT_TYPE', 'INTERMEDIATE_RESULT_CARD');

        ApplicantDocument::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'applicant_id' => $applicant->id,
                'document_title' => 'Intermediate Result Card',
            ],
            [
                'applicant_program_application_id' => null,
                'document_type_id' => $documentType?->id,
                'related_table' => 'applicant_qualifications',
                'related_id' => $qualification->id,
                'file_path' => null,
                'original_file_name' => null,
                'stored_file_name' => null,
                'mime_type' => null,
                'file_size' => null,
                'verification_status_code' => 'pending',
                'verified_at' => null,
                'verified_by' => null,
                'rejection_reason' => null,
                'remarks' => 'Sample document metadata. Actual file upload will be done from upload API.',
            ]
        );
    }

    private function seedTestResult(Tenant $tenant, Applicant $applicant): void
    {
        $testType = $this->lookupValue('TEST_TYPE', 'nat');

        ApplicantTestResult::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'applicant_id' => $applicant->id,
                'test_code' => 'NAT-IM',
            ],
            [
                'applicant_program_application_id' => null,
                'test_type_id' => $testType?->id,
                'test_source_code' => 'external',
                'test_name' => 'NAT Intermediate',
                'roll_no' => 'NAT-123456',
                'test_date' => now()->subMonths(2)->toDateString(),
                'total_marks' => 100,
                'obtained_marks' => 72,
                'percentage' => 72,
                'percentile' => null,
                'result_status_code' => 'submitted',
                'is_verified' => false,
                'document_id' => null,
                'remarks' => 'Sample external test result.',
            ]
        );
    }

    private function seedStepStatuses(Tenant $tenant, Applicant $applicant): void
    {
        $steps = [
            ['personal_info', 'Personal Information', 'completed', 1],
            ['demographic_info', 'Demographic Information', 'completed', 2],
            ['qualifications', 'Previous Qualifications', 'completed', 3],
            ['experience', 'Experience', 'completed', 4],
            ['research_profile', 'Research Profile', 'completed', 5],
            ['publications', 'Publications', 'completed', 6],
            ['documents', 'Documents', 'pending', 7],
            ['test_results', 'Test Results', 'completed', 8],
            ['eligible_programs', 'Eligible Programs', 'pending', 9],
            ['final_submission', 'Final Submission', 'pending', 10],
        ];

        foreach ($steps as [$code, $title, $status, $order]) {
            ApplicantProfileStepStatus::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'applicant_id' => $applicant->id,
                    'step_code' => $code,
                ],
                [
                    'step_title' => $title,
                    'status_code' => $status,
                    'display_order' => $order,
                    'started_at' => $status !== 'pending' ? now()->subDays(2) : null,
                    'completed_at' => $status === 'completed' ? now()->subDay() : null,
                    'verified_at' => null,
                    'verified_by' => null,
                    'remarks' => 'Sample profile wizard step status.',
                ]
            );
        }
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