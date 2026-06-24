<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\PatientAccount;
use App\Models\PatientStatement;
use App\Models\PortalAppointment;
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
            'email' => 'portal@demo-medical.test',
            'timezone' => 'America/New_York',
        ]);

        User::create([
            'organization_id' => $organization->id,
            'name' => 'Portal Admin',
            'email' => 'portal@demo-medical.test',
            'password' => Hash::make('password'),
            'role' => 'org_admin',
        ]);

        $patient = PatientAccount::create([
            'organization_id' => $organization->id,
            'uuid' => 'e47ac10b-58cc-4372-a567-0e02b2c3d480',
            'ehr_patient_uuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
            'billing_patient_uuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane.doe@patient.test',
            'phone' => '555-0100',
            'date_of_birth' => '1990-05-15',
            'password' => Hash::make('password'),
        ]);

        PortalAppointment::create([
            'patient_account_id' => $patient->id,
            'provider_name' => 'Dr. John Smith',
            'location_name' => 'Main Clinic',
            'appointment_at' => now()->addDays(3),
            'appointment_type' => 'office_visit',
            'status' => 'scheduled',
        ]);

        PatientStatement::create([
            'patient_account_id' => $patient->id,
            'statement_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'total_amount' => 150.00,
            'paid_amount' => 0,
            'balance_due' => 150.00,
            'status' => 'open',
            'line_items' => [
                ['description' => 'Office visit co-pay', 'amount' => 150.00],
            ],
        ]);
    }
}
