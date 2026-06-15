<?php

namespace Database\Seeders;

use App\Core\Tenant\Models\Tenant;
use App\Modules\Academic\Models\AcademicSession;
use App\Modules\Academic\Models\Department;
use App\Modules\Academic\Models\Faculty;
use App\Modules\Academic\Models\Institute;
//use App\Modules\Academic\Models\Program;
use App\Modules\Lookup\Models\LookupCategory;
use App\Modules\Lookup\Models\LookupValue;
use App\Modules\Student\Models\Guardian;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Models\StudentBatch;
use App\Modules\Student\Models\StudentDocument;
use App\Modules\Student\Models\StudentGuardian;
use App\Modules\Student\Models\StudentPreviousEducation;
use App\Modules\Student\Models\StudentStatusHistory;
use App\Modules\Subject\Models\Curriculum;
use Illuminate\Database\Seeder;

class StudentSampleSeeder extends Seeder
{
    public function run(): void
    {
        $curriculum = Curriculum::query()
            ->with('program')
            ->whereHas('program')
            ->first();

        if (!$curriculum) {
            $this->command->error('Curriculum missing. Please create at least one curriculum first.');
            return;
        }

        $program = $curriculum->program;

        $tenant = Tenant::query()
            ->where('id', $curriculum->tenant_id)
            ->first();

        if (!$tenant) {
            $this->command->error('Tenant missing for selected curriculum.');
            return;
        }

        $session = AcademicSession::where('tenant_id', $tenant->id)->first();
        $faculty = Faculty::where('tenant_id', $tenant->id)->first();
        $institute = Institute::where('tenant_id', $tenant->id)->first();
        $department = Department::where('tenant_id', $tenant->id)->first();

        $country = $this->lookup('COUNTRY', 'PK');
        $province = $this->lookup('PROVINCE', 'PUNJAB');
        $city = $this->lookup('CITY', 'MULTAN');
        $bloodGroup = $this->lookup('BLOOD_GROUP', 'A_POS');
        $religion = $this->lookup('RELIGION', 'ISLAM');
        $nationality = $this->lookup('NATIONALITY', 'PAKISTANI');

        $fatherRelation = $this->lookup('RELATIONSHIP_TYPE', 'FATHER');
        $matric = $this->lookup('QUALIFICATION_LEVEL', 'MATRIC');
        $intermediate = $this->lookup('QUALIFICATION_LEVEL', 'INTERMEDIATE');
        $board = $this->lookup('BOARD', 'BISE_MULTAN');
        $documentType = $this->lookup('DOCUMENT_TYPE', 'B-FORM');

        $batch = StudentBatch::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'code' => 'BSCS-2026-MORNING',
            ],
            [
                'academic_session_id' => $session?->id,
                'faculty_id' => $faculty?->id,
                'institute_id' => $institute?->id,
                'department_id' => $department?->id,
                'program_id' => $program->id,
                'curriculum_id' => $curriculum->id,
                'name' => 'BSCS 2026 Morning',
                'start_date' => '2026-06-01',
                'expected_end_date' => '2030-06-30',
                'capacity' => 60,
                'shift' => 'morning',
                'status' => 'active',
                'remarks' => 'Sample batch for testing.',
            ]
        );

        $student = Student::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'student_no' => 'STD-0001',
            ],
            [
                'admission_no' => 'ADM-2026-0001',
                'first_name' => 'Ali',
                'last_name' => 'Hassan',
                'father_name' => 'Muhammad Hassan',
                'mother_name' => 'Fatima Hassan',
                'cnic_bform' => '31203-1234567-1',
                'passport_no' => null,
                'date_of_birth' => '2008-05-10',
                'gender' => 'male',
                'blood_group_id' => $bloodGroup?->id,
                'religion_id' => $religion?->id,
                'nationality_id' => $nationality?->id,
                'phone' => '03001234567',
                'alternate_phone' => '03009876543',
                'email' => 'ali.hassan@example.com',
                'current_address' => 'Multan, Punjab',
                'permanent_address' => 'Multan, Punjab',
                'country_id' => $country?->id,
                'province_id' => $province?->id,
                'city_id' => $city?->id,
                'photo_path' => null,
                'admission_date' => '2026-06-01',
                'student_status' => 'active',
                'remarks' => 'Sample student.',
            ]
        );

        $guardian = Guardian::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'cnic' => '31203-7654321-1',
            ],
            [
                'name' => 'Muhammad Hassan',
                'phone' => '03001234567',
                'alternate_phone' => '03001112222',
                'email' => 'hassan.guardian@example.com',
                'occupation' => 'Business',
                'monthly_income' => 120000,
                'address' => 'Multan, Punjab',
                'country_id' => $country?->id,
                'province_id' => $province?->id,
                'city_id' => $city?->id,
                'status' => 'active',
            ]
        );

        StudentGuardian::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'student_id' => $student->id,
                'guardian_id' => $guardian->id,
            ],
            [
                'relationship_type_id' => $fatherRelation?->id,
                'is_primary' => true,
                'is_emergency_contact' => true,
                'can_pick_student' => true,
                'remarks' => 'Father and primary guardian.',
                'status' => 'active',
            ]
        );

        StudentPreviousEducation::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'student_id' => $student->id,
                'qualification_level_id' => $matric?->id,
            ],
            [
                'education_board_id' => $board?->id,
                'external_institution_id' => null,
                'degree_class_name' => 'Matric Science',
                'roll_no' => '123456',
                'registration_no' => 'REG-MAT-001',
                'passing_year' => '2024',
                'total_marks' => 1100,
                'obtained_marks' => 935,
                'percentage' => 85.00,
                'grade' => 'A',
                'cgpa' => null,
                'document_path' => null,
                'remarks' => 'Sample matric record.',
                'status' => 'active',
            ]
        );

        StudentPreviousEducation::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'student_id' => $student->id,
                'qualification_level_id' => $intermediate?->id,
            ],
            [
                'education_board_id' => $board?->id,
                'external_institution_id' => null,
                'degree_class_name' => 'Intermediate Pre-Engineering',
                'roll_no' => '789012',
                'registration_no' => 'REG-INT-001',
                'passing_year' => '2026',
                'total_marks' => 1100,
                'obtained_marks' => 880,
                'percentage' => 80.00,
                'grade' => 'A',
                'cgpa' => null,
                'document_path' => null,
                'remarks' => 'Sample intermediate record.',
                'status' => 'active',
            ]
        );

        StudentDocument::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'student_id' => $student->id,
                'document_title' => 'B-Form',
            ],
            [
                'document_type_id' => $documentType?->id,
                'file_path' => null,
                'file_name' => null,
                'mime_type' => null,
                'file_size' => null,
                'verification_status' => 'pending',
                'verified_at' => null,
                'verified_by' => null,
                'remarks' => 'Sample document placeholder.',
                'status' => 'active',
            ]
        );

        StudentStatusHistory::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'student_id' => $student->id,
                'to_status' => 'active',
            ],
            [
                'from_status' => 'applicant',
                'effective_date' => '2026-06-01',
                'reason' => 'Admission confirmed',
                'remarks' => 'Sample status history.',
                'changed_by' => null,
            ]
        );

        $this->command->info('Student sample data seeded successfully.');
    }

    private function lookup(string $categoryCode, string $valueCode): ?LookupValue
    {
        $category = LookupCategory::where('code', $categoryCode)->first();

        if (!$category) {
            return null;
        }

        return LookupValue::where('lookup_category_id', $category->id)
            ->where('code', $valueCode)
            ->first();
    }
}