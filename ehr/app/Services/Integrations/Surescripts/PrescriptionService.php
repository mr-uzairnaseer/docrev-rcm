<?php

namespace App\Services\Integrations\Surescripts;

use App\Models\Prescription;
use App\Models\SurescriptsEnrollment;

class PrescriptionService
{
    public function __construct(private SurescriptsProviderInterface $provider)
    {
    }

    public function send(Prescription $prescription): Prescription
    {
        if ($prescription->status !== Prescription::STATUS_DRAFT) {
            throw new \RuntimeException('Only draft prescriptions can be sent.');
        }

        $this->ensureProviderEnrolled($prescription);

        $result = $this->provider->sendNewRx($prescription->load(['patient', 'provider', 'pharmacy']));

        $prescription->update([
            'status' => $result['success'] ? Prescription::STATUS_SENT : Prescription::STATUS_FAILED,
            'surescripts_message_id' => $result['message_id'] ?? null,
            'transmission_payload' => $result['payload'] ?? null,
            'transmission_response' => $result['message'] ?? null,
            'sent_at' => $result['success'] ? now() : null,
        ]);

        return $prescription->fresh()->load(['patient', 'provider', 'pharmacy']);
    }

    private function ensureProviderEnrolled(Prescription $prescription): void
    {
        if (config('surescripts.driver') === 'stub') {
            return;
        }

        $enrolled = SurescriptsEnrollment::forOrganization($prescription->organization_id)
            ->where('provider_id', $prescription->provider_id)
            ->where('status', SurescriptsEnrollment::STATUS_ACTIVE)
            ->exists();

        if (! $enrolled) {
            throw new \RuntimeException(
                'Provider is not enrolled with Surescripts. Complete enrollment before e-prescribing.'
            );
        }
    }
}
