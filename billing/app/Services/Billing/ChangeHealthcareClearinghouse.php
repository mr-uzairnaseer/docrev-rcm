<?php

namespace App\Services\Billing;

use App\Models\Claim;
use App\Support\IntegrationRequirements;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChangeHealthcareClearinghouse implements ClearinghouseInterface
{
    use FetchesOAuthToken;

    public function submitClaim(Claim $claim, string $edi837): array
    {
        $this->assertConfigured();

        $config = config('clearinghouse.change_healthcare');
        $claim->loadMissing('organization');

        $token = $this->fetchClientCredentialsToken(
            rtrim($config['api_url'], '/').'/apip/auth/v2/token',
            $config['client_id'],
            $config['client_secret'],
        );

        $response = Http::withToken($token)
            ->timeout(60)
            ->acceptJson()
            ->post(rtrim($config['api_url'], '/').'/medicalnetwork/professionalclaims/v3/submission', [
                'submitter' => [
                    'organizationName' => $claim->organization->name ?? 'Provider',
                    'submitterIdentification' => $config['submitter_id'],
                ],
                'claimReference' => [
                    'patientControlNumber' => $claim->claim_number,
                ],
                'x12' => base64_encode($edi837),
            ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'external_reference' => $response->json('controlNumber')
                    ?? $response->json('transactionId')
                    ?? 'CHC-'.strtoupper(Str::random(10)),
                'message' => $response->json('message', 'Claim submitted via Change Healthcare.'),
            ];
        }

        Log::warning('Change Healthcare claim submission failed', [
            'claim_number' => $claim->claim_number,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        throw new \RuntimeException(
            'Change Healthcare claim submission failed ('.$response->status().'). '
            .'Verify API credentials and submitter enrollment.'
        );
    }

    private function assertConfigured(): void
    {
        $req = IntegrationRequirements::clearinghouse();
        if (! $req['ready']) {
            throw new \RuntimeException(
                'Change Healthcare is not configured. Missing env: '.implode(', ', $req['missing'])
            );
        }
    }
}
