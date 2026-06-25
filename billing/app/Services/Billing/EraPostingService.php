<?php

namespace App\Services\Billing;

use App\Models\Claim;
use App\Models\ClaimPayment;
use App\Models\EraRemittance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

    public function __construct(
        private Edi835Parser $parser,
        private PortalStatementSyncService $portalSync,
        private ClaimDenialService $denialService,
        private ReassociationService $reassociation,
    ) {}

    public function post(int $organizationId, string $edi835, ?string $traceNumber = null): EraRemittance
    {
        $parsed = $this->parser->parse($edi835);
        $claims = $parsed['claims'] ?? [];

        return DB::transaction(function () use ($organizationId, $edi835, $traceNumber, $claims) {
            $totalPaid = 0;
            $matched = 0;

            $remittance = EraRemittance::create([
                'organization_id' => $organizationId,
                'trace_number' => $traceNumber ?? 'ERA-'.strtoupper(Str::random(8)),
                'edi_835_content' => $edi835,
                'claim_count' => count($claims),
                'matched_count' => 0,
                'status' => EraRemittance::STATUS_POSTED,
                'posted_at' => now(),
            ]);

            foreach ($claims as $eraClaim) {
                $claimNumber = $eraClaim['claim_number'] ?? null;
                if (! $claimNumber) {
                    continue;
                }

                $paid = (float) ($eraClaim['paid_amount'] ?? 0);
                $patientResp = (float) ($eraClaim['patient_responsibility'] ?? 0);
                $totalCharge = (float) ($eraClaim['total_charge'] ?? 0);
                $eraStatus = $eraClaim['status'] ?? '1';
                $totalPaid += $paid;

                $claim = Claim::forOrganization($organizationId)
                    ->where('claim_number', $claimNumber)
                    ->first();

                $paymentStatus = $this->resolvePaymentStatus($eraStatus, $paid, $totalCharge);

                $payment = ClaimPayment::create([
                    'era_remittance_id' => $remittance->id,
                    'claim_id' => $claim?->id,
                    'claim_number' => $claimNumber,
                    'era_status' => $eraStatus,
                    'total_charge' => $totalCharge,
                    'paid_amount' => $paid,
                    'patient_responsibility' => $patientResp,
                    'payment_status' => $paymentStatus,
                ]);

                if ($claim) {
                    $matched++;
                    $claimStatus = match ($paymentStatus) {
                        ClaimPayment::STATUS_DENIED => Claim::STATUS_DENIED,
                        ClaimPayment::STATUS_PARTIAL => Claim::STATUS_PARTIAL,
                        default => Claim::STATUS_PAID,
                    };

                    $claim->update([
                        'status' => $claimStatus,
                        'paid_amount' => $paid,
                        'patient_responsibility' => $patientResp,
                        'paid_at' => now(),
                    ]);

                    if ($paymentStatus === ClaimPayment::STATUS_DENIED) {
                        $this->denialService->recordFromPayment($claim, $payment);
                    }

                    if ($patientResp > 0) {
                        $synced = $this->portalSync->syncPatientBalance($claim->fresh()->load('patient'), $patientResp);
                        $payment->update(['portal_synced' => $synced]);
                    }
                }
            }

            $remittance->update([
                'total_payment_amount' => $totalPaid,
                'matched_count' => $matched,
                'status' => $matched === 0
                    ? EraRemittance::STATUS_FAILED
                    : ($matched < count($claims) ? EraRemittance::STATUS_PARTIAL : EraRemittance::STATUS_POSTED),
            ]);

            // Auto-create a matching EFT deposit to simulate bank-clearing side
            $deposit = \App\Models\EftDeposit::updateOrCreate(
                [
                    'organization_id' => $organizationId,
                    'trace_number' => $remittance->trace_number,
                ],
                [
                    'amount' => $totalPaid,
                    'deposit_date' => now()->toDateString(),
                    'payer_id' => count($claims) > 0 ? (Claim::where('claim_number', $claims[0]['claim_number'])->value('payer_id') ?? null) : null,
                ]
            );
            $this->reassociation->associateDeposit($deposit);

            return $remittance->fresh()->load('claimPayments');
        });
    }

    private function resolvePaymentStatus(string $eraStatus, float $paid, float $totalCharge): string
    {
        if ($eraStatus === '4' || ($paid <= 0 && $totalCharge > 0)) {
            return ClaimPayment::STATUS_DENIED;
        }

        if ($paid > 0 && $paid < $totalCharge) {
            return ClaimPayment::STATUS_PARTIAL;
        }

        return ClaimPayment::STATUS_PAID;
    }
}
