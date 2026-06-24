<?php

namespace Database\Seeders;

use App\Models\CmsReferencePayer;
use App\Models\CmsState;
use App\Models\Organization;
use App\Models\OrganizationProvider;
use App\Models\Patient;
use App\Models\Payer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CmsReferenceSeeder::class);

        $organization = Organization::create([
            'name' => 'Demo Medical Practice',
            'slug' => 'demo-medical',
            'npi' => '1245319599',
            'tax_id' => '12-3456789',
            'email' => 'billing@demo-medical.test',
            'phone' => '555-010-0200',
            'address' => [
                'line1' => '100 Main Street',
                'city' => 'Philadelphia',
                'state' => 'PA',
                'zip' => '19103',
            ],
            'timezone' => 'America/New_York',
        ]);

        OrganizationProvider::create([
            'organization_id' => $organization->id,
            'first_name' => 'Sarah',
            'last_name' => 'Chen',
            'npi' => '1245319599',
            'credentials' => 'MD',
            'taxonomy_code' => '207Q00000X',
        ]);

        User::create([
            'organization_id' => $organization->id,
            'name' => 'Clearinghouse Admin',
            'email' => 'billing@demo-medical.test',
            'password' => Hash::make('password'),
            'role' => 'biller',
        ]);

        $paState = CmsState::query()->where('code', 'PA')->first();
        $bcbsRef = CmsReferencePayer::query()->where('code', 'BCBS-PA')->first();

        Payer::create([
            'organization_id' => $organization->id,
            'cms_reference_payer_id' => $bcbsRef?->id,
            'cms_state_id' => $paState?->id,
            'name' => $bcbsRef?->name ?? 'Highmark Blue Cross Blue Shield (PA)',
            'payer_type' => 'commercial',
            'electronic_payer_id' => $bcbsRef?->electronic_payer_id ?? '54771',
        ]);

        Patient::create([
            'organization_id' => $organization->id,
            'uuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'date_of_birth' => '1990-05-15',
            'gender' => 'female',
            'mrn' => 'MRN-1001',
            'insurance_member_id' => 'BCBS-JDOE-001',
            'insurance_group_number' => 'GRP-10001',
        ]);

        $this->call(DemoRcmSeeder::class);
    }
}
