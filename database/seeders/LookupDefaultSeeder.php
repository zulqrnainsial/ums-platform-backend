<?php

namespace Database\Seeders;

use App\Modules\Lookup\Models\LookupCategory;
use App\Modules\Lookup\Models\LookupValue;
use Illuminate\Database\Seeder;

class LookupDefaultSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['code' => 'COUNTRY', 'name' => 'Country', 'order' => 1],
            ['code' => 'PROVINCE', 'name' => 'Province / State', 'order' => 2],
            ['code' => 'CITY', 'name' => 'City', 'order' => 3],
            ['code' => 'BLOOD_GROUP', 'name' => 'Blood Group', 'order' => 4],
            ['code' => 'RELIGION', 'name' => 'Religion', 'order' => 5],
            ['code' => 'NATIONALITY', 'name' => 'Nationality', 'order' => 6],
            ['code' => 'DOCUMENT_TYPE', 'name' => 'Document Type', 'order' => 7],
            ['code' => 'QUALIFICATION_LEVEL', 'name' => 'Qualification Level', 'order' => 8],
            ['code' => 'BOARD', 'name' => 'Board / University', 'order' => 9],
            ['code' => 'EXTERNAL_INSTITUTION', 'name' => 'External Institution', 'order' => 10],
            ['code' => 'INSTITUTION_TYPE', 'name' => 'Institution Type', 'order' => 11],
            ['code' => 'RELATIONSHIP_TYPE', 'name' => 'Relationship Type', 'order' => 12],
        ];

        foreach ($categories as $item) {
            LookupCategory::updateOrCreate(
                [
                    'tenant_id' => null,
                    'code' => $item['code'],
                ],
                [
                    'name' => $item['name'],
                    'description' => $item['name'] . ' lookup category.',
                    'is_system' => true,
                    'is_tenant_editable' => true,
                    'display_order' => $item['order'],
                    'status' => 'active',
                ]
            );
        }

        $country = $this->category('COUNTRY');
        $province = $this->category('PROVINCE');
        $city = $this->category('CITY');

        $pakistan = $this->value($country->id, null, 'PK', 'Pakistan', 'PK', 1);

        $punjab = $this->value($province->id, $pakistan->id, 'PUNJAB', 'Punjab', null, 1);
        $sindh = $this->value($province->id, $pakistan->id, 'SINDH', 'Sindh', null, 2);
        $kpk = $this->value($province->id, $pakistan->id, 'KPK', 'Khyber Pakhtunkhwa', 'KPK', 3);
        $balochistan = $this->value($province->id, $pakistan->id, 'BALOCHISTAN', 'Balochistan', null, 4);

        $this->value($city->id, $punjab->id, 'MULTAN', 'Multan', null, 1);
        $this->value($city->id, $punjab->id, 'LAHORE', 'Lahore', null, 2);
        $this->value($city->id, $punjab->id, 'FAISALABAD', 'Faisalabad', null, 3);
        $this->value($city->id, $sindh->id, 'KARACHI', 'Karachi', null, 1);
        $this->value($city->id, $kpk->id, 'PESHAWAR', 'Peshawar', null, 1);
        $this->value($city->id, $balochistan->id, 'QUETTA', 'Quetta', null, 1);

        foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $index => $bloodGroup) {
            $this->value($this->category('BLOOD_GROUP')->id, null, str_replace(['+', '-'], ['_POS', '_NEG'], $bloodGroup), $bloodGroup, $bloodGroup, $index + 1);
        }

        $this->value($this->category('RELIGION')->id, null, 'ISLAM', 'Islam', null, 1);
        $this->value($this->category('RELIGION')->id, null, 'CHRISTIANITY', 'Christianity', null, 2);
        $this->value($this->category('RELIGION')->id, null, 'HINDUISM', 'Hinduism', null, 3);
        $this->value($this->category('RELIGION')->id, null, 'OTHER', 'Other', null, 99);

        $this->value($this->category('NATIONALITY')->id, null, 'PAKISTANI', 'Pakistani', null, 1);
        $this->value($this->category('NATIONALITY')->id, null, 'OTHER', 'Other', null, 99);

        $documentTypes = [
            'CNIC',
            'B-FORM',
            'PHOTO',
            'DOMICILE',
            'MATRIC_CERTIFICATE',
            'INTERMEDIATE_CERTIFICATE',
            'CHARACTER_CERTIFICATE',
            'MIGRATION_CERTIFICATE',
        ];

        foreach ($documentTypes as $index => $documentType) {
            $this->value(
                $this->category('DOCUMENT_TYPE')->id,
                null,
                $documentType,
                ucwords(strtolower(str_replace('_', ' ', $documentType))),
                null,
                $index + 1
            );
        }

        $qualificationLevels = [
            'MATRIC' => 'Matric',
            'INTERMEDIATE' => 'Intermediate',
            'BACHELOR' => 'Bachelor',
            'MASTER' => 'Master',
            'MS_MPHIL' => 'MS / MPhil',
            'PHD' => 'PhD',
            'CERTIFICATE' => 'Certificate',
            'DIPLOMA' => 'Diploma',
        ];

        $order = 1;
        foreach ($qualificationLevels as $code => $name) {
            $this->value($this->category('QUALIFICATION_LEVEL')->id, null, $code, $name, null, $order++);
        }

        $boards = [
            'BISE_MULTAN' => 'BISE Multan',
            'BISE_LAHORE' => 'BISE Lahore',
            'BISE_DG_KHAN' => 'BISE Dera Ghazi Khan',
            'BISE_BWP' => 'BISE Bahawalpur',
            'PBTE' => 'Punjab Board of Technical Education',
            'HEC' => 'Higher Education Commission',
        ];

        $order = 1;
        foreach ($boards as $code => $name) {
            $this->value($this->category('BOARD')->id, null, $code, $name, null, $order++);
        }

        foreach ([
            'SCHOOL' => 'School',
            'COLLEGE' => 'College',
            'UNIVERSITY' => 'University',
            'MADRESSAH' => 'Madressah',
            'INSTITUTE' => 'Institute',
            'OTHER' => 'Other',
        ] as $code => $name) {
            $this->value($this->category('INSTITUTION_TYPE')->id, null, $code, $name, null, 1);
        }

        foreach ([
            'FATHER' => 'Father',
            'MOTHER' => 'Mother',
            'BROTHER' => 'Brother',
            'SISTER' => 'Sister',
            'UNCLE' => 'Uncle',
            'AUNT' => 'Aunt',
            'GRANDFATHER' => 'Grandfather',
            'GRANDMOTHER' => 'Grandmother',
            'GUARDIAN' => 'Guardian',
            'OTHER' => 'Other',
        ] as $code => $name) {
            $this->value($this->category('RELATIONSHIP_TYPE')->id, null, $code, $name, null, 1);
        }
    }

    private function category(string $code): LookupCategory
    {
        return LookupCategory::whereNull('tenant_id')
            ->where('code', $code)
            ->firstOrFail();
    }

    private function value(
        int $categoryId,
        ?int $parentId,
        string $code,
        string $name,
        ?string $shortName,
        int $order
    ): LookupValue {
        return LookupValue::updateOrCreate(
            [
                'tenant_id' => null,
                'lookup_category_id' => $categoryId,
                'code' => $code,
            ],
            [
                'parent_id' => $parentId,
                'name' => $name,
                'short_name' => $shortName,
                'display_order' => $order,
                'status' => 'active',
            ]
        );
    }
}