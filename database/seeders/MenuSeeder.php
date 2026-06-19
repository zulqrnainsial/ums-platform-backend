<?php

namespace Database\Seeders;

use App\Core\Menu\Models\Menu;
use App\Core\Modules\Models\Module;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $moduleMap = Module::query()
            ->pluck('id', 'code')
            ->toArray();

        Menu::query()
            ->whereIn('code', [
                'admission_applicant_portal_test',
            ])
            ->delete();

        $menus = [
            [
                'title' => 'Dashboard',
                'code' => 'dashboard',
                'route' => '/dashboard',
                'icon' => 'DashboardOutlined',
                'permission_name' => 'dashboard.view',
                'module_code' => 'dashboard',
                'display_order' => 1,
                'children' => [],
            ],

            [
                'title' => 'System Administration',
                'code' => 'system_administration',
                'route' => null,
                'icon' => 'SettingOutlined',
                'permission_name' => null,
                'module_code' => 'settings',
                'display_order' => 2,
                'children' => [
                    [
                        'title' => 'Tenants',
                        'code' => 'system_tenants',
                        'route' => '/crud/tenants',
                        'icon' => 'ApartmentOutlined',
                        'permission_name' => 'tenant.view',
                        'module_code' => 'tenant',
                        'display_order' => 1,
                    ],
                    [
                        'title' => 'Modules',
                        'code' => 'system_modules',
                        'route' => '/crud/modules',
                        'icon' => 'AppstoreOutlined',
                        'permission_name' => 'module.view',
                        'module_code' => 'modules',
                        'display_order' => 2,
                    ],
                    [
                        'title' => 'Menus',
                        'code' => 'system_menus',
                        'route' => '/menus',
                        'icon' => 'MenuOutlined',
                        'permission_name' => 'menu.view',
                        'module_code' => 'menus',
                        'display_order' => 3,
                    ],
                    [
                        'title' => 'Settings',
                        'code' => 'system_settings',
                        'route' => '/crud/settings',
                        'icon' => 'SettingOutlined',
                        'permission_name' => 'settings.system.view',
                        'module_code' => 'settings',
                        'display_order' => 4,
                    ],
                    [
    'title' => 'Lookup Categories',
    'code' => 'lookup_categories',
    'route' => '/crud/lookup-categories',
    'icon' => 'TagsOutlined',
    'permission_name' => 'lookup.category.view',
    'module_code' => 'settings',
    'display_order' => 20,
],
[
    'title' => 'Lookup Values',
    'code' => 'lookup_values',
    'route' => '/crud/lookup-values',
    'icon' => 'UnorderedListOutlined',
    'permission_name' => 'lookup.value.view',
    'module_code' => 'settings',
    'display_order' => 21,
],
                    [
                        'title' => 'Audit Logs',
                        'code' => 'system_audit_logs',
                        'route' => '/crud/audit-logs',
                        'icon' => 'HistoryOutlined',
                        'permission_name' => 'audit.log.view',
                        'module_code' => 'audit',
                        'display_order' => 5,
                    ],
                    [
    'title' => 'Users',
    'code' => 'system_users',
    'route' => '/users',
    'icon' => 'UserOutlined',
    'permission_name' => 'user.view',
    'module_code' => 'auth',
    'display_order' => 2,
],
                ],
            ],

            [
                'title' => 'Access Control',
                'code' => 'access_control',
                'route' => null,
                'icon' => 'SafetyCertificateOutlined',
                'permission_name' => null,
                'module_code' => 'rbac',
                'display_order' => 3,
                'children' => [
                    [
                        'title' => 'Roles',
                        'code' => 'rbac_roles',
                        'route' => '/rbac/roles',
                        'icon' => 'TeamOutlined',
                        'permission_name' => 'rbac.role.view',
                        'module_code' => 'rbac',
                        'display_order' => 1,
                    ],
                    [
                        'title' => 'Permissions',
                        'code' => 'rbac_permissions',
                        'route' => '/rbac/permissions',
                        'icon' => 'SafetyOutlined',
                        'permission_name' => 'rbac.permission.view',
                        'module_code' => 'rbac',
                        'display_order' => 2,
                    ],
                ],
            ],

            [
                'title' => 'Academic Setup',
                'code' => 'academic_setup',
                'route' => null,
                'icon' => 'BankOutlined',
                'permission_name' => null,
                'module_code' => 'academic',
                'display_order' => 4,
                'children' => [
    [
        'title' => 'Campuses',
        'code' => 'academic_campuses',
        'route' => '/crud/campuses',
        'permission_name' => 'academic.campus.view',
        'module_code' => 'academic',
        'display_order' => 1,
    ],
    [
        'title' => 'Buildings',
        'code' => 'academic_buildings',
        'route' => '/crud/buildings',
        'permission_name' => 'academic.building.view',
        'module_code' => 'academic',
        'display_order' => 2,
    ],
    [
        'title' => 'Floors',
        'code' => 'academic_floors',
        'route' => '/crud/floors',
        'permission_name' => 'academic.floor.view',
        'module_code' => 'academic',
        'display_order' => 3,
    ],
    [
        'title' => 'Rooms',
        'code' => 'academic_rooms',
        'route' => '/crud/rooms',
        'permission_name' => 'academic.room.view',
        'module_code' => 'academic',
        'display_order' => 4,
    ],
    [
        'title' => 'Faculties',
        'code' => 'academic_faculties',
        'route' => '/crud/faculties',
        'permission_name' => 'academic.faculty.view',
        'module_code' => 'academic',
        'display_order' => 5,
    ],
    [
        'title' => 'Institutes',
        'code' => 'academic_institutes',
        'route' => '/crud/institutes',
        'permission_name' => 'academic.institute.view',
        'module_code' => 'academic',
        'display_order' => 6,
    ],
    [
        'title' => 'Departments',
        'code' => 'academic_departments',
        'route' => '/crud/departments',
        'permission_name' => 'academic.department.view',
        'module_code' => 'academic',
        'display_order' => 7,
    ],
    [
        'title' => 'Program Levels',
        'code' => 'academic_program_levels',
        'route' => '/crud/program-levels',
        'permission_name' => 'academic.program_level.view',
        'module_code' => 'academic',
        'display_order' => 8,
    ],
    [
        'title' => 'Programs',
        'code' => 'academic_programs',
        'route' => '/crud/programs',
        'permission_name' => 'academic.program.view',
        'module_code' => 'academic',
        'display_order' => 9,
    ],
    [
        'title' => 'Academic Sessions',
        'code' => 'academic_sessions',
        'route' => '/crud/academic-sessions',
        'permission_name' => 'academic.session.view',
        'module_code' => 'academic',
        'display_order' => 10,
    ],
    [
        'title' => 'Academic Terms',
        'code' => 'academic_terms',
        'route' => '/crud/academic-terms',
        'permission_name' => 'academic.term.view',
        'module_code' => 'academic',
        'display_order' => 11,
    ],
    [
        'title' => 'Sections',
        'code' => 'academic_sections',
        'route' => '/crud/sections',
        'permission_name' => 'academic.section.view',
        'module_code' => 'academic',
        'display_order' => 12,
    ],
],
            ],
[
    'title' => 'Subject Management',
    'code' => 'subject_management',
    'route' => null,
    'icon' => 'BookOutlined',
    'permission_name' => null,
    'module_code' => 'subject',
    'display_order' => 40,
    'children' => [
        [
            'title' => 'Subject Types',
            'code' => 'subject_types',
            'route' => '/crud/subject-types',
            'icon' => 'TagsOutlined',
            'permission_name' => 'subject.type.view',
            'module_code' => 'subject',
            'display_order' => 1,
        ],
        [
            'title' => 'Subject Groups',
            'code' => 'subject_groups',
            'route' => '/crud/subject-groups',
            'icon' => 'AppstoreOutlined',
            'permission_name' => 'subject.group.view',
            'module_code' => 'subject',
            'display_order' => 2,
        ],
        [
            'title' => 'Subjects / Courses',
            'code' => 'subjects',
            'route' => '/crud/subjects',
            'icon' => 'ReadOutlined',
            'permission_name' => 'subject.subject.view',
            'module_code' => 'subject',
            'display_order' => 3,
        ],
        /*[
    'title' => 'Curriculum Elective Subjects',
    'code' => 'curriculum_elective_subjects',
    'route' => '/crud/curriculum-elective-subjects',
    'icon' => 'BranchesOutlined',
    'permission_name' => 'subject.curriculum_elective_subject.view',
    'module_code' => 'subject',
    'display_order' => 7,
],*/
        [
            'title' => 'Curriculums',
            'code' => 'curriculums',
            'route' => '/crud/curriculums',
            'icon' => 'ProfileOutlined',
            'permission_name' => 'subject.curriculum.view',
            'module_code' => 'subject',
            'display_order' => 4,
        ],
        /*[
    'title' => 'Bulk Assign Curriculum Subjects',
    'code' => 'bulk_assign_curriculum_subjects',
    'route' => '/subject/curriculum-subject-bulk-assign',
    'icon' => 'PlusSquareOutlined',
    'permission_name' => 'subject.curriculum_subject.create',
    'module_code' => 'subject',
    'display_order' => 5,
],*/
        [
            'title' => 'Curriculum Subjects',
            'code' => 'curriculum_subjects',
            'route' => '/subject/curriculum-subjects',
            'icon' => 'OrderedListOutlined',
            'permission_name' => 'subject.curriculum_subject.view',
            'module_code' => 'subject',
            'display_order' => 6,
        ],
        [
            'title' => 'Subject Prerequisites',
            'code' => 'subject_prerequisites',
            'route' => '/crud/subject-prerequisites',
            'icon' => 'BranchesOutlined',
            'permission_name' => 'subject.prerequisite.view',
            'module_code' => 'subject',
            'display_order' => 7,
        ],
    ],
],
            [
    'title' => 'Student Management',
    'code' => 'student_management',
    'route' => null,
    'icon' => 'UsergroupAddOutlined',
    'permission_name' => null,
    'module_code' => 'student',
    'display_order' => 7,
    'children' => [
        [
            'title' => 'Student Batches',
            'code' => 'student_batches',
            'route' => '/crud/student-batches',
            'icon' => 'TeamOutlined',
            'permission_name' => 'student.batch.view',
            'module_code' => 'student',
            'display_order' => 1,
        ],
        [
            'title' => 'Students',
            'code' => 'student_list',
            'route' => '/student-management/students',
            'icon' => 'UserOutlined',
            'permission_name' => 'student.student.view',
            'module_code' => 'student',
            'display_order' => 2,
        ],
        [
            'title' => 'Bulk Course Registration',
            'code' => 'bulk_course_registration',
            'route' => '/student-management/bulk-course-registration',
            'icon' => 'UserOutlined',
            'permission_name' => 'student.course-registration.bulk',
            'module_code' => 'student',
            'display_order' => 2.12,
        ],
        [
            'title' => 'Enrollments',
            'code' => 'student_academic_enrollments',
            'route' => '/student-management/enrollments',
            'icon' => 'ProfileOutlined',
            'permission_name' => 'student.student.view',
            'module_code' => 'student',
            'display_order' => 2.1,
        ],
        [
            'title' => 'Section / Batch Allocation',
            'code' => 'student_section_batch_allocation',
            'route' => '/student-management/section-batch-allocation',
            'icon' => 'PartitionOutlined',
            'permission_name' => 'student.batch.update',
            'module_code' => 'student',
            'display_order' => 2.2,
        ],
        [
            'title' => 'Lifecycle Actions',
            'code' => 'student_lifecycle_actions',
            'route' => '/student-management/lifecycle-actions',
            'icon' => 'SyncOutlined',
            'permission_name' => 'student.status_history.create',
            'module_code' => 'student',
            'display_order' => 2.3,
        ],
        [
            'title' => 'Student Requests',
            'code' => 'student_requests_admin',
            'route' => '/student-management/student-requests',
            'icon' => 'FormOutlined',
            'permission_name' => 'student.request.view',
            'module_code' => 'student',
            'display_order' => 2.4,
        ],
        [
            'title' => 'Guardians',
            'code' => 'student_guardians_master',
            'route' => '/crud/guardians',
            'icon' => 'ContactsOutlined',
            'permission_name' => 'student.guardian.view',
            'module_code' => 'student',
            'display_order' => 3,
        ],
        [
            'title' => 'Student Guardians',
            'code' => 'student_guardian_links',
            'route' => '/crud/student-guardians',
            'icon' => 'SolutionOutlined',
            'permission_name' => 'student.student_guardian.view',
            'module_code' => 'student',
            'display_order' => 4,
        ],
        [
            'title' => 'Previous Education',
            'code' => 'student_previous_education',
            'route' => '/crud/student-previous-educations',
            'icon' => 'ReadOutlined',
            'permission_name' => 'student.previous_education.view',
            'module_code' => 'student',
            'display_order' => 5,
        ],
        [
            'title' => 'Student Documents',
            'code' => 'student_document_records',
            'route' => '/crud/student-documents',
            'icon' => 'FileTextOutlined',
            'permission_name' => 'student.document.view',
            'module_code' => 'student',
            'display_order' => 6,
        ],
        [
            'title' => 'Status History',
            'code' => 'student_status_history',
            'route' => '/crud/student-status-histories',
            'icon' => 'HistoryOutlined',
            'permission_name' => 'student.status_history.view',
            'module_code' => 'student',
            'display_order' => 7,
        ],
    ],
],


            [
                'title' => 'Student Portal',
                'code' => 'student_portal',
                'route' => null,
                'icon' => 'UserOutlined',
                'permission_name' => null,
                'module_code' => 'student',
                'display_order' => 8,
                'children' => [
                    [
                        'title' => 'Dashboard',
                        'code' => 'student_portal_dashboard',
                        'route' => '/student-portal/dashboard',
                        'icon' => 'DashboardOutlined',
                        'permission_name' => 'student.portal.dashboard.view',
                        'module_code' => 'student',
                        'display_order' => 1,
                    ],
                    [
                        'title' => 'My Profile',
                        'code' => 'student_portal_profile',
                        'route' => '/student-portal/profile',
                        'icon' => 'UserOutlined',
                        'permission_name' => 'student.portal.profile.view',
                        'module_code' => 'student',
                        'display_order' => 2,
                    ],
                    [
                        'title' => 'My Enrollment',
                        'code' => 'student_portal_enrollment',
                        'route' => '/student-portal/enrollment',
                        'icon' => 'ProfileOutlined',
                        'permission_name' => 'student.portal.enrollment.view',
                        'module_code' => 'student',
                        'display_order' => 3,
                    ],
                    [
                        'title' => 'My Courses',
                        'code' => 'student_portal_courses',
                        'route' => '/student-portal/courses',
                        'icon' => 'BookOutlined',
                        'permission_name' => 'student.portal.courses.view',
                        'module_code' => 'student',
                        'display_order' => 4,
                    ],
                    [
                        'title' => 'Course Registration',
                        'code' => 'course_registration',
                        'route' => '/student-portal/course-registration',
                        'icon' => 'BookOutlined',
                        'permission_name' => 'student.portal.course-registration',
                        'module_code' => 'student',
                        'display_order' => 4.1,
                    ],
                    [
                        'title' => 'My Documents',
                        'code' => 'student_portal_documents',
                        'route' => '/student-portal/documents',
                        'icon' => 'FileTextOutlined',
                        'permission_name' => 'student.portal.documents.view',
                        'module_code' => 'student',
                        'display_order' => 5,
                    ],
                    [
                        'title' => 'My Requests',
                        'code' => 'student_portal_requests',
                        'route' => '/student-portal/requests',
                        'icon' => 'FormOutlined',
                        'permission_name' => 'student.portal.requests.view',
                        'module_code' => 'student',
                        'display_order' => 6,
                    ],
                ],
            ],


            [
                'title' => 'Admission Management',
                'code' => 'admission_management',
                'route' => null,
                'icon' => 'SolutionOutlined',
                'permission_name' => null,
                'module_code' => 'admission',
                'display_order' => 6,
                'children' => [
                    [
                        'title' => 'Admission Sessions',
                        'code' => 'admission_sessions',
                        'route' => '/crud/admission-sessions',
                        'icon' => 'CalendarOutlined',
                        'permission_name' => 'admission.session.view',
                        'module_code' => 'admission',
                        'display_order' => 1,
                    ],
                    [
                        'title' => 'Offered Programs',
                        'code' => 'admission_offered_programs',
                        'route' => '/crud/offered-programs',
                        'icon' => 'ApartmentOutlined',
                        'permission_name' => 'admission.offered_program.view',
                        'module_code' => 'admission',
                        'display_order' => 2,
                    ],
                    [
                        'title' => 'Program Quota Seats',
                        'code' => 'admission_program_quota_seats',
                        'route' => '/crud/program-quota-seats',
                        'icon' => 'TableOutlined',
                        'permission_name' => 'admission.quota_seat.view',
                        'module_code' => 'admission',
                        'display_order' => 3,
                    ],
                    [
                        'title' => 'Preference Groups',
                        'code' => 'admission_preference_groups',
                        'route' => '/crud/admission-preference-groups',
                        'icon' => 'ClusterOutlined',
                        'permission_name' => 'admission.preference_group.view',
                        'module_code' => 'admission',
                        'display_order' => 4,
                    ],
                    [
                        'title' => 'Preference Group Programs',
                        'code' => 'admission_preference_group_programs',
                        'route' => '/crud/admission-preference-group-programs',
                        'icon' => 'PartitionOutlined',
                        'permission_name' => 'admission.preference_group_program.view',
                        'module_code' => 'admission',
                        'display_order' => 5,
                    ],
                    [
                        'title' => 'Eligibility Builder',
                        'code' => 'admission_eligibility_builder',
                        'route' => '/admission/eligibility-builder',
                        'icon' => 'ControlOutlined',
                        'permission_name' => 'admission.eligibility_rule.update',
                        'module_code' => 'admission',
                        'display_order' => 6,
                    ],
                    [
                        'title' => 'Applicants',
                        'code' => 'admission_applicants',
                        'route' => '/crud/applicants',
                        'icon' => 'UserAddOutlined',
                        'permission_name' => 'admission.applicant.view',
                        'module_code' => 'admission',
                        'display_order' => 7,
                    ],
                    [
                        'title' => 'Applicant Progress',
                        'code' => 'admission_applicant_progress',
                        'route' => '/admission/applicant-progress',
                        'icon' => 'DashboardOutlined',
                        'permission_name' => 'admission.applicant.view',
                        'module_code' => 'admission',
                        'display_order' => 8,
                    ],
                    [
                        'title' => 'Applications',
                        'code' => 'admission_applicant_program_applications',
                        'route' => '/crud/applicant-program-applications',
                        'icon' => 'FileDoneOutlined',
                        'permission_name' => 'admission.application.view',
                        'module_code' => 'admission',
                        'display_order' => 9,
                    ],
                    [
                        'title' => 'Payment Verification',
                        'code' => 'admission_payment_verification',
                        'route' => '/admission/payment-verification',
                        'icon' => 'CheckCircleOutlined',
                        'permission_name' => 'admission.payment.verify',
                        'module_code' => 'admission',
                        'display_order' => 10,
                    ],
                    [
                        'title' => 'Merit Formulas',
                        'code' => 'admission_merit_formulas',
                        'route' => '/crud/admission-merit-formulas',
                        'icon' => 'CalculatorOutlined',
                        'permission_name' => 'admission.merit_formula.view',
                        'display_order' => 120,
                    ],
                    [
                        'title' => 'Merit Formula Components',
                        'code' => 'admission_merit_formula_components',
                        'route' => '/crud/admission-merit-formula-components',
                        'icon' => 'FunctionOutlined',
                        'permission_name' => 'admission.merit_formula_component.view',
                        'display_order' => 130,
                    ],
                    [
                        'title' => 'Merit Formula Applicability',
                        'code' => 'admission_merit_formula_applicability',
                        'route' => '/crud/admission-merit-formula-applicabilities',
                        'icon' => 'BranchesOutlined',
                        'permission_name' => 'admission.merit_formula_applicability.view',
                        'display_order' => 140,
                    ],
                    [
                        'title' => 'Merit Formula Builder',
                        'code' => 'admission_merit_builder',
                        'route' => '/admission/merit-builder',
                        'icon' => 'CalculatorOutlined',
                        'permission_name' => 'admission.merit_formula.view',
                        'module_code' => 'admission',
                        'display_order' => 118,
                    ],
                    [
                        'title' => 'Merit Calculation',
                        'code' => 'admission_merit_calculation',
                        'route' => '/admission/merit-calculation',
                        'icon' => 'PercentageOutlined',
                        'permission_name' => 'admission.merit_calculation.view',
                        'module_code' => 'admission',
                        'display_order' => 119,
                    ],
                    [
                        'title' => 'Merit Lists',
                        'code' => 'admission_merit_lists',
                        'route' => '/admission/merit-lists',
                        'icon' => 'OrderedListOutlined',
                        'permission_name' => 'admission.merit_list.view',
                        'module_code' => 'admission',
                        'display_order' => 120,
                    ],
                ],
            ],

[
    'title' => 'Assessment Management',
    'code' => 'assessment_management',
    'route' => null,
    'icon' => 'ReadOutlined',
    'permission_name' => 'assessment.test.view',
    'module_code' => 'assessment',
    'display_order' => 80,
    'children' => [
        [
            'title' => 'Assessment Categories',
            'code' => 'assessment_categories',
            'route' => '/crud/assessment-categories',
            'icon' => 'TagsOutlined',
            'permission_name' => 'assessment.category.view',
            'module_code' => 'assessment',
            'display_order' => 1,
        ],
        [
            'title' => 'Assessment Subjects',
            'code' => 'assessment_subjects',
            'route' => '/crud/assessment-subjects',
            'icon' => 'BookOutlined',
            'permission_name' => 'assessment.subject.view',
            'module_code' => 'assessment',
            'display_order' => 2,
        ],
        [
            'title' => 'Assessment Topics',
            'code' => 'assessment_topics',
            'route' => '/crud/assessment-topics',
            'icon' => 'BranchesOutlined',
            'permission_name' => 'assessment.topic.view',
            'module_code' => 'assessment',
            'display_order' => 3,
        ],
        [
            'title' => 'Question Banks',
            'code' => 'assessment_question_banks',
            'route' => '/crud/question-banks',
            'icon' => 'DatabaseOutlined',
            'permission_name' => 'assessment.question_bank.view',
            'module_code' => 'assessment',
            'display_order' => 4,
        ],
        [
            'title' => 'Questions',
            'code' => 'assessment_questions',
            'route' => '/assessment/questions',
            'icon' => 'QuestionCircleOutlined',
            'permission_name' => 'assessment.question.view',
            'module_code' => 'assessment',
            'display_order' => 5,
        ],
        [
            'title' => 'Question Import',
            'code' => 'assessment_question_import',
            'route' => '/assessment/question-import',
            'icon' => 'UploadOutlined',
            'permission_name' => 'assessment.question.import',
            'module_code' => 'assessment',
            'display_order' => 6,
        ],
        [
            'title' => 'Assessments / Tests',
            'code' => 'assessments',
            'route' => '/crud/assessments',
            'icon' => 'FileTextOutlined',
            'permission_name' => 'assessment.test.view',
            'module_code' => 'assessment',
            'display_order' => 7,
        ],
        [
            'title' => 'Test Builder',
            'code' => 'assessment_test_builder',
            'route' => '/assessment/test-builder',
            'icon' => 'BuildOutlined',
            'permission_name' => 'assessment.test.update',
            'module_code' => 'assessment',
            'display_order' => 8,
        ],
        [
            'title' => 'Assessment Sections',
            'code' => 'assessment_sections',
            'route' => '/crud/assessment-sections',
            'icon' => 'PartitionOutlined',
            'permission_name' => 'assessment.section.view',
            'module_code' => 'assessment',
            'display_order' => 9,
        ],
        [
            'title' => 'Test Questions',
            'code' => 'assessment_test_questions',
            'route' => '/crud/assessment-questions',
            'icon' => 'OrderedListOutlined',
            'permission_name' => 'assessment.test_question.view',
            'module_code' => 'assessment',
            'display_order' => 10,
        ],
        [
            'title' => 'Participant Assignment',
            'code' => 'assessment_participant_assignment',
            'route' => '/assessment/participant-assignment',
            'icon' => 'UsergroupAddOutlined',
            'permission_name' => 'assessment.participant.bulk_assign',
            'module_code' => 'assessment',
            'display_order' => 11,
        ],
        [
            'title' => 'Schedules',
            'code' => 'assessment_schedules',
            'route' => '/crud/assessment-schedules',
            'icon' => 'CalendarOutlined',
            'permission_name' => 'assessment.schedule.view',
            'module_code' => 'assessment',
            'display_order' => 12,
        ],
        [
            'title' => 'Participants',
            'code' => 'assessment_participants',
            'route' => '/crud/assessment-participants',
            'icon' => 'TeamOutlined',
            'permission_name' => 'assessment.participant.view',
            'module_code' => 'assessment',
            'display_order' => 13,
        ],
        [
            'title' => 'Attempts',
            'code' => 'assessment_attempts',
            'route' => '/crud/assessment-attempts',
            'icon' => 'FieldTimeOutlined',
            'permission_name' => 'assessment.attempt.view',
            'module_code' => 'assessment',
            'display_order' => 14,
        ],
        [
            'title' => 'Manual Marking',
            'code' => 'assessment_manual_marking',
            'route' => '/assessment/manual-marking',
            'icon' => 'EditOutlined',
            'permission_name' => 'assessment.manual_marking.view',
            'module_code' => 'assessment',
            'display_order' => 15,
        ],
        [
            'title' => 'Results',
            'code' => 'assessment_results',
            'route' => '/crud/assessment-results',
            'icon' => 'TrophyOutlined',
            'permission_name' => 'assessment.result.view',
            'module_code' => 'assessment',
            'display_order' => 16,
        ],
        [
            'title' => 'Analytics',
            'code' => 'assessment_analytics',
            'route' => '/assessment/analytics',
            'icon' => 'BarChartOutlined',
            'permission_name' => 'assessment.analytics.view',
            'module_code' => 'assessment',
            'display_order' => 17,
        ],
    ],
],
            [
                'title' => 'Enrollment',
                'code' => 'enrollment',
                'route' => null,
                'icon' => 'ProfileOutlined',
                'permission_name' => null,
                'module_code' => 'enrollment',
                'display_order' => 7,
                'children' => [
                    ['title' => 'Enroll Students', 'code' => 'enrollment_students', 'route' => '/crud/enrollments', 'permission_name' => 'enrollment.student.view', 'module_code' => 'enrollment', 'display_order' => 1],
                    ['title' => 'Subject Registration', 'code' => 'enrollment_subject_registration', 'route' => '/crud/student-subject-registrations', 'permission_name' => 'enrollment.subject_registration.view', 'module_code' => 'enrollment', 'display_order' => 2],
                ],
            ],

            [
                'title' => 'Fees',
                'code' => 'fees',
                'route' => null,
                'icon' => 'DollarOutlined',
                'permission_name' => null,
                'module_code' => 'fee',
                'display_order' => 9,
                'children' => [
                    ['title' => 'Fee Heads', 'code' => 'fee_heads', 'route' => '/crud/fee-heads', 'permission_name' => 'fee.head.view', 'module_code' => 'fee', 'display_order' => 1],
                    ['title' => 'Fee Structures', 'code' => 'fee_structures', 'route' => '/crud/fee-structures', 'permission_name' => 'fee.structure.view', 'module_code' => 'fee', 'display_order' => 2],
                    ['title' => 'Fee Collection', 'code' => 'fee_collection', 'route' => '/fees/collection', 'permission_name' => 'fee.collection.view', 'module_code' => 'fee', 'display_order' => 3],
                ],
            ],
        ];

        foreach ($menus as $menu) {
            $this->createMenu($menu, null, $moduleMap);
        }
        $this->cleanupAssessmentMenus();
    }
private function cleanupAssessmentMenus(): void
{
    /*
     | Keep Assessment Management simple and operational.
     | Broken / generic CRUD links are removed from tenant admin menu.
     */

    $assessmentParent = DB::table('menus')
        ->where('code', 'assessment_management')
        ->first();

    if (!$assessmentParent) {
        $assessmentParentId = DB::table('menus')->insertGetId($this->menuPayload([
            'title' => 'Assessment Management',
            'code' => 'assessment_management',
            'route' => null,
            'icon' => 'FileSearchOutlined',
            'permission_name' => null,
            'module_code' => 'assessment',
            'display_order' => 60,
            'parent_id' => null,
            'is_active' => 1,
        ]));
    } else {
        $assessmentParentId = $assessmentParent->id;

        DB::table('menus')
            ->where('id', $assessmentParentId)
            ->update($this->menuPayload([
                'title' => 'Assessment Management',
                'code' => 'assessment_management',
                'route' => null,
                'icon' => 'FileSearchOutlined',
                'permission_name' => null,
                'module_code' => 'assessment',
                'display_order' => 60,
                'parent_id' => null,
                'is_active' => 1,
            ], true));
    }

    /*
     | Remove / hide broken and confusing menu items.
     | We use both code and route because previous seeders may have used different codes.
     */
    $brokenCodes = [
        'assessment_question_import',
        'question_import',
        'assessment_participants',
        'assessment_attempts',
        'assessment_results',
        'assessment_analytics',
        'analytics',
    ];

    $brokenRoutes = [
        '/assessment/question-import',
        '/crud/assessment-participants',
        '/crud/assessment-attempts',
        '/crud/assessment-results',
        '/assessment/analytics',
    ];

    if (Schema::hasColumn('menus', 'is_active')) {
        DB::table('menus')
            ->whereIn('code', $brokenCodes)
            ->orWhereIn('route', $brokenRoutes)
            ->update(['is_active' => 0, 'updated_at' => now()]);
    } else {
        DB::table('menus')
            ->whereIn('code', $brokenCodes)
            ->orWhereIn('route', $brokenRoutes)
            ->delete();
    }

    /*
     | Rebuild the clean working Assessment menu.
     */
    $items = [
        [
            'title' => 'Assessment Categories',
            'code' => 'assessment_categories',
            'route' => '/crud/assessment-categories',
            'icon' => 'AppstoreOutlined',
            'permission_name' => 'assessment.category.view',
            'display_order' => 10,
        ],
        [
            'title' => 'Assessment Subjects',
            'code' => 'assessment_subjects',
            'route' => '/crud/assessment-subjects',
            'icon' => 'BookOutlined',
            'permission_name' => 'assessment.subject.view',
            'display_order' => 20,
        ],
        [
            'title' => 'Assessment Topics',
            'code' => 'assessment_topics',
            'route' => '/crud/assessment-topics',
            'icon' => 'PartitionOutlined',
            'permission_name' => 'assessment.topic.view',
            'display_order' => 30,
        ],
        [
            'title' => 'Question Banks',
            'code' => 'assessment_question_banks',
            'route' => '/crud/question-banks',
            'icon' => 'DatabaseOutlined',
            'permission_name' => 'assessment.question_bank.view',
            'display_order' => 40,
        ],
        [
            'title' => 'Questions',
            'code' => 'assessment_questions',
            'route' => '/assessment/questions',
            'icon' => 'QuestionCircleOutlined',
            'permission_name' => 'assessment.question.view',
            'display_order' => 50,
        ],
        [
            'title' => 'Assessments / Tests',
            'code' => 'assessments_tests',
            'route' => '/crud/assessments',
            'icon' => 'FileTextOutlined',
            'permission_name' => 'assessment.assessment.view',
            'display_order' => 60,
        ],
        [
            'title' => 'Test Builder',
            'code' => 'assessment_test_builder',
            'route' => '/assessment/test-builder',
            'icon' => 'BuildOutlined',
            'permission_name' => 'assessment.test.update',
            'display_order' => 70,
        ],
        [
            'title' => 'Assessment Schedules',
            'code' => 'assessment_schedules',
            'route' => '/crud/assessment-schedules',
            'icon' => 'CalendarOutlined',
            'permission_name' => 'assessment.schedule.view',
            'display_order' => 80,
        ],
        [
            'title' => 'Participant Assignment',
            'code' => 'assessment_participant_assignment',
            'route' => '/assessment/participant-assignment',
            'icon' => 'UsergroupAddOutlined',
            'permission_name' => 'assessment.participant.bulk_assign',
            'display_order' => 90,
        ],
        [
            'title' => 'Attempts',
            'code' => 'assessment_attempts_viewer',
            'route' => '/assessment/attempts',
            'icon' => 'EyeOutlined',
            'permission_name' => 'assessment.attempt.view',
            'display_order' => 100,
        ],
        [
            'title' => 'Results',
            'code' => 'assessment_results_viewer',
            'route' => '/assessment/results',
            'icon' => 'BarChartOutlined',
            'permission_name' => 'assessment.result.view',
            'display_order' => 110,
        ],
        [
            'title' => 'Analytics',
            'code' => 'assessment_analytics_dashboard',
            'route' => '/assessment/analytics',
            'icon' => 'DashboardOutlined',
            'permission_name' => 'assessment.analytics.view',
            'display_order' => 120,
        ],
        [
            'title' => 'Manual Marking',
            'code' => 'assessment_manual_marking',
            'route' => '/assessment/manual-marking',
            'icon' => 'EditOutlined',
            'permission_name' => 'assessment.manual_marking.view',
            'display_order' => 130,
        ],
    ];

    foreach ($items as $item) {
        $payload = $this->menuPayload([
            'title' => $item['title'],
            'code' => $item['code'],
            'route' => $item['route'],
            'icon' => $item['icon'],
            'permission_name' => $item['permission_name'],
            'module_code' => 'assessment',
            'display_order' => $item['display_order'],
            'parent_id' => $assessmentParentId,
            'is_active' => 1,
        ]);

        $existing = DB::table('menus')
            ->where('code', $item['code'])
            ->first();

        if ($existing) {
            DB::table('menus')
                ->where('id', $existing->id)
                ->update($this->menuPayload([
                    ...$payload,
                    'updated_at' => now(),
                ], true));
        } else {
            DB::table('menus')->insert($payload);
        }
    }
}
private function menuPayload(array $data, bool $forUpdate = false): array
{
    $payload = [];

    foreach ($data as $column => $value) {
        if (Schema::hasColumn('menus', $column)) {
            $payload[$column] = $value;
        }
    }

    if (!$forUpdate && Schema::hasColumn('menus', 'created_at')) {
        $payload['created_at'] = now();
    }

    if (Schema::hasColumn('menus', 'updated_at')) {
        $payload['updated_at'] = now();
    }

    return $payload;
}
    private function createMenu(array $menu, ?int $parentId, array $moduleMap): void
    {
        $children = $menu['children'] ?? [];

        $moduleCode = $menu['module_code'] ?? null;

        unset($menu['children'], $menu['module_code']);

        $createdMenu = Menu::updateOrCreate(
            ['code' => $menu['code']],
            [
                ...$menu,
                'parent_id' => $parentId,
                'module_id' => $moduleCode ? ($moduleMap[$moduleCode] ?? null) : null,
                'is_system' => true,
                'is_active' => true,
            ]
        );

        foreach ($children as $child) {
            $this->createMenu($child, $createdMenu->id, $moduleMap);
        }
    }
}