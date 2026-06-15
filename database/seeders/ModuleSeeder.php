<?php

namespace Database\Seeders;

use App\Core\Modules\Models\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            ['name' => 'Tenant Management', 'code' => 'tenant', 'icon' => 'ApartmentOutlined', 'is_core' => true, 'display_order' => 1],
            ['name' => 'Authentication', 'code' => 'auth', 'icon' => 'LoginOutlined', 'is_core' => true, 'display_order' => 2],
            ['name' => 'Role-Based Access Control', 'code' => 'rbac', 'icon' => 'SafetyCertificateOutlined', 'is_core' => true, 'display_order' => 3],
            ['name' => 'Module Management', 'code' => 'modules', 'icon' => 'AppstoreOutlined', 'is_core' => true, 'display_order' => 4],
            ['name' => 'Dynamic Menu Management', 'code' => 'menus', 'icon' => 'MenuOutlined', 'is_core' => true, 'display_order' => 5],
            ['name' => 'Academic Structure', 'code' => 'academic', 'icon' => 'BankOutlined', 'is_core' => false, 'display_order' => 6],
            ['name' => 'Student Management', 'code' => 'student', 'icon' => 'UserOutlined', 'is_core' => false, 'display_order' => 7],
            [
                'name' => 'Admission Management',
                'code' => 'admission',
                'icon' => 'SolutionOutlined',
                'description' => 'Admission sessions, offered programs, eligibility, applicants, and applications.',
                'is_core' => false,
                'display_order' => 8,
            ],
            ['name' => 'Enrollment / Registration', 'code' => 'enrollment', 'icon' => 'ProfileOutlined', 'is_core' => false, 'display_order' => 9],
            ['name' => 'Subject / Course Management', 'code' => 'subject', 'icon' => 'BookOutlined', 'is_core' => false, 'display_order' => 10],
            ['name' => 'Attendance', 'code' => 'attendance', 'icon' => 'CheckSquareOutlined', 'is_core' => false, 'display_order' => 11],
            ['name' => 'Examination', 'code' => 'examination', 'icon' => 'FileDoneOutlined', 'is_core' => false, 'display_order' => 12],
            ['name' => 'Fee Management', 'code' => 'fee', 'icon' => 'DollarOutlined', 'is_core' => false, 'display_order' => 13],
            ['name' => 'Accounts', 'code' => 'accounts', 'icon' => 'AccountBookOutlined', 'is_core' => false, 'display_order' => 14],
            ['name' => 'Payroll', 'code' => 'payroll', 'icon' => 'WalletOutlined', 'is_core' => false, 'display_order' => 15],
            ['name' => 'HR / Employees', 'code' => 'hr', 'icon' => 'TeamOutlined', 'is_core' => false, 'display_order' => 16],
            ['name' => 'Timetable', 'code' => 'timetable', 'icon' => 'CalendarOutlined', 'is_core' => false, 'display_order' => 17],
            ['name' => 'Notifications', 'code' => 'notifications', 'icon' => 'BellOutlined', 'is_core' => false, 'display_order' => 18],
            ['name' => 'Reports', 'code' => 'reports', 'icon' => 'BarChartOutlined', 'is_core' => false, 'display_order' => 19],
            ['name' => 'Audit Logs', 'code' => 'audit', 'icon' => 'HistoryOutlined', 'is_core' => true, 'display_order' => 20],
            ['name' => 'Settings', 'code' => 'settings', 'icon' => 'SettingOutlined', 'is_core' => true, 'display_order' => 21],
            ['name' => 'Import / Export', 'code' => 'import_export', 'icon' => 'ImportOutlined', 'is_core' => false, 'display_order' => 22],
            ['name' => 'Dashboard Widgets', 'code' => 'dashboard', 'icon' => 'DashboardOutlined', 'is_core' => true, 'display_order' => 23],
        ];

        foreach ($modules as $module) {
            Module::updateOrCreate(
                ['code' => $module['code']],
                $module
            );
        }
    }
}