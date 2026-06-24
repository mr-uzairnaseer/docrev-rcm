<?php

namespace App\Services\Billing;

use App\Support\IntegrationRequirements;
use Illuminate\Support\Facades\File;

class ClearinghouseConnectionService
{
    use FetchesOAuthToken;

    public function test(): array
    {
        $driver = config('clearinghouse.driver', 'stub');

        return match ($driver) {
            'availity' => $this->testAvaility(),
            'change_healthcare' => $this->testChangeHealthcare(),
            'sftp' => $this->testSftp(),
            default => [
                'success' => true,
                'driver' => 'stub',
                'message' => 'Stub clearinghouse active — claims stay in sandbox mode.',
            ],
        };
    }

    private function testAvaility(): array
    {
        $req = IntegrationRequirements::clearinghouse();
        if (! $req['ready']) {
            return [
                'success' => false,
                'driver' => 'availity',
                'message' => 'Missing configuration: '.implode(', ', $req['missing']),
            ];
        }

        $config = config('clearinghouse.availity');
        $this->fetchClientCredentialsToken(
            rtrim($config['api_url'], '/').'/availity/v1/token',
            $config['client_id'],
            $config['client_secret'],
            ['scope' => $config['scope'] ?? 'hipaa'],
        );

        return [
            'success' => true,
            'driver' => 'availity',
            'message' => 'Availity OAuth token retrieved successfully.',
        ];
    }

    private function testChangeHealthcare(): array
    {
        $req = IntegrationRequirements::clearinghouse();
        if (! $req['ready']) {
            return [
                'success' => false,
                'driver' => 'change_healthcare',
                'message' => 'Missing configuration: '.implode(', ', $req['missing']),
            ];
        }

        $config = config('clearinghouse.change_healthcare');
        $this->fetchClientCredentialsToken(
            rtrim($config['api_url'], '/').'/apip/auth/v2/token',
            $config['client_id'],
            $config['client_secret'],
        );

        return [
            'success' => true,
            'driver' => 'change_healthcare',
            'message' => 'Change Healthcare OAuth token retrieved successfully.',
        ];
    }

    private function testSftp(): array
    {
        $req = IntegrationRequirements::clearinghouse();
        if (! $req['ready']) {
            return [
                'success' => false,
                'driver' => 'sftp',
                'message' => 'Missing configuration: '.implode(', ', $req['missing']),
            ];
        }

        $config = config('clearinghouse.sftp');
        $localDir = storage_path('app/clearinghouse/outbound');
        File::ensureDirectoryExists($localDir);

        if (function_exists('ssh2_connect') && ! empty($config['host'])) {
            $connection = @ssh2_connect($config['host'], (int) ($config['port'] ?? 22));
            if (! $connection) {
                return [
                    'success' => false,
                    'driver' => 'sftp',
                    'message' => 'Could not connect to '.$config['host'],
                ];
            }

            return [
                'success' => true,
                'driver' => 'sftp',
                'message' => 'SFTP host reachable. Outbound path: '.$config['outbound_path'],
            ];
        }

        return [
            'success' => true,
            'driver' => 'sftp',
            'message' => 'SFTP credentials configured. Claims stage locally at storage/app/clearinghouse/outbound (php-ssh2 not available).',
        ];
    }
}
