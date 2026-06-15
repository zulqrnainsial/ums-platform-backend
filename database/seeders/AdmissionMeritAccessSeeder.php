<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdmissionMeritAccessSeeder extends Seeder
{
    private string $moduleCode = 'admission';

    public function run(): void
    {
        $this->ensureAdmissionModule();
        $this->seedPermissions();
        $this->seedMenus();

        /*
         | For immediate testing, this grants merit permissions to tenant admin.
         | Super admin will still be able to manage/allocate permissions from UI.
         */
        $this->grantPermissionsToTenantAdminForTesting();

        $this->command?->info('Admission merit permissions and menus seeded successfully.');
    }

    private function ensureAdmissionModule(): void
    {
        if (!Schema::hasTable('modules')) {
            return;
        }

        $payload = $this->filterColumns('modules', [
            'code' => $this->moduleCode,
            'name' => 'Admission Management',
            'title' => 'Admission Management',
            'description' => 'Admission management module',
            'icon' => 'SolutionOutlined',
            'is_active' => 1,
            'status_code' => 'active',
            'display_order' => 40,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $where = $this->filterColumns('modules', [
            'code' => $this->moduleCode,
        ]);

        if (!empty($where)) {
            DB::table('modules')->updateOrInsert($where, $payload);
        }
    }

    private function seedPermissions(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $permissions = [
            [
                'name' => 'admission.merit_builder.view',
                'title' => 'View Merit Formula Builder',
                'group' => 'Merit Management',
            ],
            [
                'name' => 'admission.merit_formula.view',
                'title' => 'View Merit Formulas',
                'group' => 'Merit Management',
            ],
            [
                'name' => 'admission.merit_formula.create',
                'title' => 'Create Merit Formula',
                'group' => 'Merit Management',
            ],
            [
                'name' => 'admission.merit_formula.update',
                'title' => 'Update Merit Formula',
                'group' => 'Merit Management',
            ],
            [
                'name' => 'admission.merit_formula.delete',
                'title' => 'Delete Merit Formula',
                'group' => 'Merit Management',
            ],

            [
                'name' => 'admission.merit_formula_component.view',
                'title' => 'View Merit Formula Components',
                'group' => 'Merit Management',
            ],
            [
                'name' => 'admission.merit_formula_component.create',
                'title' => 'Create Merit Formula Component',
                'group' => 'Merit Management',
            ],
            [
                'name' => 'admission.merit_formula_component.update',
                'title' => 'Update Merit Formula Component',
                'group' => 'Merit Management',
            ],
            [
                'name' => 'admission.merit_formula_component.delete',
                'title' => 'Delete Merit Formula Component',
                'group' => 'Merit Management',
            ],

            [
                'name' => 'admission.merit_formula_applicability.view',
                'title' => 'View Merit Formula Applicability',
                'group' => 'Merit Management',
            ],
            [
                'name' => 'admission.merit_formula_applicability.create',
                'title' => 'Create Merit Formula Applicability',
                'group' => 'Merit Management',
            ],
            [
                'name' => 'admission.merit_formula_applicability.update',
                'title' => 'Update Merit Formula Applicability',
                'group' => 'Merit Management',
            ],
            [
                'name' => 'admission.merit_formula_applicability.delete',
                'title' => 'Delete Merit Formula Applicability',
                'group' => 'Merit Management',
            ],

            [
                'name' => 'admission.merit_calculation.view',
                'title' => 'View Merit Calculation',
                'group' => 'Merit Management',
            ],
            [
                'name' => 'admission.merit_calculation.calculate',
                'title' => 'Calculate Applicant Merit',
                'group' => 'Merit Management',
            ],
            [
                'name' => 'admission.merit_calculation.bulk_calculate',
                'title' => 'Bulk Calculate Applicant Merit',
                'group' => 'Merit Management',
            ],

            [
                'name' => 'admission.merit_score.view',
                'title' => 'View Applicant Merit Scores',
                'group' => 'Merit Management',
            ],
            [
                'name' => 'admission.merit_list.view',
                'title' => 'View Merit Lists',
                'group' => 'Merit Management',
            ],
            [
                'name' => 'admission.merit_list.generate',
                'title' => 'Generate Merit Lists',
                'group' => 'Merit Management',
            ],
            [
                'name' => 'admission.merit_list.publish',
                'title' => 'Publish Merit Lists',
                'group' => 'Merit Management',
            ],
            [
                'name' => 'admission.merit_list.cancel',
                'title' => 'Cancel Merit Lists',
                'group' => 'Merit Management',
            ],
            [
                'name' => 'admission.merit_offer.generate',
                'title' => 'Generate Merit Offers',
                'group' => 'Merit Management',
            ],
            [
                'name' => 'admission.merit_offer.accept',
                'title' => 'Accept Merit Offer',
                'group' => 'Merit Management',
            ],
            [
                'name' => 'admission.merit_offer.reject',
                'title' => 'Reject Merit Offer',
                'group' => 'Merit Management',
            ],
            [
                'name' => 'admission.merit_offer.expire',
                'title' => 'Expire Merit Offer',
                'group' => 'Merit Management',
            ],
            [
                'name' => 'admission.merit_offer.movement.view',
                'title' => 'View Merit Offer Movements',
                'group' => 'Merit Management',
            ],
        ];

        foreach ($permissions as $permission) {
            $name = $permission['name'];

            $where = [];

            if (Schema::hasColumn('permissions', 'name')) {
                $where['name'] = $name;
            } elseif (Schema::hasColumn('permissions', 'permission_name')) {
                $where['permission_name'] = $name;
            } elseif (Schema::hasColumn('permissions', 'code')) {
                $where['code'] = $name;
            }

            if (empty($where)) {
                continue;
            }

            $payload = [
                'name' => $name,
                'permission_name' => $name,
                'code' => $name,
                'title' => $permission['title'],
                'display_name' => $permission['title'],
                'label' => $permission['title'],
                'description' => $permission['title'],
                'module_code' => $this->moduleCode,
                'group_name' => $permission['group'],
                'permission_group' => $permission['group'],
                'guard_name' => 'web',
                'is_active' => 1,
                'status_code' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            DB::table('permissions')->updateOrInsert(
                $this->filterColumns('permissions', $where),
                $this->filterColumns('permissions', $payload)
            );
        }
    }

    private function seedMenus(): void
    {
        if (!Schema::hasTable('menus')) {
            return;
        }

        $parentId = $this->ensureAdmissionParentMenu();

        if (!$parentId) {
            return;
        }

        $items = [
            [
                'title' => 'Merit Formula Builder',
                'code' => 'admission_merit_builder',
                'route' => '/admission/merit-builder',
                'icon' => 'CalculatorOutlined',
                'permission_name' => 'admission.merit_builder.view',
                'display_order' => 118,
            ],
            [
                'title' => 'Merit Calculation',
                'code' => 'admission_merit_calculation',
                'route' => '/admission/merit-calculation',
                'icon' => 'PercentageOutlined',
                'permission_name' => 'admission.merit_calculation.view',
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
        ];

        foreach ($items as $item) {
            $where = $this->filterColumns('menus', [
                'code' => $item['code'],
            ]);

            if (empty($where)) {
                continue;
            }

            $payload = [
                'parent_id' => $parentId,
                'title' => $item['title'],
                'name' => $item['title'],
                'label' => $item['title'],
                'code' => $item['code'],
                'route' => $item['route'],
                'path' => $item['route'],
                'icon' => $item['icon'],
                'permission_name' => $item['permission_name'],
                'permission' => $item['permission_name'],
                'module_code' => $this->moduleCode,
                'display_order' => $item['display_order'],
                'sort_order' => $item['display_order'],
                'is_active' => 1,
                'status_code' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            DB::table('menus')->updateOrInsert(
                $where,
                $this->filterColumns('menus', $payload)
            );
        }
    }

    private function ensureAdmissionParentMenu(): ?int
    {
        $existing = DB::table('menus')
            ->where(function ($q) {
                $q->where('code', 'admission_management')
                    ->orWhere('route', '/admission')
                    ->orWhere('title', 'Admission Management');
            })
            ->first();

        if ($existing) {
            DB::table('menus')
                ->where('id', $existing->id)
                ->update($this->filterColumns('menus', [
                    'title' => 'Admission Management',
                    'name' => 'Admission Management',
                    'label' => 'Admission Management',
                    'code' => 'admission_management',
                    'module_code' => $this->moduleCode,
                    'is_active' => 1,
                    'status_code' => 'active',
                    'updated_at' => now(),
                ]));

            return (int) $existing->id;
        }

        $payload = [
            'title' => 'Admission Management',
            'name' => 'Admission Management',
            'label' => 'Admission Management',
            'code' => 'admission_management',
            'route' => null,
            'path' => null,
            'icon' => 'SolutionOutlined',
            'permission_name' => null,
            'permission' => null,
            'module_code' => $this->moduleCode,
            'parent_id' => null,
            'display_order' => 40,
            'sort_order' => 40,
            'is_active' => 1,
            'status_code' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return DB::table('menus')->insertGetId(
            $this->filterColumns('menus', $payload)
        );
    }

    private function grantPermissionsToTenantAdminForTesting(): void
{
    if (!Schema::hasTable('roles') || !Schema::hasTable('permissions')) {
        return;
    }

    /*
     | Do not assume roles table has code/slug.
     | Your current roles table error proves it does not have code.
     */
    $roleQuery = DB::table('roles');

    $roleQuery->where(function ($q) {
        if (Schema::hasColumn('roles', 'name')) {
            $q->where('name', 'tenant_admin')
                ->orWhere('name', 'Tenant Admin');
        }

        if (Schema::hasColumn('roles', 'guard_name')) {
            // No filtering needed here. Kept only to avoid schema assumptions.
        }
    });

    if (Schema::hasColumn('roles', 'code')) {
        $roleQuery->orWhere('code', 'tenant_admin');
    }

    if (Schema::hasColumn('roles', 'slug')) {
        $roleQuery->orWhere('slug', 'tenant_admin');
    }

    $role = $roleQuery->first();

    if (!$role) {
        $this->command?->warn('Tenant admin role not found. Merit permissions were seeded, but not auto-assigned.');
        return;
    }

    $permissionNames = [
        'admission.merit_builder.view',
        'admission.merit_formula.view',
        'admission.merit_formula.create',
        'admission.merit_formula.update',
        'admission.merit_formula.delete',

        'admission.merit_formula_component.view',
        'admission.merit_formula_component.create',
        'admission.merit_formula_component.update',
        'admission.merit_formula_component.delete',

        'admission.merit_formula_applicability.view',
        'admission.merit_formula_applicability.create',
        'admission.merit_formula_applicability.update',
        'admission.merit_formula_applicability.delete',

        'admission.merit_calculation.view',
        'admission.merit_calculation.calculate',
        'admission.merit_calculation.bulk_calculate',

        'admission.merit_score.view',
    ];

    $permissionQuery = DB::table('permissions');

    $permissionQuery->where(function ($q) use ($permissionNames) {
        if (Schema::hasColumn('permissions', 'name')) {
            $q->whereIn('name', $permissionNames);
        }

        if (Schema::hasColumn('permissions', 'code')) {
            $q->orWhereIn('code', $permissionNames);
        }

        if (Schema::hasColumn('permissions', 'permission_name')) {
            $q->orWhereIn('permission_name', $permissionNames);
        }
    });

    $permissions = $permissionQuery->get();

    if ($permissions->isEmpty()) {
        $this->command?->warn('No merit permissions found to assign.');
        return;
    }

    /*
     | Spatie standard table.
     */
    if (Schema::hasTable('role_has_permissions')) {
        foreach ($permissions as $permission) {
            $where = [
                'role_id' => $role->id,
                'permission_id' => $permission->id,
            ];

            if (Schema::hasColumn('role_has_permissions', 'role_id')
                && Schema::hasColumn('role_has_permissions', 'permission_id')) {
                DB::table('role_has_permissions')->updateOrInsert($where, $where);
            }
        }
    }

    /*
     | Custom pivot table possibility.
     */
    if (Schema::hasTable('role_permissions')) {
        foreach ($permissions as $permission) {
            $where = $this->filterColumns('role_permissions', [
                'role_id' => $role->id,
                'permission_id' => $permission->id,
            ]);

            $payload = $this->filterColumns('role_permissions', [
                'role_id' => $role->id,
                'permission_id' => $permission->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if (!empty($where)) {
                DB::table('role_permissions')->updateOrInsert($where, $payload);
            }
        }
    }
}

    private function filterColumns(string $table, array $payload): array
    {
        return collect($payload)
            ->filter(fn ($value, $column) => Schema::hasColumn($table, $column))
            ->toArray();
    }
}