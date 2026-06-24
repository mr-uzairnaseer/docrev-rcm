<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Patient;
use App\Models\PatientAllergyItem;
use App\Models\PatientCareTeamMember;
use App\Models\PatientDocument;
use App\Models\PatientInsurance;
use App\Models\PatientProblem;
use App\Models\PatientVital;
use App\Models\Pharmacy;
use App\Models\Prescription;
use App\Models\Provider;
use Illuminate\Database\Seeder;

class PatientChartSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::where('slug', 'demo-medical')->first();
        $patient = Patient::where('organization_id', $org?->id)->where('mrn', 'MRN-1001')->first();
        $provider = Provider::where('organization_id', $org?->id)->first();
        $pharmacy = Pharmacy::where('organization_id', $org?->id)->first();

        if (! $org || ! $patient || ! $provider || ! $pharmacy) {
            return;
        }

        PatientProblem::firstOrCreate(
            ['patient_id' => $patient->id, 'icd10_code' => 'I10'],
            [
                'organization_id' => $org->id,
                'description' => 'Essential (primary) hypertension',
                'onset_date' => '2025-03-12',
                'status' => 'active',
                'rank' => 1,
            ]
        );

        PatientProblem::firstOrCreate(
            ['patient_id' => $patient->id, 'icd10_code' => 'E119'],
            [
                'organization_id' => $org->id,
                'description' => 'Type 2 diabetes mellitus without complications',
                'onset_date' => '2025-11-20',
                'status' => 'active',
                'rank' => 2,
            ]
        );

        PatientInsurance::firstOrCreate(
            ['patient_id' => $patient->id, 'plan_type' => 'primary'],
            [
                'organization_id' => $org->id,
                'payer_name' => 'UnitedHealthcare (UHC)',
                'member_id' => 'UHC-'.$patient->id.'09876',
                'group_number' => '9008127',
                'coverage_status' => 'active',
                'copay_amount' => 20.00,
                'last_verified_at' => now()->subDays(3),
            ]
        );

        PatientCareTeamMember::firstOrCreate(
            ['patient_id' => $patient->id, 'name' => 'Dr. John Smith'],
            [
                'organization_id' => $org->id,
                'role' => 'Primary Care Physician (PCP)',
                'specialty' => 'Family Medicine',
                'contact' => 'doctor.smith@demo-medical.test',
                'is_primary' => true,
            ]
        );

        PatientCareTeamMember::firstOrCreate(
            ['patient_id' => $patient->id, 'name' => 'Sarah Jenkins, NP'],
            [
                'organization_id' => $org->id,
                'role' => 'Nurse Practitioner',
                'specialty' => 'Family Medicine',
                'contact' => 'np.jenkins@demo-medical.test',
                'is_primary' => false,
            ]
        );

        PatientAllergyItem::firstOrCreate(
            ['patient_id' => $patient->id, 'allergen' => 'Penicillin'],
            [
                'organization_id' => $org->id,
                'reaction' => 'Rash',
                'severity' => 'moderate',
                'status' => 'active',
            ]
        );

        $patient->update(['allergies' => 'Penicillin — Rash']);

        PatientVital::firstOrCreate(
            ['patient_id' => $patient->id, 'recorded_at' => now()->subDays(2)->startOfDay()->addHours(10)],
            [
                'organization_id' => $org->id,
                'bp_systolic' => 128,
                'bp_diastolic' => 82,
                'heart_rate' => 72,
                'respiratory_rate' => 16,
                'temperature_f' => 98.4,
                'weight_lb' => 165,
                'height_in' => 66,
                'spo2' => 98,
            ]
        );

        PatientDocument::firstOrCreate(
            ['patient_id' => $patient->id, 'title' => 'Annual wellness visit summary'],
            [
                'organization_id' => $org->id,
                'document_type' => 'clinical_note',
                'file_name' => 'wellness-summary.pdf',
                'notes' => 'Signed visit summary from last annual exam.',
            ]
        );

        Prescription::firstOrCreate(
            ['patient_id' => $patient->id, 'drug_name' => 'Lisinopril 10mg'],
            [
                'organization_id' => $org->id,
                'provider_id' => $provider->id,
                'pharmacy_id' => $pharmacy->id,
                'ndc' => '68180098003',
                'strength' => '10mg',
                'dosage_form' => 'tablet',
                'quantity' => 30,
                'days_supply' => 30,
                'refills' => 3,
                'sig' => 'Take 1 tablet by mouth once daily',
                'status' => Prescription::STATUS_SENT,
                'sent_at' => now()->subDays(14),
            ]
        );

        Prescription::firstOrCreate(
            ['patient_id' => $patient->id, 'drug_name' => 'Metformin 500mg'],
            [
                'organization_id' => $org->id,
                'provider_id' => $provider->id,
                'pharmacy_id' => $pharmacy->id,
                'ndc' => '00904559161',
                'strength' => '500mg',
                'dosage_form' => 'tablet',
                'quantity' => 60,
                'days_supply' => 30,
                'refills' => 5,
                'sig' => 'Take 1 tablet by mouth twice daily with meals',
                'status' => Prescription::STATUS_SENT,
                'sent_at' => now()->subDays(30),
            ]
        );
    }
}
