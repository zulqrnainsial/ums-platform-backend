<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AssessmentPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'assessment.category.view',
            'assessment.category.create',
            'assessment.category.update',
            'assessment.category.delete',

            'assessment.subject.view',
            'assessment.subject.create',
            'assessment.subject.update',
            'assessment.subject.delete',

            'assessment.topic.view',
            'assessment.topic.create',
            'assessment.topic.update',
            'assessment.topic.delete',

            'assessment.question_bank.view',
            'assessment.question_bank.create',
            'assessment.question_bank.update',
            'assessment.question_bank.delete',

            'assessment.question.view',
            'assessment.question.create',
            'assessment.question.update',
            'assessment.question.delete',
            'assessment.question.approve',
            'assessment.question.import',

            'assessment.test.view',
            'assessment.test.create',
            'assessment.test.update',
            'assessment.test.delete',
            'assessment.test.publish',

            'assessment.section.view',
            'assessment.section.create',
            'assessment.section.update',
            'assessment.section.delete',

            'assessment.test_question.view',
            'assessment.test_question.create',
            'assessment.test_question.update',
            'assessment.test_question.delete',
            'assessment.test_question.bulk_assign',

            'assessment.schedule.view',
            'assessment.schedule.create',
            'assessment.schedule.update',
            'assessment.schedule.delete',

            'assessment.participant.view',
            'assessment.participant.create',
            'assessment.participant.update',
            'assessment.participant.delete',
            'assessment.participant.bulk_assign',
            'assessment.roll_no.generate',

            'assessment.attempt.view',
            'assessment.attempt.monitor',

            'assessment.manual_marking.view',
            'assessment.manual_marking.update',

            'assessment.result.view',
            'assessment.result.generate',
            'assessment.result.approve',
            'assessment.result.publish',
            'assessment.result.withhold',

            'assessment.analytics.view',
        ];

        foreach ($permissions as $permissionName) {
            $this->createPermission($permissionName);
        }

        $this->assignToTenantAdmin($permissions);
    }

    private function createPermission(string $permissionName): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $data = [
            'name' => $permissionName,
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('permissions', 'guard_name')) {
            $data['guard_name'] = 'web';
        }

        if (Schema::hasColumn('permissions', 'module_code')) {
            $data['module_code'] = 'assessment';
        }

        if (Schema::hasColumn('permissions', 'display_name')) {
            $data['display_name'] = ucwords(str_replace(['.', '_'], ' ', $permissionName));
        }

        if (Schema::hasColumn('permissions', 'description')) {
            $data['description'] = ucwords(str_replace(['.', '_'], ' ', $permissionName));
        }

        if (Schema::hasColumn('permissions', 'status')) {
            $data['status'] = 'active';
        }

        if (Schema::hasColumn('permissions', 'created_at')) {
            $data['created_at'] = now();
        }

        DB::table('permissions')->updateOrInsert(
            ['name' => $permissionName],
            $data
        );
    }

    private function assignToTenantAdmin(array $permissions): void
    {
        if (
            !Schema::hasTable('roles') ||
            !Schema::hasTable('permissions') ||
            !Schema::hasTable('role_has_permissions')
        ) {
            return;
        }

        $role = DB::table('roles')
            ->whereIn('name', ['Tenant Admin', 'tenant_admin', 'TenantAdmin'])
            ->first();

        if (!$role) {
            return;
        }

        $permissionRows = DB::table('permissions')
            ->whereIn('name', $permissions)
            ->get();

        foreach ($permissionRows as $permission) {
            DB::table('role_has_permissions')->updateOrInsert(
                [
                    'role_id' => $role->id,
                    'permission_id' => $permission->id,
                ],
                []
            );
        }
    }
}