<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AssessmentLookupSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCategory('ASSESSMENT_PURPOSE', 'Assessment Purpose', [
            ['code' => 'admission', 'name' => 'Admission'],
            ['code' => 'hr', 'name' => 'HR Recruitment'],
            ['code' => 'academic', 'name' => 'Academic'],
            ['code' => 'training', 'name' => 'Training'],
            ['code' => 'survey', 'name' => 'Survey'],
        ]);

        $this->seedCategory('ASSESSMENT_MODE', 'Assessment Mode', [
            ['code' => 'online', 'name' => 'Online'],
            ['code' => 'offline', 'name' => 'Offline'],
            ['code' => 'external', 'name' => 'External'],
            ['code' => 'interview', 'name' => 'Interview'],
            ['code' => 'hybrid', 'name' => 'Hybrid'],
        ]);

        $this->seedCategory('QUESTION_TYPE', 'Question Type', [
            ['code' => 'mcq_single', 'name' => 'MCQ Single Choice'],
            ['code' => 'mcq_multiple', 'name' => 'MCQ Multiple Choice'],
            ['code' => 'true_false', 'name' => 'True / False'],
            ['code' => 'short_answer', 'name' => 'Short Answer'],
            ['code' => 'long_answer', 'name' => 'Long Answer'],
            ['code' => 'numeric', 'name' => 'Numeric Answer'],
            ['code' => 'fill_blank', 'name' => 'Fill in the Blank'],
            ['code' => 'matching', 'name' => 'Matching'],
            ['code' => 'ordering', 'name' => 'Ordering'],
            ['code' => 'file_upload', 'name' => 'File Upload'],
            ['code' => 'code', 'name' => 'Code Writing'],
            ['code' => 'interview', 'name' => 'Interview / Viva'],
            ['code' => 'practical', 'name' => 'Practical Observation'],
        ]);

        $this->seedCategory('QUESTION_DIFFICULTY', 'Question Difficulty', [
            ['code' => 'easy', 'name' => 'Easy'],
            ['code' => 'medium', 'name' => 'Medium'],
            ['code' => 'hard', 'name' => 'Hard'],
        ]);

        $this->seedCategory('COGNITIVE_LEVEL', 'Cognitive Level', [
            ['code' => 'knowledge', 'name' => 'Knowledge'],
            ['code' => 'understanding', 'name' => 'Understanding'],
            ['code' => 'application', 'name' => 'Application'],
            ['code' => 'analysis', 'name' => 'Analysis'],
            ['code' => 'evaluation', 'name' => 'Evaluation'],
            ['code' => 'creation', 'name' => 'Creation'],
        ]);

        $this->seedCategory('QUESTION_APPROVAL_STATUS', 'Question Approval Status', [
            ['code' => 'draft', 'name' => 'Draft'],
            ['code' => 'reviewed', 'name' => 'Reviewed'],
            ['code' => 'approved', 'name' => 'Approved'],
            ['code' => 'rejected', 'name' => 'Rejected'],
        ]);

        $this->seedCategory('QUESTION_SELECTION_MODE', 'Question Selection Mode', [
            ['code' => 'manual', 'name' => 'Manual'],
            ['code' => 'random', 'name' => 'Random'],
            ['code' => 'mixed', 'name' => 'Mixed'],
        ]);

        $this->seedCategory('NEGATIVE_MARKING_TYPE', 'Negative Marking Type', [
            ['code' => 'none', 'name' => 'None'],
            ['code' => 'per_question', 'name' => 'Per Question'],
            ['code' => 'per_section', 'name' => 'Per Section'],
        ]);

        $this->seedCategory('ASSESSMENT_STATUS', 'Assessment Status', [
            ['code' => 'draft', 'name' => 'Draft'],
            ['code' => 'scheduled', 'name' => 'Scheduled'],
            ['code' => 'open', 'name' => 'Open'],
            ['code' => 'closed', 'name' => 'Closed'],
            ['code' => 'result_generated', 'name' => 'Result Generated'],
            ['code' => 'published', 'name' => 'Published'],
            ['code' => 'cancelled', 'name' => 'Cancelled'],
        ]);

        $this->seedCategory('ASSESSMENT_SCHEDULE_STATUS', 'Assessment Schedule Status', [
            ['code' => 'scheduled', 'name' => 'Scheduled'],
            ['code' => 'open', 'name' => 'Open'],
            ['code' => 'closed', 'name' => 'Closed'],
            ['code' => 'cancelled', 'name' => 'Cancelled'],
        ]);

        $this->seedCategory('PARTICIPANT_TYPE', 'Participant Type', [
            ['code' => 'applicant', 'name' => 'Applicant'],
            ['code' => 'student', 'name' => 'Student'],
            ['code' => 'employee', 'name' => 'Employee'],
            ['code' => 'hr_candidate', 'name' => 'HR Candidate'],
            ['code' => 'external_candidate', 'name' => 'External Candidate'],
        ]);

        $this->seedCategory('ATTENDANCE_STATUS', 'Attendance Status', [
            ['code' => 'pending', 'name' => 'Pending'],
            ['code' => 'present', 'name' => 'Present'],
            ['code' => 'absent', 'name' => 'Absent'],
            ['code' => 'exempted', 'name' => 'Exempted'],
        ]);

        $this->seedCategory('ATTEMPT_STATUS', 'Attempt Status', [
            ['code' => 'not_started', 'name' => 'Not Started'],
            ['code' => 'in_progress', 'name' => 'In Progress'],
            ['code' => 'submitted', 'name' => 'Submitted'],
            ['code' => 'auto_submitted', 'name' => 'Auto Submitted'],
            ['code' => 'evaluated', 'name' => 'Evaluated'],
            ['code' => 'cancelled', 'name' => 'Cancelled'],
        ]);

        $this->seedCategory('RESULT_STATUS', 'Result Status', [
            ['code' => 'pending', 'name' => 'Pending'],
            ['code' => 'draft', 'name' => 'Draft'],
            ['code' => 'generated', 'name' => 'Generated'],
            ['code' => 'approved', 'name' => 'Approved'],
            ['code' => 'published', 'name' => 'Published'],
            ['code' => 'withheld', 'name' => 'Withheld'],
        ]);

        $this->seedCategory('RESULT_OUTCOME', 'Result Outcome', [
            ['code' => 'pass', 'name' => 'Pass'],
            ['code' => 'fail', 'name' => 'Fail'],
            ['code' => 'withheld', 'name' => 'Withheld'],
        ]);
    }

    private function seedCategory(string $code, string $name, array $values): void
    {
        if (!Schema::hasTable('lookup_categories') || !Schema::hasTable('lookup_values')) {
            return;
        }

        $categoryData = [
            'tenant_id' => null,
            'code' => $code,
            'name' => $name,
            'description' => $name,
            'status' => 'active',
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('lookup_categories', 'is_system')) {
            $categoryData['is_system'] = true;
        }

        if (Schema::hasColumn('lookup_categories', 'is_tenant_editable')) {
            $categoryData['is_tenant_editable'] = true;
        }

        if (Schema::hasColumn('lookup_categories', 'display_order')) {
            $categoryData['display_order'] = 0;
        }

        if (Schema::hasColumn('lookup_categories', 'created_at')) {
            $categoryData['created_at'] = now();
        }

        DB::table('lookup_categories')->updateOrInsert(
            [
                'tenant_id' => null,
                'code' => $code,
            ],
            $categoryData
        );

        $category = DB::table('lookup_categories')
            ->whereNull('tenant_id')
            ->where('code', $code)
            ->first();

        if (!$category) {
            return;
        }

        foreach ($values as $index => $value) {
            $valueData = [
                'tenant_id' => null,
                'lookup_category_id' => $category->id,
                'code' => $value['code'],
                'name' => $value['name'],
                'status' => 'active',
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('lookup_values', 'display_order')) {
                $valueData['display_order'] = $index + 1;
            }

            if (Schema::hasColumn('lookup_values', 'created_at')) {
                $valueData['created_at'] = now();
            }

            if (Schema::hasColumn('lookup_values', 'description')) {
                $valueData['description'] = $value['name'];
            }

            DB::table('lookup_values')->updateOrInsert(
                [
                    'tenant_id' => null,
                    'lookup_category_id' => $category->id,
                    'code' => $value['code'],
                ],
                $valueData
            );
        }
    }
}