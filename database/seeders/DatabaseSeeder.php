<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ModuleSeeder::class,
            SuperAdminSeeder::class,
            RBACPermissionSeeder::class,
            RBACRoleSeeder::class,
            MenuSeeder::class,
            DynamicMetadataSeeder::class,
            AcademicDynamicMetadataSeeder::class,
            SubjectDynamicMetadataSeeder::class,
            LookupDefaultSeeder::class,
            LookupDynamicMetadataSeeder::class,
            StudentDynamicMetadataSeeder::class,
            
        ]);
        $this->call(DynamicFieldStorageRuleSeeder::class);
    }
}