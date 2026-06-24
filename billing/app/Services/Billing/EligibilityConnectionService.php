<?php

namespace App\Services\Billing;

use App\Support\IntegrationRequirements;

class EligibilityConnectionService
{
    use FetchesOAuthToken;

    public function test(): array
    {
        $driver = config('eligibility.driver', 'stub');

        if ($driver === 'stub') {
            return [
                'success' => true,
                'driver' => 'stub',
                'message' => 'Stub eligibility provider active — checks return demo coverage.',
            ];
        }

        $req = IntegrationRequirements::eligibility();
        if (! $req['ready']) {
            return [
                'success' => false,
                'driver' => $driver,
                'message' => 'Missing configuration: '.implode(', ', $req['missing']),
            ];
        }

        return match ($driver) {
            'availity' => $this->testOAuthDriver('availity', config('eligibility.availity') ?? config('clearinghouse.availity'), '/availity/v1/token'),
            'change_healthcare' => $this->testOAuthDriver('change_healthcare', config('eligibility.change_healthcare') ?? config('clearinghouse.change_healthcare'), '/apip/auth/v2/token'),
            default => [
                'success' => true,
                'driver' => $driver,
                'message' => 'Eligibility driver configured.',
            ],
        };
    }

    private function testOAuthDriver(string $driver, ?array $config, string $tokenPath): array
    {
        if (! $config || empty($config['client_id'])) {
            return [
                'success' => false,
                'driver' => $driver,
                'message' => 'OAuth credentials not configured for '.$driver.'.',
            ];
        }

        $this->fetchClientCredentialsToken(
            rtrim($config['api_url'], '/').$tokenPath,
            $config['client_id'],
            $config['client_secret'],
            isset($config['scope']) ? ['scope' => $config['scope']] : [],
        );

        return [
            'success' => true,
            'driver' => $driver,
            'message' => ucfirst(str_replace('_', ' ', $driver)).' eligibility OAuth token retrieved successfully.',
        ];
    }
}
