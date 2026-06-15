<?php

namespace App\Core\RBAC\Services;

class PermissionModuleResolver
{
    public function resolve(string $permissionName): string
    {
        $prefix = explode('.', $permissionName)[0] ?? $permissionName;

        return match ($prefix) {
            'tenant' => 'tenant',
            'module' => 'modules',
            'rbac' => 'rbac',
            'menu' => 'menus',
            'dashboard' => 'dashboard',
            'settings' => 'settings',
            'audit' => 'audit',
            'academic' => 'academic',
            'subject' => 'subject',
            'student' => 'student',
            'admission' => 'admission',
            'enrollment' => 'enrollment',
            'attendance' => 'attendance',
            'exam' => 'exam',
            'fee' => 'fee',
            'accounts' => 'accounts',
            'hr' => 'hr',
            'payroll' => 'payroll',
            'timetable' => 'timetable',
            'notification' => 'notification',
            'report' => 'reports',
            'import' => 'import_export',
            'export' => 'import_export',
            default => $prefix,
        };
    }
}