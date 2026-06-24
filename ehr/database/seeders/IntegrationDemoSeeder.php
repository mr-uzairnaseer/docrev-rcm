<?php

namespace Database\Seeders;

use App\Models\HieConnection;
use App\Models\LabVendor;
use App\Models\Organization;
use App\Models\Pharmacy;
use App\Models\Provider;
use App\Models\SurescriptsEnrollment;
use Illuminate\Database\Seeder;

class IntegrationDemoSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::where('slug', 'demo-medical')->first();
        $provider = Provider::where('organization_id', $org->id)->first();

        if (! $org || ! $provider) {
            return;
        }

        Pharmacy::firstOrCreate(
            ['organization_id' => $org->id, 'ncpdp_id' => '1234567'],
            [
                'name' => 'Demo Community Pharmacy',
                'phone' => '555-0200',
                'address_line1' => '100 Main St',
                'city' => 'Springfield',
                'state' => 'IL',
                'postal_code' => '62701',
            ]
        );

        SurescriptsEnrollment::firstOrCreate(
            ['organization_id' => $org->id, 'provider_id' => $provider->id],
            [
                'spi' => 'SPI-DEMO-000001',
                'dea_number' => 'AD1234567',
                'status' => SurescriptsEnrollment::STATUS_ACTIVE,
                'enrolled_at' => now(),
                'notes' => 'Demo Surescripts enrollment.',
            ]
        );

        LabVendor::firstOrCreate(
            ['organization_id' => $org->id, 'vendor_code' => 'QUEST'],
            [
                'name' => 'Quest Diagnostics (Demo)',
                'interface_type' => 'hl7_v2',
                'sending_application' => 'DOCREV_EHR',
                'sending_facility' => 'DEMO_MEDICAL',
                'receiving_application' => 'QUEST',
                'receiving_facility' => 'QUEST',
            ]
        );

        HieConnection::firstOrCreate(
            ['organization_id' => $org->id, 'name' => 'CommonWell (Demo)'],
            [
                'network_type' => 'fhir_r4',
                'fhir_base_url' => 'https://fhir.commonwellalliance.org/r4',
                'status' => HieConnection::STATUS_ACTIVE,
                'agreement_signed_at' => now(),
                'agreement_notes' => 'Demo HIE participation agreement.',
            ]
        );
    }
}
