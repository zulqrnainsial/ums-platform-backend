<?php

namespace Database\Seeders;

use App\Core\Dynamic\Models\DynamicAction;
use App\Core\Dynamic\Models\DynamicEntity;
use App\Core\Dynamic\Models\DynamicField;
use App\Core\Dynamic\Models\DynamicFilter;
use App\Modules\Assessment\Models\Assessment;
use App\Modules\Assessment\Models\AssessmentCategory;
use App\Modules\Assessment\Models\AssessmentParticipant;
use App\Modules\Assessment\Models\AssessmentQuestion;
use App\Modules\Assessment\Models\AssessmentResult;
use App\Modules\Assessment\Models\AssessmentSchedule;
use App\Modules\Assessment\Models\AssessmentSection;
use App\Modules\Assessment\Models\AssessmentSubject;
use App\Modules\Assessment\Models\AssessmentTopic;
use App\Modules\Assessment\Models\Question;
use App\Modules\Assessment\Models\QuestionAnswerKey;
use App\Modules\Assessment\Models\QuestionBank;
use App\Modules\Assessment\Models\QuestionOption;
use Illuminate\Database\Seeder;

class AssessmentDynamicMetadataSeeder extends Seeder
{
    public function run(): void
    {
        $this->assessmentCategories();
        $this->assessmentSubjects();
        $this->assessmentTopics();
        $this->questionBanks();

        /*
         | Metadata exists for questions/options/answer keys,
         | but final daily-use UI will be a dedicated question editor.
         */
        $this->questions();
        $this->questionOptions();
        $this->questionAnswerKeys();

        $this->assessments();
        $this->assessmentSections();
        $this->assessmentQuestions();
        $this->assessmentSchedules();
        $this->assessmentParticipants();
        $this->assessmentResults();
    }

    private function assessmentCategories(): void
    {
        $entity = $this->entity(
            'Assessment',
            'Assessment Category',
            'assessment-categories',
            'assessment_categories',
            AssessmentCategory::class,
            'Assessment Categories',
            'Manage assessment categories such as admission, HR, academic and training.'
        );

        $this->text($entity, 'code', 'Code', true, true, true, 1, true);
        $this->text($entity, 'name', 'Name', true, true, true, 2);
        $this->lookupCodeField($entity, 'status_code', 'Status', 'STATUS', true, true, true, 3, 'active');
        $this->number($entity, 'display_order', 'Display Order', false, false, false, 4, 0);
        $this->textarea($entity, 'description', 'Description', false, false, false, 90);

        $this->filter($entity, 'status_code', 'Status', 'select', '=', 1, '/dynamic-options/lookups/STATUS');

        $this->standardActions($entity, 'assessment.category');
    }

    private function assessmentSubjects(): void
    {
        $entity = $this->entity(
            'Assessment',
            'Assessment Subject',
            'assessment-subjects',
            'assessment_subjects',
            AssessmentSubject::class,
            'Assessment Subjects',
            'Manage assessment subjects like English, Mathematics, IQ, Programming.'
        );

        $this->text($entity, 'code', 'Code', true, true, true, 1, true);
        $this->text($entity, 'name', 'Name', true, true, true, 2);
        $this->lookupCodeField($entity, 'status_code', 'Status', 'STATUS', true, true, true, 3, 'active');
        $this->number($entity, 'display_order', 'Display Order', false, false, false, 4, 0);
        $this->textarea($entity, 'description', 'Description', false, false, false, 90);

        $this->filter($entity, 'status_code', 'Status', 'select', '=', 1, '/dynamic-options/lookups/STATUS');

        $this->standardActions($entity, 'assessment.subject');
    }

    private function assessmentTopics(): void
    {
        $entity = $this->entity(
            'Assessment',
            'Assessment Topic',
            'assessment-topics',
            'assessment_topics',
            AssessmentTopic::class,
            'Assessment Topics',
            'Manage topics/subtopics for strength and weakness analysis.'
        );

        $this->selectField($entity, 'assessment_subject_id', 'Subject', '/dynamic-options/assessment-subjects', true, true, true, 1, null, null, 'subject', 'name');
        $this->selectField($entity, 'parent_id', 'Parent Topic', '/dynamic-options/assessment-topics', false, true, true, 2, 'assessment_subject_id', 'assessment_subject_id', 'parent', 'name');

        $this->text($entity, 'code', 'Code', true, true, true, 3, true);
        $this->text($entity, 'name', 'Name', true, true, true, 4);
        $this->lookupCodeField($entity, 'status_code', 'Status', 'STATUS', true, true, true, 5, 'active');
        $this->number($entity, 'display_order', 'Display Order', false, false, false, 6, 0);
        $this->textarea($entity, 'description', 'Description', false, false, false, 90);

        $this->filter($entity, 'assessment_subject_id', 'Subject', 'select', '=', 1, '/dynamic-options/assessment-subjects');
        $this->filter($entity, 'status_code', 'Status', 'select', '=', 2, '/dynamic-options/lookups/STATUS');

        $this->standardActions($entity, 'assessment.topic');
    }

    private function questionBanks(): void
    {
        $entity = $this->entity(
            'Assessment',
            'Question Bank',
            'question-banks',
            'question_banks',
            QuestionBank::class,
            'Question Banks',
            'Manage reusable question banks for admission, HR and academic assessments.'
        );

        $this->selectField($entity, 'assessment_category_id', 'Category', '/dynamic-options/assessment-categories', false, true, true, 1, null, null, 'category', 'name');
        $this->selectField($entity, 'assessment_subject_id', 'Subject', '/dynamic-options/assessment-subjects', false, true, true, 2, null, null, 'subject', 'name');

        $this->text($entity, 'code', 'Code', true, true, true, 3, true);
        $this->text($entity, 'name', 'Name', true, true, true, 4);
        $this->lookupCodeField($entity, 'status_code', 'Status', 'STATUS', true, true, true, 5, 'active');
        $this->number($entity, 'display_order', 'Display Order', false, false, false, 6, 0);
        $this->textarea($entity, 'description', 'Description', false, false, false, 90);

        $this->filter($entity, 'assessment_category_id', 'Category', 'select', '=', 1, '/dynamic-options/assessment-categories');
        $this->filter($entity, 'assessment_subject_id', 'Subject', 'select', '=', 2, '/dynamic-options/assessment-subjects');
        $this->filter($entity, 'status_code', 'Status', 'select', '=', 3, '/dynamic-options/lookups/STATUS');

        $this->standardActions($entity, 'assessment.question_bank');
    }

    private function questions(): void
    {
        $entity = $this->entity(
            'Assessment',
            'Question',
            'questions',
            'questions',
            Question::class,
            'Questions',
            'Manage question master records. Use dedicated question editor for full question creation.'
        );

        $this->selectField($entity, 'question_bank_id', 'Question Bank', '/dynamic-options/question-banks', true, true, true, 1, null, null, 'bank', 'name');
        $this->selectField($entity, 'assessment_subject_id', 'Subject', '/dynamic-options/assessment-subjects', false, true, true, 2, null, null, 'subject', 'name');
        $this->selectField($entity, 'assessment_topic_id', 'Topic', '/dynamic-options/assessment-topics', false, true, true, 3, 'assessment_subject_id', 'assessment_subject_id', 'topic', 'name');

        $this->lookupCodeField($entity, 'question_type_code', 'Question Type', 'QUESTION_TYPE', true, true, true, 4, 'mcq_single');
        $this->lookupCodeField($entity, 'difficulty_code', 'Difficulty', 'QUESTION_DIFFICULTY', false, true, true, 5, 'medium');
        $this->lookupCodeField($entity, 'cognitive_level_code', 'Cognitive Level', 'COGNITIVE_LEVEL', false, false, true, 6, 'understanding');

        $this->textarea($entity, 'question_text', 'Question Text', true, true, true, 7);
        $this->textarea($entity, 'question_html', 'Question HTML', false, false, false, 8);

        $this->number($entity, 'default_marks', 'Default Marks', true, true, false, 9, 1);
        $this->number($entity, 'default_negative_marks', 'Negative Marks', false, false, false, 10, 0);
        $this->number($entity, 'default_time_seconds', 'Default Time Seconds', false, false, false, 11, null);

        $this->lookupCodeField($entity, 'approval_status_code', 'Approval Status', 'QUESTION_APPROVAL_STATUS', true, true, true, 12, 'draft');
        $this->switchField($entity, 'is_active', 'Active', false, true, true, 13, true);

        $this->text($entity, 'source_code', 'Source', false, false, true, 80);
        $this->text($entity, 'import_batch_no', 'Import Batch No', false, false, true, 81);
        $this->text($entity, 'external_ref_no', 'External Ref No', false, false, true, 82);

        $this->textarea($entity, 'explanation', 'Explanation', false, false, false, 90);
        $this->textarea($entity, 'explanation_html', 'Explanation HTML', false, false, false, 91);

        $this->filter($entity, 'question_bank_id', 'Question Bank', 'select', '=', 1, '/dynamic-options/question-banks');
        $this->filter($entity, 'assessment_subject_id', 'Subject', 'select', '=', 2, '/dynamic-options/assessment-subjects');
        $this->filter($entity, 'question_type_code', 'Question Type', 'select', '=', 3, '/dynamic-options/lookups/QUESTION_TYPE');
        $this->filter($entity, 'difficulty_code', 'Difficulty', 'select', '=', 4, '/dynamic-options/lookups/QUESTION_DIFFICULTY');
        $this->filter($entity, 'approval_status_code', 'Approval Status', 'select', '=', 5, '/dynamic-options/lookups/QUESTION_APPROVAL_STATUS');

        $this->standardActions($entity, 'assessment.question');
    }

    private function questionOptions(): void
    {
        $entity = $this->entity(
            'Assessment',
            'Question Option',
            'question-options',
            'question_options',
            QuestionOption::class,
            'Question Options',
            'Manage MCQ, matching, ordering and option records for questions.'
        );

        $this->selectField($entity, 'question_id', 'Question', '/dynamic-options/questions', true, true, true, 1, null, null, 'question', 'question_text');

        $this->text($entity, 'option_key', 'Option Key', false, true, true, 2);
        $this->textarea($entity, 'option_text', 'Option Text', false, true, true, 3);
        $this->textarea($entity, 'option_html', 'Option HTML', false, false, false, 4);

        $this->switchField($entity, 'is_correct', 'Correct', false, true, true, 5, false);
        $this->number($entity, 'correct_order', 'Correct Order', false, true, false, 6, null);
        $this->text($entity, 'match_key', 'Match Key', false, false, false, 7);
        $this->text($entity, 'correct_match_key', 'Correct Match Key', false, false, false, 8);
        $this->number($entity, 'marks_percentage', 'Marks %', false, false, false, 9, null);
        $this->number($entity, 'display_order', 'Display Order', false, true, false, 10, 0);

        $this->text($entity, 'import_batch_no', 'Import Batch No', false, false, true, 80);
        $this->text($entity, 'external_ref_no', 'External Ref No', false, false, true, 81);

        $this->filter($entity, 'question_id', 'Question', 'select', '=', 1, '/dynamic-options/questions');
        $this->filter($entity, 'is_correct', 'Correct', 'select', '=', 2, null, [
            ['label' => 'Yes', 'value' => true],
            ['label' => 'No', 'value' => false],
        ]);

        $this->standardActions($entity, 'assessment.question');
    }

    private function questionAnswerKeys(): void
    {
        $entity = $this->entity(
            'Assessment',
            'Question Answer Key',
            'question-answer-keys',
            'question_answer_keys',
            QuestionAnswerKey::class,
            'Question Answer Keys',
            'Manage accepted answer keys for numeric, short-answer and fill-blank questions.'
        );

        $this->selectField($entity, 'question_id', 'Question', '/dynamic-options/questions', true, true, true, 1, null, null, 'question', 'question_text');

        $this->textarea($entity, 'answer_text', 'Answer Text', false, true, true, 2);
        $this->number($entity, 'answer_number', 'Answer Number', false, true, false, 3, null);
        $this->textarea($entity, 'accepted_variants_json', 'Accepted Variants JSON', false, false, false, 4);

        $this->switchField($entity, 'case_sensitive', 'Case Sensitive', false, false, false, 5, false);
        $this->number($entity, 'numeric_tolerance', 'Numeric Tolerance', false, false, false, 6, null);
        $this->number($entity, 'marks_percentage', 'Marks %', false, false, false, 7, null);
        $this->lookupCodeField($entity, 'status_code', 'Status', 'STATUS', true, true, true, 8, 'active');

        $this->filter($entity, 'question_id', 'Question', 'select', '=', 1, '/dynamic-options/questions');
        $this->filter($entity, 'status_code', 'Status', 'select', '=', 2, '/dynamic-options/lookups/STATUS');

        $this->standardActions($entity, 'assessment.question');
    }

    private function assessments(): void
    {
        $entity = $this->entity(
            'Assessment',
            'Assessment',
            'assessments',
            'assessments',
            Assessment::class,
            'Assessments / Tests',
            'Manage assessment/test definitions for admission, HR, academic and training purposes.'
        );

        $this->selectField($entity, 'assessment_category_id', 'Category', '/dynamic-options/assessment-categories', false, true, true, 1, null, null, 'category', 'name');
        $this->selectField($entity, 'admission_session_id', 'Admission Session', '/dynamic-options/admission-sessions', false, true, true, 2, null, null, 'admissionSession', 'name');

        $this->lookupCodeField($entity, 'purpose_code', 'Purpose', 'ASSESSMENT_PURPOSE', true, true, true, 3, 'admission');
        $this->lookupCodeField($entity, 'mode_code', 'Mode', 'ASSESSMENT_MODE', true, true, true, 4, 'online');

        $this->text($entity, 'code', 'Code', true, true, true, 5, true);
        $this->text($entity, 'title', 'Title', true, true, true, 6);

        $this->number($entity, 'total_marks', 'Total Marks', true, true, false, 7, 0);
        $this->number($entity, 'passing_marks', 'Passing Marks', false, true, false, 8, null);
        $this->number($entity, 'duration_minutes', 'Duration Minutes', false, true, false, 9, null);

        $this->switchField($entity, 'allow_negative_marking', 'Negative Marking', false, false, true, 10, false);
        $this->lookupCodeField($entity, 'negative_marking_type_code', 'Negative Marking Type', 'NEGATIVE_MARKING_TYPE', false, false, true, 11, 'none');

        $this->number($entity, 'attempt_limit', 'Attempt Limit', true, false, false, 12, 1);
        $this->switchField($entity, 'shuffle_questions', 'Shuffle Questions', false, false, false, 13, false);
        $this->switchField($entity, 'shuffle_options', 'Shuffle Options', false, false, false, 14, false);
        $this->switchField($entity, 'show_result_immediately', 'Show Result Immediately', false, false, false, 15, false);
        $this->switchField($entity, 'show_correct_answers', 'Show Correct Answers', false, false, false, 16, false);
        $this->switchField($entity, 'allow_review_before_submit', 'Allow Review Before Submit', false, false, false, 17, true);

        $this->lookupCodeField($entity, 'status_code', 'Status', 'ASSESSMENT_STATUS', true, true, true, 18, 'draft');

        $this->textarea($entity, 'description', 'Description', false, false, false, 90);
        $this->textarea($entity, 'instructions_html', 'Instructions HTML', false, false, false, 91);

        $this->filter($entity, 'assessment_category_id', 'Category', 'select', '=', 1, '/dynamic-options/assessment-categories');
        $this->filter($entity, 'purpose_code', 'Purpose', 'select', '=', 2, '/dynamic-options/lookups/ASSESSMENT_PURPOSE');
        $this->filter($entity, 'mode_code', 'Mode', 'select', '=', 3, '/dynamic-options/lookups/ASSESSMENT_MODE');
        $this->filter($entity, 'status_code', 'Status', 'select', '=', 4, '/dynamic-options/lookups/ASSESSMENT_STATUS');

        $this->standardActions($entity, 'assessment.test');
    }

    private function assessmentSections(): void
    {
        $entity = $this->entity(
            'Assessment',
            'Assessment Section',
            'assessment-sections',
            'assessment_sections',
            AssessmentSection::class,
            'Assessment Sections',
            'Manage sections inside an assessment/test.'
        );

        $this->selectField($entity, 'assessment_id', 'Assessment', '/dynamic-options/assessments', true, true, true, 1, null, null, 'assessment', 'title');
        $this->selectField($entity, 'assessment_subject_id', 'Subject', '/dynamic-options/assessment-subjects', false, true, true, 2, null, null, 'subject', 'name');

        $this->text($entity, 'section_code', 'Section Code', true, true, true, 3);
        $this->text($entity, 'section_title', 'Section Title', true, true, true, 4);

        $this->number($entity, 'total_questions', 'Total Questions', false, true, false, 5, null);
        $this->number($entity, 'total_marks', 'Total Marks', true, true, false, 6, 0);
        $this->number($entity, 'passing_marks', 'Passing Marks', false, false, false, 7, null);
        $this->number($entity, 'duration_minutes', 'Duration Minutes', false, true, false, 8, null);

        $this->lookupCodeField($entity, 'question_selection_mode_code', 'Question Selection Mode', 'QUESTION_SELECTION_MODE', true, true, true, 9, 'manual');
        $this->switchField($entity, 'shuffle_questions', 'Shuffle Questions', false, false, false, 10, false);
        $this->number($entity, 'display_order', 'Display Order', false, true, false, 11, 0);
        $this->lookupCodeField($entity, 'status_code', 'Status', 'STATUS', true, true, true, 12, 'active');

        $this->textarea($entity, 'instructions', 'Instructions', false, false, false, 90);

        $this->filter($entity, 'assessment_id', 'Assessment', 'select', '=', 1, '/dynamic-options/assessments');
        $this->filter($entity, 'assessment_subject_id', 'Subject', 'select', '=', 2, '/dynamic-options/assessment-subjects');
        $this->filter($entity, 'status_code', 'Status', 'select', '=', 3, '/dynamic-options/lookups/STATUS');

        $this->standardActions($entity, 'assessment.section');
    }

    private function assessmentQuestions(): void
    {
        $entity = $this->entity(
            'Assessment',
            'Assessment Question',
            'assessment-questions',
            'assessment_questions',
            AssessmentQuestion::class,
            'Test Questions',
            'Assign questions to assessment sections.'
        );

        $this->selectField($entity, 'assessment_id', 'Assessment', '/dynamic-options/assessments', true, true, true, 1, null, null, 'assessment', 'title');
        $this->selectField($entity, 'assessment_section_id', 'Section', '/dynamic-options/assessment-sections', true, true, true, 2, 'assessment_id', 'assessment_id', 'section', 'section_title');
        $this->selectField($entity, 'question_id', 'Question', '/dynamic-options/questions', true, true, true, 3, null, null, 'question', 'question_text');

        $this->number($entity, 'marks', 'Marks', true, true, false, 4, 1);
        $this->number($entity, 'negative_marks', 'Negative Marks', false, true, false, 5, 0);
        $this->number($entity, 'time_seconds', 'Time Seconds', false, false, false, 6, null);
        $this->number($entity, 'display_order', 'Display Order', false, true, false, 7, 0);
        $this->switchField($entity, 'is_mandatory', 'Mandatory', false, true, true, 8, true);
        $this->lookupCodeField($entity, 'status_code', 'Status', 'STATUS', true, true, true, 9, 'active');

        $this->filter($entity, 'assessment_id', 'Assessment', 'select', '=', 1, '/dynamic-options/assessments');
        $this->filter($entity, 'assessment_section_id', 'Section', 'select', '=', 2, '/dynamic-options/assessment-sections');
        $this->filter($entity, 'status_code', 'Status', 'select', '=', 3, '/dynamic-options/lookups/STATUS');

        $this->standardActions($entity, 'assessment.test_question');
    }

    private function assessmentSchedules(): void
    {
        $entity = $this->entity(
            'Assessment',
            'Assessment Schedule',
            'assessment-schedules',
            'assessment_schedules',
            AssessmentSchedule::class,
            'Assessment Schedules',
            'Manage online/offline assessment schedules.'
        );

        $this->selectField($entity, 'assessment_id', 'Assessment', '/dynamic-options/assessments', true, true, true, 1, null, null, 'assessment', 'title');

        $this->text($entity, 'schedule_code', 'Schedule Code', true, true, true, 2, true);
        $this->text($entity, 'title', 'Title', true, true, true, 3);

        $this->dateTime($entity, 'start_at', 'Start At', true, true, true, 4);
        $this->dateTime($entity, 'end_at', 'End At', true, true, true, 5);
        $this->dateTime($entity, 'reporting_time', 'Reporting Time', false, false, false, 6);

        $this->text($entity, 'timezone', 'Timezone', true, false, false, 7);
        $this->lookupCodeField($entity, 'mode_code', 'Mode', 'ASSESSMENT_MODE', true, true, true, 8, 'online');

        $this->text($entity, 'venue_name', 'Venue Name', false, true, true, 9);
        $this->selectField($entity, 'campus_id', 'Campus', '/dynamic-options/campuses', false, false, true, 10, null, null, 'campus', 'name');
        $this->selectField($entity, 'building_id', 'Building', '/dynamic-options/buildings', false, false, true, 11, 'campus_id', 'campus_id', 'building', 'name');
        $this->selectField($entity, 'room_id', 'Room', '/dynamic-options/rooms', false, false, true, 12, 'building_id', 'building_id', 'room', 'name');

        $this->number($entity, 'max_candidates', 'Max Candidates', false, true, false, 13, null);
        $this->lookupCodeField($entity, 'status_code', 'Status', 'ASSESSMENT_SCHEDULE_STATUS', true, true, true, 14, 'scheduled');

        $this->textarea($entity, 'instructions', 'Instructions', false, false, false, 90);

        $this->filter($entity, 'assessment_id', 'Assessment', 'select', '=', 1, '/dynamic-options/assessments');
        $this->filter($entity, 'mode_code', 'Mode', 'select', '=', 2, '/dynamic-options/lookups/ASSESSMENT_MODE');
        $this->filter($entity, 'status_code', 'Status', 'select', '=', 3, '/dynamic-options/lookups/ASSESSMENT_SCHEDULE_STATUS');

        $this->standardActions($entity, 'assessment.schedule');
    }

    private function assessmentParticipants(): void
    {
        $entity = $this->entity(
            'Assessment',
            'Assessment Participant',
            'assessment-participants',
            'assessment_participants',
            AssessmentParticipant::class,
            'Assessment Participants',
            'Assign applicants, HR candidates, students or employees to assessments.'
        );

        $this->selectField($entity, 'assessment_id', 'Assessment', '/dynamic-options/assessments', true, true, true, 1, null, null, 'assessment', 'title');
        $this->selectField($entity, 'assessment_schedule_id', 'Schedule', '/dynamic-options/assessment-schedules', false, true, true, 2, 'assessment_id', 'assessment_id', 'schedule', 'title');

        $this->lookupCodeField($entity, 'participant_type_code', 'Participant Type', 'PARTICIPANT_TYPE', true, true, true, 3, 'applicant');
        $this->number($entity, 'participant_id', 'Participant ID', true, false, false, 4, null);

        $this->selectField($entity, 'applicant_id', 'Applicant', '/dynamic-options/applicants', false, true, true, 5, null, null, 'applicant', 'full_name');

        $this->text($entity, 'roll_no', 'Roll No', false, true, true, 6);
        $this->text($entity, 'seat_no', 'Seat No', false, true, true, 7);

        $this->lookupCodeField($entity, 'attendance_status_code', 'Attendance', 'ATTENDANCE_STATUS', true, true, true, 8, 'pending');
        $this->lookupCodeField($entity, 'attempt_status_code', 'Attempt', 'ATTEMPT_STATUS', true, true, true, 9, 'not_started');
        $this->lookupCodeField($entity, 'result_status_code', 'Result', 'RESULT_STATUS', true, true, true, 10, 'pending');

        $this->dateTime($entity, 'assigned_at', 'Assigned At', false, false, false, 11);
        $this->dateTime($entity, 'started_at', 'Started At', false, false, false, 12);
        $this->dateTime($entity, 'submitted_at', 'Submitted At', false, false, false, 13);
        $this->dateTime($entity, 'evaluated_at', 'Evaluated At', false, false, false, 14);

        $this->number($entity, 'obtained_marks', 'Obtained Marks', false, true, false, 15, null);
        $this->number($entity, 'percentage', 'Percentage', false, true, false, 16, null);
        $this->text($entity, 'grade_code', 'Grade', false, false, true, 17);

        $this->text($entity, 'import_batch_no', 'Import Batch No', false, false, true, 80);
        $this->textarea($entity, 'remarks', 'Remarks', false, false, false, 90);

        $this->filter($entity, 'assessment_id', 'Assessment', 'select', '=', 1, '/dynamic-options/assessments');
        $this->filter($entity, 'participant_type_code', 'Participant Type', 'select', '=', 2, '/dynamic-options/lookups/PARTICIPANT_TYPE');
        $this->filter($entity, 'attempt_status_code', 'Attempt Status', 'select', '=', 3, '/dynamic-options/lookups/ATTEMPT_STATUS');
        $this->filter($entity, 'result_status_code', 'Result Status', 'select', '=', 4, '/dynamic-options/lookups/RESULT_STATUS');

        $this->standardActions($entity, 'assessment.participant');
    }

    private function assessmentResults(): void
    {
        $entity = $this->entity(
            'Assessment',
            'Assessment Result',
            'assessment-results',
            'assessment_results',
            AssessmentResult::class,
            'Assessment Results',
            'View generated assessment results and analysis.'
        );

        $this->selectField($entity, 'assessment_id', 'Assessment', '/dynamic-options/assessments', true, true, true, 1, null, null, 'assessment', 'title');
        $this->selectField($entity, 'assessment_participant_id', 'Participant', '/dynamic-options/assessment-participants', true, true, true, 2, 'assessment_id', 'assessment_id', 'participant', 'roll_no');

        $this->number($entity, 'total_marks', 'Total Marks', true, true, false, 3, 0);
        $this->number($entity, 'obtained_marks', 'Obtained Marks', true, true, false, 4, 0);
        $this->number($entity, 'negative_marks', 'Negative Marks', true, false, false, 5, 0);
        $this->number($entity, 'final_marks', 'Final Marks', true, true, false, 6, 0);
        $this->number($entity, 'percentage', 'Percentage', true, true, false, 7, 0);

        $this->switchField($entity, 'is_passed', 'Passed', false, true, true, 8, false);
        $this->number($entity, 'rank', 'Rank', false, true, false, 9, null);
        $this->number($entity, 'percentile', 'Percentile', false, false, false, 10, null);
        $this->text($entity, 'grade_code', 'Grade', false, false, true, 11);

        $this->lookupCodeField($entity, 'result_status_code', 'Result Status', 'RESULT_STATUS', true, true, true, 12, 'generated');

        $this->dateTime($entity, 'generated_at', 'Generated At', false, false, false, 13);
        $this->dateTime($entity, 'approved_at', 'Approved At', false, false, false, 14);
        $this->dateTime($entity, 'published_at', 'Published At', false, false, false, 15);

        $this->textarea($entity, 'strengths_json', 'Strengths JSON', false, false, false, 80);
        $this->textarea($entity, 'weaknesses_json', 'Weaknesses JSON', false, false, false, 81);
        $this->textarea($entity, 'analysis_json', 'Analysis JSON', false, false, false, 82);
        $this->textarea($entity, 'remarks', 'Remarks', false, false, false, 90);

        $this->filter($entity, 'assessment_id', 'Assessment', 'select', '=', 1, '/dynamic-options/assessments');
        $this->filter($entity, 'result_status_code', 'Result Status', 'select', '=', 2, '/dynamic-options/lookups/RESULT_STATUS');
        $this->filter($entity, 'is_passed', 'Passed', 'select', '=', 3, null, [
            ['label' => 'Yes', 'value' => true],
            ['label' => 'No', 'value' => false],
        ]);

        $this->standardActions($entity, 'assessment.result');
    }

    private function entity(
        string $module,
        string $entityName,
        string $entityCode,
        string $tableName,
        string $modelClass,
        string $title,
        string $subtitle
    ): DynamicEntity {
        return DynamicEntity::updateOrCreate(
            ['entity_code' => $entityCode],
            [
                'module_name' => $module,
                'entity_name' => $entityName,
                'table_name' => $tableName,
                'model_class' => $modelClass,
                'api_endpoint' => "/dynamic/crud/{$entityCode}",
                'title' => $title,
                'subtitle' => $subtitle,
                'is_tenant_scoped' => true,
                'is_system' => false,
                'is_active' => true,
                'default_sort' => ['field' => 'id', 'direction' => 'desc'],
            ]
        );
    }

    private function text(
        DynamicEntity $entity,
        string $name,
        string $label,
        bool $required,
        bool $table,
        bool $filterable,
        int $order,
        bool $unique = false,
        bool $form = true
    ): void {
        DynamicField::updateOrCreate(
            ['dynamic_entity_id' => $entity->id, 'field_name' => $name],
            [
                'label' => $label,
                'control_type' => 'text',
                'data_type' => 'string',
                'is_required' => $required,
                'is_visible_in_table' => $table,
                'is_visible_in_form' => $form,
                'is_filterable' => $filterable,
                'is_sortable' => $table,
                'is_unique' => $unique,
                'display_order' => $order,
            ]
        );
    }

    private function textarea(
        DynamicEntity $entity,
        string $name,
        string $label,
        bool $required,
        bool $table,
        bool $filterable,
        int $order
    ): void {
        DynamicField::updateOrCreate(
            ['dynamic_entity_id' => $entity->id, 'field_name' => $name],
            [
                'label' => $label,
                'control_type' => 'textarea',
                'data_type' => 'string',
                'is_required' => $required,
                'is_visible_in_table' => $table,
                'is_visible_in_form' => true,
                'is_filterable' => $filterable,
                'is_sortable' => false,
                'display_order' => $order,
            ]
        );
    }

    private function number(
        DynamicEntity $entity,
        string $name,
        string $label,
        bool $required,
        bool $table,
        bool $filterable,
        int $order,
        mixed $default
    ): void {
        DynamicField::updateOrCreate(
            ['dynamic_entity_id' => $entity->id, 'field_name' => $name],
            [
                'label' => $label,
                'control_type' => 'number',
                'data_type' => 'decimal',
                'is_required' => $required,
                'is_visible_in_table' => $table,
                'is_visible_in_form' => true,
                'is_filterable' => $filterable,
                'is_sortable' => $table,
                'display_order' => $order,
                'default_value' => $default,
            ]
        );
    }

    private function dateTime(
        DynamicEntity $entity,
        string $name,
        string $label,
        bool $required,
        bool $table,
        bool $filterable,
        int $order
    ): void {
        DynamicField::updateOrCreate(
            ['dynamic_entity_id' => $entity->id, 'field_name' => $name],
            [
                'label' => $label,
                'control_type' => 'datetime',
                'data_type' => 'datetime',
                'is_required' => $required,
                'is_visible_in_table' => $table,
                'is_visible_in_form' => true,
                'is_filterable' => $filterable,
                'is_sortable' => $table,
                'display_order' => $order,
            ]
        );
    }

    private function switchField(
        DynamicEntity $entity,
        string $name,
        string $label,
        bool $required,
        bool $table,
        bool $filterable,
        int $order,
        bool $default
    ): void {
        DynamicField::updateOrCreate(
            ['dynamic_entity_id' => $entity->id, 'field_name' => $name],
            [
                'label' => $label,
                'control_type' => 'switch',
                'data_type' => 'boolean',
                'is_required' => $required,
                'is_visible_in_table' => $table,
                'is_visible_in_form' => true,
                'is_filterable' => $filterable,
                'is_sortable' => $table,
                'display_order' => $order,
                'default_value' => $default,
            ]
        );
    }

    private function selectField(
        DynamicEntity $entity,
        string $name,
        string $label,
        string $url,
        bool $required,
        bool $table,
        bool $filterable,
        int $order,
        ?string $dependsOn = null,
        ?string $dependencyParam = null,
        ?string $relationName = null,
        ?string $displayColumn = null
    ): void {
        DynamicField::updateOrCreate(
            ['dynamic_entity_id' => $entity->id, 'field_name' => $name],
            [
                'label' => $label,
                'control_type' => 'select',
                'data_type' => 'integer',
                'is_required' => $required,
                'is_visible_in_table' => $table,
                'is_visible_in_form' => true,
                'is_filterable' => $filterable,
                'is_sortable' => false,
                'options_source_type' => 'api',
                'options_source_url' => $url,
                'display_order' => $order,
                'meta' => [
                    'depends_on' => $dependsOn,
                    'dependency_param' => $dependencyParam ?? $dependsOn,
                    'clear_on_parent_change' => true,
                    'relation_name' => $relationName,
                    'display_column' => $displayColumn ?? 'name',
                ],
            ]
        );
    }

    private function lookupCodeField(
        DynamicEntity $entity,
        string $name,
        string $label,
        string $category,
        bool $required,
        bool $table,
        bool $filterable,
        int $order,
        mixed $default
    ): void {
        DynamicField::updateOrCreate(
            ['dynamic_entity_id' => $entity->id, 'field_name' => $name],
            [
                'label' => $label,
                'control_type' => 'select',
                'data_type' => 'string',
                'is_required' => $required,
                'is_visible_in_table' => $table,
                'is_visible_in_form' => true,
                'is_filterable' => $filterable,
                'is_sortable' => $table,
                'options_source_type' => 'api',
                'options_source_url' => "/dynamic-options/lookups/{$category}",
                'display_order' => $order,
                'default_value' => $default,
                'meta' => [
                    'value_column' => 'code',
                    'display_column' => 'name',
                ],
            ]
        );
    }

    private function filter(
        DynamicEntity $entity,
        string $name,
        string $label,
        string $control,
        string $operator,
        int $order,
        ?string $url = null,
        ?array $options = null
    ): void {
        DynamicFilter::updateOrCreate(
            ['dynamic_entity_id' => $entity->id, 'field_name' => $name],
            [
                'label' => $label,
                'control_type' => $control,
                'operator' => $operator,
                'options_source_type' => $url ? 'api' : ($options ? 'static' : null),
                'options_source_url' => $url,
                'options_static_json' => $options,
                'display_order' => $order,
                'is_active' => true,
            ]
        );
    }

    private function standardActions(DynamicEntity $entity, string $permissionPrefix): void
    {
        $actions = [
            ['name' => 'create', 'label' => 'Create', 'placement' => 'toolbar', 'permission' => $permissionPrefix . '.create', 'type' => 'modal', 'order' => 1],
            ['name' => 'edit', 'label' => 'Edit', 'placement' => 'row', 'permission' => $permissionPrefix . '.update', 'type' => 'modal', 'order' => 2],
            ['name' => 'delete', 'label' => 'Delete', 'placement' => 'row', 'permission' => $permissionPrefix . '.delete', 'type' => 'api', 'method' => 'DELETE', 'confirmation' => true, 'order' => 3],
        ];

        foreach ($actions as $action) {
            DynamicAction::updateOrCreate(
                ['dynamic_entity_id' => $entity->id, 'action_name' => $action['name']],
                [
                    'label' => $action['label'],
                    'placement' => $action['placement'],
                    'permission_name' => $action['permission'],
                    'action_type' => $action['type'],
                    'http_method' => $action['method'] ?? null,
                    'confirmation_required' => $action['confirmation'] ?? false,
                    'confirmation_title' => 'Are you sure?',
                    'confirmation_message' => 'This action cannot be undone.',
                    'is_active' => true,
                    'display_order' => $action['order'],
                ]
            );
        }
    }
}