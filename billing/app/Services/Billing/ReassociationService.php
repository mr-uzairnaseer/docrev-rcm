<?php

namespace App\Services\Billing;

use App\Models\EftDeposit;
use App\Models\EraRemittance;
use Illuminate\Support\Facades\Log;

class ReassociationService
{
    /**
     * Auto-associate a single EFT deposit with an ERA remittance.
     */
    public function associateDeposit(EftDeposit $deposit): bool
    {
        if ($deposit->matched_status === 'matched') {
            return true;
        }

        // Try exact match on trace number (case-insensitive)
        $era = EraRemittance::query()
            ->where('organization_id', $deposit->organization_id)
            ->where('trace_number', $deposit->trace_number)
            ->first();

        if (! $era) {
            // Check if trace number matches without prefix (e.g., SIM- or DENY-)
            $cleanTrace = preg_replace('/^(SIM|DENY)-/i', '', $deposit->trace_number);
            $era = EraRemittance::query()
                ->where('organization_id', $deposit->organization_id)
                ->where(function ($q) use ($cleanTrace) {
                    $q->where('trace_number', $cleanTrace)
                      ->orWhere('trace_number', 'like', '%' . $cleanTrace . '%');
                })
                ->first();
        }

        if ($era) {
            // Check if amounts match
            $amountDiff = abs((float)$deposit->amount - (float)$era->total_payment_amount);
            if ($amountDiff < 0.01) {
                $deposit->update([
                    'matched_status' => 'matched',
                    'era_remittance_id' => $era->id,
                ]);
                return true;
            } else {
                $deposit->update([
                    'matched_status' => 'exception',
                    'era_remittance_id' => $era->id,
                ]);
                Log::warning("Reassociation amount discrepancy for trace {$deposit->trace_number}: Deposit={$deposit->amount}, ERA={$era->total_payment_amount}");
                return false;
            }
        }

        $deposit->update(['matched_status' => 'unmatched']);
        return false;
    }

    /**
     * Reassociate all unmatched deposits.
     */
    public function reassociateAll(int $organizationId): int
    {
        $unmatched = EftDeposit::query()
            ->where('organization_id', $organizationId)
            ->whereIn('matched_status', ['unmatched', 'exception'])
            ->get();

        $associatedCount = 0;
        foreach ($unmatched as $deposit) {
            if ($this->associateDeposit($deposit)) {
                $associatedCount++;
            }
        }

        return $associatedCount;
    }

    /**
     * Manually match a deposit to an ERA.
     */
    public function manualMatch(int $depositId, int $eraId): bool
    {
        $deposit = EftDeposit::findOrFail($depositId);
        $era = EraRemittance::findOrFail($eraId);

        if ($deposit->organization_id !== $era->organization_id) {
            throw new \InvalidArgumentException("Organization mismatch between deposit and ERA.");
        }

        $deposit->update([
            'matched_status' => 'matched',
            'era_remittance_id' => $era->id,
        ]);

        return true;
    }
}
