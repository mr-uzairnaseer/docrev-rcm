<?php

namespace App\Services\Billing;

use App\Models\Claim;
use App\Support\IntegrationRequirements;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AvailityClearinghouse implements ClearinghouseInterface
{
    use FetchesOAuthToken;

    public function submitClaim(Claim $claim, string $edi837): array
    {
        $this->assertConfigured();

        $config = config('clearinghouse.availity');
        $token = $this->fetchClientCredentialsToken(
            rtrim($config['api_url'], '/').'/availity/v1/token',
            $config['client_id'],
            $config['client_secret'],
            ['scope' => $config['scope'] ?? 'hipaa'],
        );

        $response = Http::withToken($token)
            ->timeout(60)
            ->acceptJson()
            ->post(rtrim($config['api_url'], '/').'/availity/v1/claims/submissions', [
                'submitterId' => $config['submitter_id'],
                'receiverId' => $config['receiver_id'],
                'claimNumber' => $claim->claim_number,
                'payload' => base64_encode($edi837),
                'payloadFormat' => 'X12',
                'transactionType' => '837',
            ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'external_reference' => $response->json('id')
                    ?? $response->json('submissionId')
                    ?? 'AVL-'.strtoupper(Str::random(10)),
                'message' => $response->json('message', 'Claim submitted via Availity.'),
            ];
        }

        Log::warning('Availity claim submission failed', [
            'claim_number' => $claim->claim_number,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        throw new \RuntimeException(
            'Availity claim submission failed ('.$response->status().'). '
            .'Confirm payer enrollment and submitter ID with Availity support.'
        );
    }

    private function assertConfigured(): void
    {
        $req = IntegrationRequirements::clearinghouse();
        if (! $req['ready']) {
            throw new \RuntimeException(
                'Availity is not configured. Missing env: '.implode(', ', $req['missing'])
                .'. Set CLEARINGHOUSE_DRIVER=availity and provide Availity API credentials.'
            );
        }
    }
}
