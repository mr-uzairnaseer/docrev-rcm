<?php

namespace App\Services\Billing;

use App\Models\Claim;
use App\Models\ClaimDenial;
use App\Models\ClaimPayment;

class ClaimDenialService
{
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

    public function appeal(ClaimDenial $denial, string $notes): ClaimDenial
    {
        if ($denial->status !== ClaimDenial::STATUS_OPEN) {
            throw new \RuntimeException('Only open denials can be appealed.');
        }

        $denial->update([
            'status' => ClaimDenial::STATUS_APPEALED,
            'appeal_notes' => $notes,
            'appealed_at' => now(),
        ]);

        $denial->claim?->update(['status' => Claim::STATUS_SUBMITTED]);

        return $denial->fresh(['claim']);
    }
}
