<?php

namespace App\Services\Billing;

use App\Models\Claim;
use App\Models\ClaimSubmission;

class ClaimSubmissionService
{
    public function __construct(private ClearinghouseInterface $clearinghouse)
    {
    }

    public function submit(Claim $claim): ClaimSubmission
    {
        if ($claim->status !== Claim::STATUS_READY) {
            throw new \RuntimeException('Only ready claims can be submitted.');
        }

        if (empty($claim->edi_837_content)) {
            throw new \RuntimeException('EDI 837 content is missing. Mark claim ready first.');
        }

        $result = $this->clearinghouse->submitClaim($claim, $claim->edi_837_content);

        $submission = ClaimSubmission::create([
            'claim_id' => $claim->id,
            'clearinghouse' => config('clearinghouse.driver', 'stub'),
            'status' => $result['success'] ? ClaimSubmission::STATUS_ACCEPTED : ClaimSubmission::STATUS_REJECTED,
            'external_reference' => $result['external_reference'] ?? null,
            'edi_837_content' => $claim->edi_837_content,
            'response_message' => $result['message'] ?? null,
            'submitted_at' => now(),
        ]);

        if ($result['success']) {
            $claim->update([
                'status' => Claim::STATUS_SUBMITTED,
                'submitted_at' => now(),
            ]);
        }

        return $submission;
    }
}
