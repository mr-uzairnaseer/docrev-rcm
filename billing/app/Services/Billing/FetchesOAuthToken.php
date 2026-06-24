<?php

namespace App\Services\Billing;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait FetchesOAuthToken
{
    protected function fetchClientCredentialsToken(
        string $tokenUrl,
        string $clientId,
        string $clientSecret,
        array $extra = []
    ): string {
        $response = Http::asForm()
            ->timeout(30)
            ->post($tokenUrl, array_merge([
                'grant_type' => 'client_credentials',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
            ], $extra));

        if (! $response->successful()) {
            Log::warning('Clearinghouse OAuth failed', [
                'url' => $tokenUrl,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException(
                'Clearinghouse OAuth token request failed ('.$response->status().'). '
                .'Verify API credentials and endpoint URL.'
            );
        }

        $token = $response->json('access_token');

        if (! $token) {
            throw new \RuntimeException('Clearinghouse OAuth response did not include access_token.');
        }

        return $token;
    }
}
