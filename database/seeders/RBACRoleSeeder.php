<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RBACRoleSeeder extends Seeder
{
    public function run(): void
    {
        $superAdminRole = Role::updateOrCreate(
            ['name' => 'Super Admin', 'guard_name' => 'web'],
            ['role_level' => 'system', 'is_system' => true]
        );

        $tenantAdminRole = Role::updateOrCreate(
            ['name' => 'Tenant Admin', 'guard_name' => 'web'],
            ['role_level' => 'tenant_admin', 'is_system' => true]
        );

        $registrarRole = Role::updateOrCreate(
            ['name' => 'Registrar', 'guard_name' => 'web'],
            ['role_level' => 'template', 'is_system' => true]
        );

        $accountantRole = Role::updateOrCreate(
            ['name' => 'Accountant', 'guard_name' => 'web'],
            ['role_level' => 'template', 'is_system' => true]
        );

        $teacherRole = Role::updateOrCreate(
            ['name' => 'Teacher', 'guard_name' => 'web'],
            ['role_level' => 'template', 'is_system' => true]
        );

        $studentRole = Role::updateOrCreate(
            ['name' => 'Student', 'guard_name' => 'web'],
            ['role_level' => 'template', 'is_system' => true]
        );

        $parentRole = Role::updateOrCreate(
            ['name' => 'Parent', 'guard_name' => 'web'],
            ['role_level' => 'template', 'is_system' => true]
        );

        $applicantRole = Role::updateOrCreate(
            ['name' => 'Applicant', 'guard_name' => 'web'],
            ['role_level' => 'template', 'is_system' => true]
        );

        $allPermissions = Permission::query()
            ->where('guard_name', 'web')
            ->pluck('name')
            ->toArray();

        $superAdminRole->syncPermissions($allPermissions);

        $tenantAdminPermissions = array_filter($allPermissions, function ($permission) {
            return !str_starts_with($permission, 'tenant.')
                && !str_starts_with($permission, 'module.');
        });

        $tenantAdminRole->syncPermissions($tenantAdminPermissions);

        $registrarRole->syncPermissions([
            'dashboard.view',
            'academic.faculty.view',
            'academic.department.view',
            'academic.program.view',
            'academic.session.view',
            'student.profile.view',
            'student.profile.create',
            'student.profile.update',
            'admission.application.view',
            'admission.application.create',
            'admission.application.update',
            'admission.application.review',
            'admission.application.approve',
            'admission.application.reject',
            'admission.application.convert_to_student',
            'enrollment.student.view',
            'enrollment.student.create',
            'enrollment.student.update',
            'enrollment.subject_registration.view',
            'enrollment.subject_registration.create',
            'report.student.view',
        ]);

        $accountantRole->syncPermissions([
            'dashboard.view',
            'student.profile.view',
            'fee.head.view',
            'fee.structure.view',
            'fee.voucher.view',
            'fee.voucher.generate',
            'fee.collection.view',
            'fee.collection.collect',
            'fee.ledger.view',
            'accounts.group.view',
            'accounts.coa.view',
            'accounts.voucher.view',
            'accounts.voucher.create',
            'accounts.voucher.post',
            'accounts.ledger.view',
            'accounts.trial_balance.view',
            'report.fee.view',
            'report.accounts.view',
        ]);

        $teacherRole->syncPermissions([
            'dashboard.view',
            'student.profile.view',
            'attendance.student.view',
            'attendance.student.mark',
            'exam.marks.view',
            'exam.marks.enter',
            'exam.result.view',
            'timetable.class.view',
        ]);

        $studentRole->syncPermissions([
            'dashboard.view',

            'student.portal.dashboard.view',
            'student.portal.profile.view',
            'student.portal.enrollment.view',
            'student.portal.courses.view',
            'student.portal.documents.view',
            'student.portal.requests.view',
            'student.portal.requests.create',

            'student.profile.view',
            'attendance.student.view',
            'exam.result.view',
            'fee.voucher.view',
            'fee.ledger.view',
            'timetable.class.view',
        ]);

        $parentRole->syncPermissions([
            'dashboard.view',
            'student.profile.view',
            'attendance.student.view',
            'exam.result.view',
            'fee.voucher.view',
            'fee.ledger.view',
        ]);

        $applicantRole->syncPermissions([
            'applicant.self_service.access',
        ]);

        $superAdmin = User::query()
            ->where('email', 'superadmin@ums.test')
            ->whereNull('tenant_id')
            ->first();

        if ($superAdmin) {
            $superAdmin->assignRole($superAdminRole);
        }
    }
}