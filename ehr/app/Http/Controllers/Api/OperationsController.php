<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class OperationsController extends ApiController
{
    public function status(): JsonResponse
    {
        $queue = config('queue.default');
        $dbDriver = config('database.default');
        $mfaReady = (bool) config('docrev.mfa_enabled', false);

        return response()->json([
            'data' => [
                'monitoring' => [
                    'health_endpoint' => url('/api/health'),
                    'status' => 'active',
                ],
                'queue' => [
                    'driver' => $queue,
                    'production_ready' => $queue !== 'sync',
                    'recommendation' => $queue === 'sync'
                        ? 'Set QUEUE_CONNECTION=redis and run queue workers in production.'
                        : 'Queue driver configured for async jobs.',
                ],
                'database' => [
                    'driver' => $dbDriver,
                    'production_ready' => ! in_array($dbDriver, ['sqlite'], true),
                    'connected' => $this->databaseOk(),
                ],
                'backup_dr' => [
                    'documented' => true,
                    'automated' => false,
                    'recommendation' => 'Configure nightly encrypted database backups and test restore quarterly.',
                ],
                'mfa' => [
                    'enabled' => $mfaReady,
                    'ready' => true,
                    'note' => $mfaReady
                        ? 'MFA enforcement is enabled via DOCREV_MFA_ENABLED.'
                        : 'MFA-ready: set DOCREV_MFA_ENABLED=true when TOTP provider is configured.',
                ],
                'hipaa_controls' => [
                    'audit_logging' => true,
                    'rbac_enforced' => true,
                    'tenant_isolation' => true,
                    'tls_required_in_production' => true,
                ],
            ],
        ]);
    }

    private function databaseOk(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
