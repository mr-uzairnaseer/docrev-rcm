<?php

namespace Database\Seeders;

use App\Models\Charge;
use App\Models\Claim;
use App\Models\ClaimDenial;
use App\Models\ClaimLine;
use App\Models\ClaimPayment;
use App\Models\ClaimSubmission;
use App\Models\EraRemittance;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\PatientPayment;
use App\Models\Payer;
use App\Services\Billing\Edi837Builder;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoRcmSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::query()->where('slug', 'demo-medical')->first();
        $patient = Patient::query()->where('organization_id', $org?->id)->first();
        $payer = Payer::query()->where('organization_id', $org?->id)->first();

        if (! $org || ! $patient || ! $payer) {
            return;
        }

        if (Claim::query()->where('organization_id', $org->id)->exists()) {
            return;
        }

        $npi = $org->npi ?: '1245319599';
        $ediBuilder = app(Edi837Builder::class);

        // Ready charge for building new claims (RCM-304 workflow)
        Charge::create([
            'organization_id' => $org->id,
            'patient_id' => $patient->id,
            'service_date' => now()->subDays(2)->toDateString(),
            'cpt_code' => '99213',
            'units' => 1,
            'charge_amount' => 150.00,
            'diagnosis_pointers' => [1],
            'icd10_codes' => ['Z00.00'],
            'status' => Charge::STATUS_READY,
        ]);

        $draftClaim = $this->seedClaim($org, $patient, $payer, $npi, [
            'claim_number' => 'CLM-DRAFT01',
            'status' => Claim::STATUS_DRAFT,
            'service_days_ago' => 3,
            'amount' => 175.00,
            'cpt' => '99214',
            'patient_responsibility' => 0,
            'paid_amount' => 0,
        ]);

        $submittedRecent = $this->seedClaim($org, $patient, $payer, $npi, [
            'claim_number' => 'CLM-SUB001',
            'status' => Claim::STATUS_SUBMITTED,
            'service_days_ago' => 8,
            'submitted_days_ago' => 5,
            'amount' => 150.00,
            'cpt' => '99213',
            'patient_responsibility' => 25.00,
            'paid_amount' => 0,
        ]);
        $this->seedSubmission($submittedRecent, $ediBuilder);

        $submittedMid = $this->seedClaim($org, $patient, $payer, $npi, [
            'claim_number' => 'CLM-SUB002',
            'status' => Claim::STATUS_SUBMITTED,
            'service_days_ago' => 50,
            'submitted_days_ago' => 45,
            'amount' => 200.00,
            'cpt' => '99214',
            'patient_responsibility' => 40.00,
            'paid_amount' => 0,
        ]);
        $this->seedSubmission($submittedMid, $ediBuilder);

        $partialClaim = $this->seedClaim($org, $patient, $payer, $npi, [
            'claim_number' => 'CLM-PART01',
            'uuid' => 'a47ac10b-58cc-4372-a567-0e02b2c3d481',
            'status' => Claim::STATUS_PARTIAL,
            'service_days_ago' => 80,
            'submitted_days_ago' => 75,
            'amount' => 300.00,
            'cpt' => '99215',
            'patient_responsibility' => 50.00,
            'paid_amount' => 150.00,
        ]);
        $this->seedSubmission($partialClaim, $ediBuilder);
        $era = EraRemittance::create([
            'organization_id' => $org->id,
            'trace_number' => 'ERA-'.now()->format('Ymd').'-001',
            'edi_835_content' => 'ISA*00*          *00*          *ZZ*PAYER          *ZZ*PROVIDER       *'.now()->format('ymd').'*1200*^*00501*000000001*0*P*:~',
            'total_payment_amount' => 150.00,
            'claim_count' => 1,
            'matched_count' => 1,
            'status' => EraRemittance::STATUS_POSTED,
            'posted_at' => now()->subDays(10),
        ]);
        ClaimPayment::create([
            'era_remittance_id' => $era->id,
            'claim_id' => $partialClaim->id,
            'claim_number' => $partialClaim->claim_number,
            'era_status' => '1',
            'total_charge' => 300.00,
            'paid_amount' => 150.00,
            'patient_responsibility' => 50.00,
            'payment_status' => ClaimPayment::STATUS_PARTIAL,
            'portal_synced' => true,
        ]);

        $deniedClaim = $this->seedClaim($org, $patient, $payer, $npi, [
            'claim_number' => 'CLM-DEN001',
            'status' => Claim::STATUS_DENIED,
            'service_days_ago' => 105,
            'submitted_days_ago' => 100,
            'amount' => 150.00,
            'cpt' => '99213',
            'patient_responsibility' => 0,
            'paid_amount' => 0,
        ]);
        $this->seedSubmission($deniedClaim, $ediBuilder);
        ClaimDenial::create([
            'organization_id' => $org->id,
            'claim_id' => $deniedClaim->id,
            'reason_code' => 'CO-97',
            'reason_description' => 'The benefit for this service is included in the payment/allowance for another service/procedure that has already been adjudicated.',
            'denied_amount' => 150.00,
            'status' => ClaimDenial::STATUS_OPEN,
        ]);

        $appealedClaim = $this->seedClaim($org, $patient, $payer, $npi, [
            'claim_number' => 'CLM-APP001',
            'status' => Claim::STATUS_SUBMITTED,
            'service_days_ago' => 30,
            'submitted_days_ago' => 2,
            'amount' => 125.00,
            'cpt' => '99212',
            'patient_responsibility' => 0,
            'paid_amount' => 0,
        ]);
        $this->seedSubmission($appealedClaim, $ediBuilder);
        ClaimDenial::create([
            'organization_id' => $org->id,
            'claim_id' => $appealedClaim->id,
            'reason_code' => 'CO-50',
            'reason_description' => 'These are non-covered services because this is not deemed a medical necessity by the payer.',
            'denied_amount' => 125.00,
            'status' => ClaimDenial::STATUS_APPEALED,
            'appeal_notes' => 'Appealed using Appeal Letter Scribe (medical_necessity): prior appeal on file.',
            'appealed_at' => now()->subDay(),
        ]);

        PatientPayment::create([
            'organization_id' => $org->id,
            'patient_id' => $patient->id,
            'claim_id' => $partialClaim->id,
            'external_claim_uuid' => $partialClaim->uuid,
            'amount' => 25.00,
            'payment_method' => 'portal_card',
            'reference_number' => 'PP-'.now()->format('Ymd').'-1001',
            'status' => PatientPayment::STATUS_POSTED,
            'portal_synced' => true,
            'paid_at' => now()->subHours(2),
        ]);

        PatientPayment::create([
            'organization_id' => $org->id,
            'patient_id' => $patient->id,
            'claim_id' => null,
            'amount' => 15.00,
            'payment_method' => 'portal_card',
            'reference_number' => 'PP-'.now()->format('Ymd').'-1002',
            'status' => PatientPayment::STATUS_POSTED,
            'portal_synced' => true,
            'paid_at' => now()->subMinutes(30),
        ]);
    }

    private function seedClaim(Organization $org, Patient $patient, Payer $payer, string $npi, array $opts): Claim
    {
        $serviceDate = now()->subDays($opts['service_days_ago'])->toDateString();
        $submittedAt = isset($opts['submitted_days_ago'])
            ? now()->subDays($opts['submitted_days_ago'])
            : null;

        $charge = Charge::create([
            'organization_id' => $org->id,
            'patient_id' => $patient->id,
            'service_date' => $serviceDate,
            'cpt_code' => $opts['cpt'],
            'units' => 1,
            'charge_amount' => $opts['amount'],
            'diagnosis_pointers' => [1],
            'icd10_codes' => ['Z00.00'],
            'status' => Charge::STATUS_BILLED,
        ]);

        $claim = Claim::create([
            'organization_id' => $org->id,
            'uuid' => $opts['uuid'] ?? (string) \Illuminate\Support\Str::uuid(),
            'patient_id' => $patient->id,
            'payer_id' => $payer->id,
            'claim_number' => $opts['claim_number'],
            'service_date_from' => $serviceDate,
            'service_date_to' => $serviceDate,
            'total_charge_amount' => $opts['amount'],
            'paid_amount' => $opts['paid_amount'],
            'patient_responsibility' => $opts['patient_responsibility'],
            'status' => $opts['status'],
            'submitted_at' => $submittedAt,
            'icd10_codes' => ['Z00.00'],
            'rendering_provider_npi' => $npi,
            'billing_provider_npi' => $npi,
            'place_of_service' => '11',
        ]);

        ClaimLine::create([
            'claim_id' => $claim->id,
            'charge_id' => $charge->id,
            'line_number' => 1,
            'cpt_code' => $opts['cpt'],
            'units' => 1,
            'charge_amount' => $opts['amount'],
            'paid_amount' => $opts['paid_amount'],
            'patient_responsibility' => $opts['patient_responsibility'],
            'diagnosis_pointers' => [1],
        ]);

        if ($opts['status'] !== Claim::STATUS_DRAFT) {
            $claim->update([
                'edi_837_content' => app(Edi837Builder::class)->build($claim->fresh()->load(['patient', 'payer', 'claimLines', 'organization'])),
                'edi_generated_at' => $submittedAt ?? now(),
            ]);
        }

        return $claim->fresh();
    }

    private function seedSubmission(Claim $claim, Edi837Builder $ediBuilder): void
    {
        if (empty($claim->edi_837_content)) {
            $claim->update([
                'edi_837_content' => $ediBuilder->build($claim->load(['patient', 'payer', 'claimLines', 'organization'])),
                'edi_generated_at' => $claim->submitted_at ?? now(),
            ]);
        }

        ClaimSubmission::create([
            'claim_id' => $claim->id,
            'clearinghouse' => config('clearinghouse.driver', 'stub'),
            'status' => ClaimSubmission::STATUS_ACCEPTED,
            'external_reference' => 'STUB-'.strtoupper(substr($claim->claim_number, -6)),
            'edi_837_content' => $claim->edi_837_content,
            'response_message' => 'Accepted by clearinghouse (stub). 999 functional acknowledgement received.',
            'submitted_at' => $claim->submitted_at ?? now(),
        ]);
    }
}
