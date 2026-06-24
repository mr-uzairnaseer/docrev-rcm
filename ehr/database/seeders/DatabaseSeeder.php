<?php

namespace Database\Seeders;

use App\Models\HieConnection;
use App\Models\LabVendor;
use App\Models\Location;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\Pharmacy;
use App\Models\Provider;
use App\Models\SurescriptsEnrollment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::create([
            'name' => 'Demo Medical Practice',
            'slug' => 'demo-medical',
            'npi' => '1234567890',
            'email' => 'admin@demo-medical.test',
            'timezone' => 'America/New_York',
        ]);

        User::create([
            'organization_id' => $organization->id,
            'name' => 'System Admin',
            'email' => 'admin@demo-medical.test',
            'password' => Hash::make('password'),
            'role' => 'org_admin',
        ]);

        $provider = Provider::create([
            'organization_id' => $organization->id,
            'npi' => '1987654321',
            'first_name' => 'John',
            'last_name' => 'Smith',
            'credentials' => 'MD',
            'specialty' => 'Family Medicine',
        ]);

        Location::create([
            'organization_id' => $organization->id,
            'name' => 'Main Clinic',
            'code' => 'MAIN',
            'place_of_service_code' => '11',
        ]);

        Patient::create([
            'organization_id' => $organization->id,
            'uuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'date_of_birth' => '1990-05-15',
            'gender' => 'female',
            'mrn' => 'MRN-1001',
            'phone' => '555-0100',
        ]);

        Pharmacy::create([
            'organization_id' => $organization->id,
            'name' => 'Demo Community Pharmacy',
            'ncpdp_id' => '1234567',
            'phone' => '555-0200',
            'address_line1' => '100 Main St',
            'city' => 'Springfield',
            'state' => 'IL',
            'postal_code' => '62701',
        ]);

        SurescriptsEnrollment::create([
            'organization_id' => $organization->id,
            'provider_id' => $provider->id,
            'spi' => 'SPI-DEMO-000001',
            'dea_number' => 'AD1234567',
            'status' => SurescriptsEnrollment::STATUS_ACTIVE,
            'enrolled_at' => now(),
            'notes' => 'Demo Surescripts enrollment for Dr. Smith.',
        ]);

        LabVendor::create([
            'organization_id' => $organization->id,
            'name' => 'Quest Diagnostics (Demo)',
            'interface_type' => 'hl7_v2',
            'vendor_code' => 'QUEST',
            'sending_application' => 'DOCREV_EHR',
            'sending_facility' => 'DEMO_MEDICAL',
            'receiving_application' => 'QUEST',
            'receiving_facility' => 'QUEST',
        ]);

        HieConnection::create([
            'organization_id' => $organization->id,
            'name' => 'CommonWell (Demo)',
            'network_type' => 'fhir_r4',
            'fhir_base_url' => 'https://fhir.commonwellalliance.org/r4',
            'status' => HieConnection::STATUS_ACTIVE,
            'agreement_signed_at' => now(),
            'agreement_notes' => 'Demo HIE participation agreement.',
        ]);
    }
}
