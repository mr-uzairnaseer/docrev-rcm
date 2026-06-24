<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClaimFormService
{
    public function fetchForEncounter(string $encounterUuid, string $formType): array
    {
        $response = Http::timeout(config('services.billing.timeout'))
            ->withHeaders([
                'X-DocRev-Api-Key' => config('services.billing.api_key'),
                'Accept' => 'application/json',
            ])
            ->get(
                config('services.billing.url').'/api/internal/encounter-forms/'.$encounterUuid,
                ['form' => $formType]
            );

        if (! $response->successful()) {
            Log::error('Claim form fetch failed', [
                'encounter_uuid' => $encounterUuid,
                'form' => $formType,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException($response->json('message', 'Unable to generate claim form from billing.'));
        }

        return $response->json('data');
    }
}
