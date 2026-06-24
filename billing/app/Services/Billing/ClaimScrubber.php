<?php

namespace App\Services\Billing;

use App\Models\Claim;
use App\Models\Payer;
use App\Models\Patient;

class ClaimScrubber
{
    public function scrub(Claim $claim): array
    {
        $claim->load(['patient', 'payer', 'claimLines', 'organization']);
        $errors = [];

        if (! $claim->patient || ! $claim->patient->is_active) {
            $errors[] = 'Patient is missing or inactive.';
        }

        if (! $claim->payer || ! $claim->payer->is_active) {
            $errors[] = 'Payer is missing or inactive.';
        }

        if ($claim->claimLines->isEmpty()) {
            $errors[] = 'Claim must have at least one service line.';
        }

        if (empty($claim->icd10_codes)) {
            $errors[] = 'At least one ICD-10 diagnosis code is required.';
        }

        if (! $this->isValidNpi($claim->rendering_provider_npi)) {
            $errors[] = 'Rendering provider NPI is invalid.';
        }

        if (! $this->isValidNpi($claim->billing_provider_npi)) {
            $errors[] = 'Billing provider NPI is invalid.';
        }

        $lineTotal = round((float) $claim->claimLines->sum('charge_amount'), 2);
        $headerTotal = round((float) $claim->total_charge_amount, 2);

        if ($lineTotal !== $headerTotal) {
            $errors[] = "Claim total ({$headerTotal}) does not match line sum ({$lineTotal}).";
        }

        foreach ($claim->claimLines as $line) {
            if (empty($line->cpt_code) && empty($line->hcpcs_code ?? null)) {
                $errors[] = "Line {$line->line_number} is missing a procedure code.";
            }

            if ((float) $line->charge_amount <= 0) {
                $errors[] = "Line {$line->line_number} must have a positive charge amount.";
            }
        }

        if ($claim->service_date_from->gt($claim->service_date_to)) {
            $errors[] = 'Service date range is invalid.';
        }

        return $errors;
    }

    public function passes(Claim $claim): bool
    {
        return $this->scrub($claim) === [];
    }

    private function isValidNpi(?string $npi): bool
    {
        return $npi && preg_match('/^\d{10}$/', $npi);
    }
}
