<?php

namespace App\Services\Billing;

use App\Models\EligibilityInquiry;
use App\Models\Patient;
use App\Models\Payer;
use Illuminate\Support\Str;

class EligibilityCheckService
{
    public function __construct(
        private Edi270Builder $edi270Builder,
        private Edi271Parser $edi271Parser,
        private EligibilityProviderInterface $provider,
    ) {}

    public function check(
        int $organizationId,
        Patient $patient,
        Payer $payer,
        string $serviceDate,
        ?string $memberId = null,
    ): EligibilityInquiry {
        $memberId = $memberId ?? $patient->insurance_member_id ?? 'MEM-'.strtoupper(Str::random(6));
        $edi270 = $this->edi270Builder->build($patient, $payer, $serviceDate, $memberId);

        $response = $this->provider->checkEligibility($patient, $payer, $edi270, $serviceDate);
        $parsed = $this->edi271Parser->parse($response['edi_271']);

        if (! $patient->insurance_member_id) {
            $patient->update(['insurance_member_id' => $memberId]);
        }

        return EligibilityInquiry::create([
            'organization_id' => $organizationId,
            'patient_id' => $patient->id,
            'payer_id' => $payer->id,
            'trace_number' => 'ELG-'.strtoupper(Str::random(8)),
            'service_date' => $serviceDate,
            'member_id' => $memberId,
            'edi_270_content' => $edi270,
            'edi_271_content' => $response['edi_271'],
            'coverage_status' => $parsed['coverage_status'],
            'plan_name' => $parsed['plan_name'],
            'copay_amount' => $parsed['copay_amount'],
            'deductible_amount' => $parsed['deductible_amount'],
            'coinsurance_percent' => $parsed['coinsurance_percent'],
            'response_message' => $parsed['message'] ?? $response['message'],
            'checked_at' => now(),
        ]);
    }

    public function latestForPatient(int $organizationId, int $patientId, int $payerId): ?EligibilityInquiry
    {
        return EligibilityInquiry::forOrganization($organizationId)
            ->where('patient_id', $patientId)
            ->where('payer_id', $payerId)
            ->where('coverage_status', EligibilityInquiry::COVERAGE_ACTIVE)
            ->orderByDesc('checked_at')
            ->first();
    }
}
