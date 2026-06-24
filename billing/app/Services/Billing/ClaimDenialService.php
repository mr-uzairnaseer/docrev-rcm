<?php

namespace App\Services\Billing;

use App\Models\Claim;
use App\Models\ClaimDenial;
use App\Models\ClaimPayment;

class ClaimDenialService
{
    public function __construct(
        private Edi837Builder $ediBuilder,
        private ClaimSubmissionService $submissionService,
        private ClaimScrubber $scrubber,
    ) {
    }

    public function recordFromPayment(Claim $claim, ClaimPayment $payment): ClaimDenial
    {
        return ClaimDenial::create([
            'organization_id' => $claim->organization_id,
            'claim_id' => $claim->id,
            'claim_payment_id' => $payment->id,
            'reason_code' => 'CO-97',
            'reason_description' => 'Claim denied — benefit maximum reached or service not covered (demo).',
            'denied_amount' => max(0, (float) $claim->total_charge_amount - (float) $payment->paid_amount),
            'status' => ClaimDenial::STATUS_OPEN,
        ]);
    }

    public function appeal(ClaimDenial $denial, string $notes, ?string $templateType = null): ClaimDenial
    {
        if ($denial->status !== ClaimDenial::STATUS_OPEN) {
            throw new \RuntimeException('Only open denials can be appealed.');
        }

        $claim = $denial->claim;
        if (! $claim) {
            throw new \RuntimeException('Denial is not linked to a claim.');
        }

        $claim->load(['patient', 'payer', 'claimLines', 'organization']);

        $errors = $this->scrubber->scrub($claim);
        if ($errors !== []) {
            throw new \RuntimeException('Claim failed re-scrub before appeal resubmission: '.implode('; ', $errors));
        }

        $ediContent = $this->ediBuilder->build($claim);
        $claim->update([
            'status' => Claim::STATUS_READY,
            'edi_837_content' => $ediContent,
            'edi_generated_at' => now(),
            'scrub_errors' => null,
        ]);

        $this->submissionService->submit($claim->fresh());

        $appealNotes = $notes;
        if ($templateType) {
            $appealNotes = "[Template: {$templateType}]\n".$notes;
        }

        $denial->update([
            'status' => ClaimDenial::STATUS_APPEALED,
            'appeal_notes' => $appealNotes,
            'appealed_at' => now(),
        ]);

        return $denial->fresh(['claim.patient', 'claim.claimLines']);
    }
}
