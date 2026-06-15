<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ApplicantPortalRoleSeeder extends Seeder
{
    public function run(): void
    {
        $guard = 'web';

        $role = Role::firstOrCreate([
            'name' => 'Applicant',
            'guard_name' => $guard,
        ]);

        $permissions = [
            'admission.applicant.view',
            'admission.applicant.update',

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

            'admission.application.view',
            'admission.application.create',
            'admission.application.update',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => $guard,
            ]);
        }

        $role->syncPermissions($permissions);
    }
}