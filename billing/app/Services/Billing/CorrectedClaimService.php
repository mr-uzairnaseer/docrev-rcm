<?php

namespace App\Services\Billing;

use App\Models\Claim;
use App\Models\ClaimDenial;
use App\Models\ClaimLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CorrectedClaimService
{
    public const FREQUENCY_ORIGINAL = '1';
    public const FREQUENCY_CORRECTED = '7';

    public function createCorrectedClaim(Claim $original): Claim
    {
        if (! in_array($original->status, [Claim::STATUS_DENIED, Claim::STATUS_REJECTED, Claim::STATUS_PARTIAL], true)) {
            throw new \RuntimeException('Only denied, rejected, or partial claims can be corrected.');
        }

        return DB::transaction(function () use ($original) {
            $original->load(['claimLines']);

            $corrected = Claim::create([
                'organization_id' => $original->organization_id,
                'patient_id' => $original->patient_id,
                'payer_id' => $original->payer_id,
                'original_claim_id' => $original->id,
                'claim_number' => 'CLM-COR-'.strtoupper(Str::random(8)),
                'claim_type' => $original->claim_type,
                'frequency_code' => self::FREQUENCY_CORRECTED,
                'service_date_from' => $original->service_date_from,
                'service_date_to' => $original->service_date_to,
                'total_charge_amount' => $original->total_charge_amount,
                'paid_amount' => 0,
                'patient_responsibility' => 0,
                'status' => Claim::STATUS_DRAFT,
                'icd10_codes' => $original->icd10_codes,
                'rendering_provider_npi' => $original->rendering_provider_npi,
                'billing_provider_npi' => $original->billing_provider_npi,
                'place_of_service' => $original->place_of_service,
            ]);

            foreach ($original->claimLines as $line) {
                ClaimLine::create([
                    'claim_id' => $corrected->id,
                    'charge_id' => $line->charge_id,
                    'line_number' => $line->line_number,
                    'cpt_code' => $line->cpt_code,
                    'modifier_1' => $line->modifier_1,
                    'modifier_2' => $line->modifier_2,
                    'units' => $line->units,
                    'charge_amount' => $line->charge_amount,
                    'diagnosis_pointers' => $line->diagnosis_pointers,
                ]);
            }

            ClaimDenial::where('claim_id', $original->id)
                ->where('status', ClaimDenial::STATUS_OPEN)
                ->update([
                    'status' => ClaimDenial::STATUS_RESOLVED,
                    'resolved_at' => now(),
                ]);

            return $corrected->load(['patient', 'claimLines', 'originalClaim']);
        });
    }

    public function correctAndResubmit(
        Claim $original,
        ClaimScrubber $scrubber,
        Edi837Builder $ediBuilder,
        ClaimSubmissionService $submissionService
    ): array {
        $corrected = $this->createCorrectedClaim($original);

        $errors = $scrubber->scrub($corrected);
        if ($errors !== []) {
            $corrected->update(['scrub_errors' => $errors]);

            throw new \RuntimeException('Corrected claim failed scrubbing: '.implode('; ', $errors));
        }

        $corrected->update([
            'status' => Claim::STATUS_READY,
            'edi_837_content' => $ediBuilder->build($corrected),
            'edi_generated_at' => now(),
            'scrub_errors' => null,
        ]);

        $submission = $submissionService->submit($corrected->fresh()->load(['patient', 'claimLines', 'originalClaim']));

        return [
            'corrected_claim' => $corrected->fresh()->load(['patient', 'claimLines', 'originalClaim']),
            'submission' => $submission,
        ];
    }
}
