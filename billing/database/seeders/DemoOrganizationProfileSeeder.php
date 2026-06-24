<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\OrganizationProvider;
use Illuminate\Database\Seeder;

class DemoOrganizationProfileSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::query()->first();
        if (! $org) {
            return;
        }

        $org->update([
            'npi' => $org->npi ?: '1245319599',
            'tax_id' => $org->tax_id ?: '12-3456789',
            'phone' => $org->phone ?: '555-010-0200',
            'address' => $org->address ?: [
                'line1' => '100 Main Street',
                'city' => 'Philadelphia',
                'state' => 'PA',
                'zip' => '19103',
            ],
        ]);

        if (OrganizationProvider::forOrganization($org->id)->count() === 0) {
            OrganizationProvider::create([
                'organization_id' => $org->id,
                'first_name' => 'Sarah',
                'last_name' => 'Chen',
                'npi' => '1245319599',
                'credentials' => 'MD',
                'taxonomy_code' => '207Q00000X',
            ]);
        }
    }
}
