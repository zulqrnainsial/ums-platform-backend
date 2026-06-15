<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class RBACPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            /*
            |--------------------------------------------------------------------------
            | Tenant
            |--------------------------------------------------------------------------
            */
            'tenant.view',
            'tenant.create',
            'tenant.update',
            'tenant.delete',
            'tenant.activate',
            'tenant.deactivate',
            'tenant.suspend',
            'tenant.assign_modules',

            /*
            |--------------------------------------------------------------------------
            | Authentication / Users
            |--------------------------------------------------------------------------
            */
            'user.view',
            'user.create',
            'user.update',
            'user.delete',
            'user.assign_roles',

            /*
            |--------------------------------------------------------------------------
            | RBAC
            |--------------------------------------------------------------------------
            */
            'rbac.role.view',
            'rbac.role.create',
            'rbac.role.update',
            'rbac.role.delete',
            'rbac.permission.view',
            'rbac.permission.assign',

            /*
            |--------------------------------------------------------------------------
            | Module Management
            |--------------------------------------------------------------------------
            */
            'module.view',
            'module.create',
            'module.update',
            'module.delete',
            'module.enable',
            'module.disable',

            /*
            |--------------------------------------------------------------------------
            | Menu Management
            |--------------------------------------------------------------------------
            */
            'menu.view',
            'menu.create',
            'menu.update',
            'menu.delete',
            'menu.assign_permission',

            /*
            |--------------------------------------------------------------------------
            | Academic
            |--------------------------------------------------------------------------
            */
            'academic.campus.view',
'academic.campus.create',
'academic.campus.update',
'academic.campus.delete',

'academic.building.view',
'academic.building.create',
'academic.building.update',
'academic.building.delete',

'academic.floor.view',
'academic.floor.create',
'academic.floor.update',
'academic.floor.delete',

'academic.room.view',
'academic.room.create',
'academic.room.update',
'academic.room.delete',
            'academic.faculty.view',
            'academic.faculty.create',
            'academic.faculty.update',
            'academic.faculty.delete',

            'academic.institute.view',
            'academic.institute.create',
            'academic.institute.update',
            'academic.institute.delete',

            'academic.department.view',
            'academic.department.create',
            'academic.department.update',
            'academic.department.delete',

            'academic.program_level.view',
            'academic.program_level.create',
            'academic.program_level.update',
            'academic.program_level.delete',

            'academic.program.view',
            'academic.program.create',
            'academic.program.update',
            'academic.program.delete',

            'academic.session.view',
            'academic.session.create',
            'academic.session.update',
            'academic.session.delete',

            'academic.term.view',
            'academic.term.create',
            'academic.term.update',
            'academic.term.delete',

            'academic.section.view',
            'academic.section.create',
            'academic.section.update',
            'academic.section.delete',

            /*
            |--------------------------------------------------------------------------
            | Subject / Course
            |--------------------------------------------------------------------------
            */
            'subject.type.view',
            'subject.type.create',
            'subject.type.update',
            'subject.type.delete',

            'subject.profile.view',
            'subject.profile.create',
            'subject.profile.update',
            'subject.profile.delete',

            'subject.curriculum.view',
            'subject.curriculum.create',
            'subject.curriculum.update',
            'subject.curriculum.delete',
            'subject.curriculum.assign_subjects',

            'subject.group.view',
            'subject.group.create',
            'subject.group.update',
            'subject.group.delete',

            'subject.subject.view',
            'subject.subject.create',
            'subject.subject.update',
            'subject.subject.delete',

            'subject.curriculum_subject.view',
            'subject.curriculum_subject.create',
            'subject.curriculum_subject.update',
            'subject.curriculum_subject.delete',

            'subject.prerequisite.view',
            'subject.prerequisite.create',
            'subject.prerequisite.update',
            'subject.prerequisite.delete',

            'subject.curriculum_elective_subject.view',
            'subject.curriculum_elective_subject.create',
            'subject.curriculum_elective_subject.update',
            'subject.curriculum_elective_subject.delete',

            /*
            |--------------------------------------------------------------------------
            | Lookup
            |--------------------------------------------------------------------------
            */
            'lookup.category.view',
            'lookup.category.create',
            'lookup.category.update',
            'lookup.category.delete',

            'lookup.value.view',
            'lookup.value.create',
            'lookup.value.update',
            'lookup.value.delete',

            /*
            |--------------------------------------------------------------------------
            | Student
            |--------------------------------------------------------------------------
            */
            'student.profile.view',
            'student.profile.create',
            'student.profile.update',
            'student.profile.delete',
            'student.document.view',
            'student.document.verify',
            'student.batch.view',
            'student.batch.create',
            'student.batch.update',
            'student.batch.delete',

            'student.student.view',
            'student.student.create',
            'student.student.update',
            'student.student.delete',

            'student.guardian.view',
            'student.guardian.create',
            'student.guardian.update',
            'student.guardian.delete',

            'student.student_guardian.view',
            'student.student_guardian.create',
            'student.student_guardian.update',
            'student.student_guardian.delete',

            'student.previous_education.view',
            'student.previous_education.create',
            'student.previous_education.update',
            'student.previous_education.delete',

            'student.document.view',
            'student.document.create',
            'student.document.update',
            'student.document.delete',

            'student.status_history.view',
            'student.status_history.create',
            'student.status_history.update',
            'student.status_history.delete',


            'student.portal.dashboard.view',
            'student.portal.profile.view',
            'student.portal.enrollment.view',
            'student.portal.courses.view',
            'student.portal.documents.view',
            'student.portal.requests.view',
            'student.portal.requests.create',

            'student.request.view',
            'student.request.review',
            'student.request.decide',

            /*
            |--------------------------------------------------------------------------
            | Admission
            |--------------------------------------------------------------------------
            */
            'admission.application.view',
            'admission.application.create',
            'admission.application.update',
            'admission.application.delete',
            'admission.application.review',
            'admission.application.approve',
            'admission.application.reject',
            'admission.application.convert_to_student',
            'admission.merit_list.view',
            'admission.merit_list.generate',

            'admission.session.view',
            'admission.session.create',
            'admission.session.update',
            'admission.session.delete',

            'admission.offered_program.view',
            'admission.offered_program.create',
            'admission.offered_program.update',
            'admission.offered_program.delete',

            'admission.quota_seat.view',
            'admission.quota_seat.create',
            'admission.quota_seat.update',
            'admission.quota_seat.delete',

            'admission.eligibility_rule_type.view',
            'admission.eligibility_rule_type.create',
            'admission.eligibility_rule_type.update',
            'admission.eligibility_rule_type.delete',

            'admission.eligibility_rule.view',
            'admission.eligibility_rule.create',
            'admission.eligibility_rule.update',
            'admission.eligibility_rule.delete',

            'admission.applicant.view',
            'admission.applicant.create',
            'admission.applicant.update',
            'admission.applicant.delete',
            'admission.applicant_qualification.view',
            'admission.applicant_qualification.create',
            'admission.applicant_qualification.update',
            'admission.applicant_qualification.delete',

            'admission.applicant_qualification_subject.view',
            'admission.applicant_qualification_subject.create',
            'admission.applicant_qualification_subject.update',
            'admission.applicant_qualification_subject.delete',

            'admission.applicant_experience.view',
            'admission.applicant_experience.create',
            'admission.applicant_experience.update',
            'admission.applicant_experience.delete',

            'admission.applicant_research_profile.view',
            'admission.applicant_research_profile.create',
            'admission.applicant_research_profile.update',
            'admission.applicant_research_profile.delete',

            'admission.applicant_publication.view',
            'admission.applicant_publication.create',
            'admission.applicant_publication.update',
            'admission.applicant_publication.delete',

            'admission.applicant_document.view',
            'admission.applicant_document.create',
            'admission.applicant_document.update',
            'admission.applicant_document.delete',

            'admission.applicant_test_result.view',
            'admission.applicant_test_result.create',
            'admission.applicant_test_result.update',
            'admission.applicant_test_result.delete',

            'admission.applicant_profile_step_status.view',
            'admission.applicant_profile_step_status.create',
            'admission.applicant_profile_step_status.update',
            'admission.applicant_profile_step_status.delete',

            'applicant.self_service.access',
            'admission.payment.view',
            'admission.payment.verify',
            'admission.payment.reject',

            'admission.preference_group.view',
            'admission.preference_group.create',
            'admission.preference_group.update',
            'admission.preference_group.delete',

            'admission.preference_group_program.view',
            'admission.preference_group_program.create',
            'admission.preference_group_program.update',
            'admission.preference_group_program.delete',
            /*
            |--------------------------------------------------------------------------
            | Enrollment
            |--------------------------------------------------------------------------
            */
            'enrollment.student.view',
            'enrollment.student.create',
            'enrollment.student.update',
            'enrollment.student.delete',
            'enrollment.subject_registration.view',
            'enrollment.subject_registration.create',
            'enrollment.subject_registration.update',
            'enrollment.subject_registration.delete',

            /*
            |--------------------------------------------------------------------------
            | Attendance
            |--------------------------------------------------------------------------
            */
            'attendance.student.view',
            'attendance.student.mark',
            'attendance.student.update',
            'attendance.employee.view',
            'attendance.employee.mark',
            'attendance.report.view',

            /*
            |--------------------------------------------------------------------------
            | Examination
            |--------------------------------------------------------------------------
            */
            'exam.setup.view',
            'exam.setup.create',
            'exam.setup.update',
            'exam.setup.delete',
            'exam.schedule.view',
            'exam.schedule.create',
            'exam.marks.view',
            'exam.marks.enter',
            'exam.marks.update',
            'exam.marks.lock',
            'exam.result.view',
            'exam.result.preview',
            'exam.result.declare',

            /*
            |--------------------------------------------------------------------------
            | Fee
            |--------------------------------------------------------------------------
            */
            'fee.head.view',
            'fee.head.create',
            'fee.head.update',
            'fee.head.delete',
            'fee.structure.view',
            'fee.structure.create',
            'fee.structure.update',
            'fee.structure.delete',
            'fee.voucher.view',
            'fee.voucher.generate',
            'fee.voucher.cancel',
            'fee.collection.view',
            'fee.collection.collect',
            'fee.collection.reverse',
            'fee.ledger.view',

            /*
            |--------------------------------------------------------------------------
            | Accounts
            |--------------------------------------------------------------------------
            */
            'accounts.group.view',
            'accounts.group.create',
            'accounts.group.update',
            'accounts.group.delete',
            'accounts.coa.view',
            'accounts.coa.create',
            'accounts.coa.update',
            'accounts.coa.delete',
            'accounts.voucher.view',
            'accounts.voucher.create',
            'accounts.voucher.update',
            'accounts.voucher.post',
            'accounts.voucher.cancel',
            'accounts.ledger.view',
            'accounts.trial_balance.view',

            /*
            |--------------------------------------------------------------------------
            | HR
            |--------------------------------------------------------------------------
            */
            'hr.employee.view',
            'hr.employee.create',
            'hr.employee.update',
            'hr.employee.delete',
            'hr.designation.view',
            'hr.designation.create',
            'hr.designation.update',
            'hr.designation.delete',

            /*
            |--------------------------------------------------------------------------
            | Payroll
            |--------------------------------------------------------------------------
            */
            'payroll.salary_head.view',
            'payroll.salary_head.create',
            'payroll.salary_head.update',
            'payroll.salary_head.delete',
            'payroll.structure.view',
            'payroll.structure.create',
            'payroll.structure.update',
            'payroll.run.view',
            'payroll.run.generate',
            'payroll.run.approve',
            'payroll.payment.pay',
            'payroll.payslip.view',

            /*
            |--------------------------------------------------------------------------
            | Timetable
            |--------------------------------------------------------------------------
            */
            'timetable.slot.view',
            'timetable.slot.create',
            'timetable.slot.update',
            'timetable.slot.delete',
            'timetable.allocation.view',
            'timetable.allocation.create',
            'timetable.allocation.update',
            'timetable.class.view',
            'timetable.class.create',
            'timetable.class.update',

            /*
            |--------------------------------------------------------------------------
            | Notifications
            |--------------------------------------------------------------------------
            */
            'notification.template.view',
            'notification.template.create',
            'notification.template.update',
            'notification.template.delete',
            'notification.send',
            'notification.history.view',

            /*
            |--------------------------------------------------------------------------
            | Reports
            |--------------------------------------------------------------------------
            */
            'report.academic.view',
            'report.student.view',
            'report.attendance.view',
            'report.exam.view',
            'report.fee.view',
            'report.accounts.view',
            'report.hr.view',
            'report.payroll.view',
            'report.audit.view',
            'report.export_pdf',
            'report.export_excel',

            /*
            |--------------------------------------------------------------------------
            | Settings
            |--------------------------------------------------------------------------
            */
            'settings.system.view',
            'settings.system.update',
            'settings.tenant.view',
            'settings.tenant.update',
            'settings.academic.view',
            'settings.academic.update',
            'settings.fee.view',
            'settings.fee.update',
            'settings.exam.view',
            'settings.exam.update',
            'settings.payroll.view',
            'settings.payroll.update',
            'settings.notification.view',
            'settings.notification.update',

            /*
            |--------------------------------------------------------------------------
            | Import / Export
            |--------------------------------------------------------------------------
            */
            'import.data.upload',
            'import.data.commit',
            'import.data.view_errors',
            'export.data.download',

            /*
            |--------------------------------------------------------------------------
            | Dashboard
            |--------------------------------------------------------------------------
            */
            'dashboard.view',
            'dashboard.widget.manage',

            /*
            |--------------------------------------------------------------------------
            | Audit
            |--------------------------------------------------------------------------
            */
            'audit.log.view',
            'audit.login_log.view',
            
            /*
            |--------------------------------------------------------------------------
            | User
            |--------------------------------------------------------------------------
            */
            
            'user.view',
'user.create',
'user.update',
'user.delete',
'user.activate',
'user.deactivate',
'user.assign_roles',
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                [
                    'name' => $permission,
                    'guard_name' => 'web',
                ],
                [
                    'name' => $permission,
                    'guard_name' => 'web',
                ]
            );
        }
    }
}