<?php

namespace App\Services\Billing;

use App\Models\Claim;
use App\Support\IntegrationRequirements;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SftpClearinghouse implements ClearinghouseInterface
{
    public function submitClaim(Claim $claim, string $edi837): array
    {
        $this->assertConfigured();

        $config = config('clearinghouse.sftp');
        $filename = $claim->claim_number.'-'.now()->format('YmdHis').'.837';
        $localDir = storage_path('app/clearinghouse/outbound');
        File::ensureDirectoryExists($localDir);

        $localPath = $localDir.DIRECTORY_SEPARATOR.$filename;
        File::put($localPath, $edi837);

        if (function_exists('ssh2_connect') && ! empty($config['host'])) {
            $this->uploadViaSsh2($config, $localPath, $filename);
            $message = 'Claim uploaded to SFTP clearinghouse.';
        } else {
            $message = 'Claim staged locally at '.$localPath
                .'. Install php-ssh2 or configure SFTP relay for automatic upload.';
            Log::info('SFTP clearinghouse staged claim locally', [
                'path' => $localPath,
                'host' => $config['host'],
            ]);
        }

        return [
            'success' => true,
            'external_reference' => 'SFTP-'.strtoupper(Str::random(8)),
            'message' => $message,
        ];
    }

    private function assertConfigured(): void
    {
        $req = IntegrationRequirements::clearinghouse();
        if (! $req['ready']) {
            throw new \RuntimeException(
                'SFTP clearinghouse is not configured. Missing env: '.implode(', ', $req['missing'])
            );
        }
    }

    private function uploadViaSsh2(array $config, string $localPath, string $filename): void
    {
        $connection = @ssh2_connect($config['host'], (int) ($config['port'] ?? 22));
        if (! $connection) {
            throw new \RuntimeException('Could not connect to SFTP host '.$config['host']);
        }

        $authenticated = false;
        if (! empty($config['private_key_path']) && file_exists($config['private_key_path'])) {
            $authenticated = @ssh2_auth_pubkey_file(
                $connection,
                $config['username'],
                $config['public_key_path'] ?? $config['private_key_path'].'.pub',
                $config['private_key_path'],
                $config['password'] ?? null
            );
        } elseif (! empty($config['password'])) {
            $authenticated = @ssh2_auth_password($connection, $config['username'], $config['password']);
        }

        if (! $authenticated) {
            throw new \RuntimeException('SFTP authentication failed for '.$config['username'].'@'.$config['host']);
        }

        $sftp = @ssh2_sftp($connection);
        if (! $sftp) {
            throw new \RuntimeException('Could not initialize SFTP subsystem.');
        }

        $remotePath = rtrim($config['outbound_path'], '/').'/'.$filename;
        $stream = @fopen('ssh2.sftp://'.intval($sftp).$remotePath, 'w');
        if (! $stream) {
            throw new \RuntimeException('Could not open remote SFTP path: '.$remotePath);
        }

        $written = fwrite($stream, file_get_contents($localPath));
        fclose($stream);

        if ($written === false) {
            throw new \RuntimeException('Failed writing claim file to SFTP.');
        }
    }
}
