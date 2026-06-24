<?php

namespace App\Services\Integrations\Surescripts;

use App\Models\Prescription;
use App\Support\IntegrationRequirements;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LiveSurescriptsProvider implements SurescriptsProviderInterface
{
    public function sendNewRx(Prescription $prescription): array
    {
        $this->assertConfigured();

        $prescription->load(['patient', 'provider', 'pharmacy', 'organization']);
        $payload = $this->buildNewRxPayload($prescription);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->getAccessToken(),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(30)->post(rtrim(config('surescripts.api_url'), '/').'/v1/prescriptions/new', $payload);

        if ($response->successful()) {
            return [
                'success' => true,
                'message_id' => $response->json('messageId') ?? 'SS-'.strtoupper(Str::random(10)),
                'message' => $response->json('message', 'NewRx transmitted via Surescripts.'),
                'payload' => json_encode($payload),
            ];
        }

        Log::warning('Surescripts NewRx failed', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        throw new \RuntimeException(
            'Surescripts NewRx failed ('.$response->status().'). Confirm SPI enrollment and pharmacy NCPDP ID.'
        );
    }

    public function testConnection(): array
    {
        $this->assertConfigured();
        $this->getAccessToken();

        return [
            'success' => true,
            'driver' => 'live',
            'message' => 'Surescripts OAuth token retrieved. Confirm provider SPI enrollment in Surescripts portal.',
        ];
    }

    private function assertConfigured(): void
    {
        $req = IntegrationRequirements::surescripts();
        if (! $req['ready']) {
            throw new \RuntimeException(
                'Surescripts not configured. Missing: '.implode(', ', $req['missing'])
            );
        }
    }

    private function getAccessToken(): string
    {
        $response = Http::asForm()->timeout(20)->post(
            rtrim(config('surescripts.api_url'), '/').'/oauth/token',
            [
                'grant_type' => 'client_credentials',
                'client_id' => config('surescripts.client_id'),
                'client_secret' => config('surescripts.client_secret'),
            ]
        );

        if (! $response->successful() || ! $response->json('access_token')) {
            throw new \RuntimeException('Surescripts OAuth failed. Verify client credentials.');
        }

        return $response->json('access_token');
    }

    private function buildNewRxPayload(Prescription $prescription): array
    {
        return [
            'messageType' => 'NewRx',
            'prescriberSpi' => config('surescripts.account_id'),
            'patient' => [
                'firstName' => $prescription->patient->first_name,
                'lastName' => $prescription->patient->last_name,
                'dateOfBirth' => $prescription->patient->date_of_birth->format('Y-m-d'),
                'gender' => strtoupper(substr($prescription->patient->gender ?? 'U', 0, 1)),
            ],
            'medication' => [
                'drugDescription' => $prescription->drug_name,
                'ndc' => $prescription->ndc,
                'quantity' => $prescription->quantity,
                'daysSupply' => $prescription->days_supply,
                'directions' => $prescription->sig,
                'refills' => $prescription->refills,
            ],
            'pharmacy' => [
                'ncpdpId' => $prescription->pharmacy?->ncpdp_id,
            ],
        ];
    }
}
