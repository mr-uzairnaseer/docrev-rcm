<?php

namespace App\Services\Billing;

use App\Models\Claim;
use App\Models\Patient;
use App\Models\PatientPayment;
use Illuminate\Support\Str;

class PatientPaymentService
{
    public function __construct(private PortalStatementSyncService $portalSync) {}

    public function record(
        int $organizationId,
        Patient $patient,
        float $amount,
        string $paymentMethod = 'card',
        ?Claim $claim = null,
        ?string $reference = null,
    ): PatientPayment {
        $payment = PatientPayment::create([
            'organization_id' => $organizationId,
            'patient_id' => $patient->id,
            'claim_id' => $claim?->id,
            'external_claim_uuid' => $claim?->uuid,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'reference_number' => $reference ?? 'PAY-'.strtoupper(Str::random(8)),
            'status' => PatientPayment::STATUS_POSTED,
            'paid_at' => now(),
        ]);

        if ($claim && $claim->patient_responsibility > 0) {
            $newBalance = max(0, (float) $claim->patient_responsibility - $amount);
            $claim->update(['patient_responsibility' => $newBalance]);

            $synced = $this->portalSync->syncPatientBalance(
                $claim->fresh()->load('patient'),
                $newBalance,
            );
            $payment->update(['portal_synced' => $synced]);
        }

        return $payment->fresh(['patient', 'claim']);
    }
}
