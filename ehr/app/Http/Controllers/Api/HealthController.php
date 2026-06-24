<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $queue = config('queue.default');
        $checks = [
            'app' => config('app.name'),
            'type' => config('docrev.app_type'),
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'queue' => [
                'driver' => $queue,
                'status' => $queue === 'sync' ? 'dev_only' : 'ok',
            ],
            'backup_dr' => [
                'status' => 'documented',
                'note' => 'Configure encrypted nightly backups before production go-live.',
            ],
            'mfa' => [
                'enabled' => (bool) config('docrev.mfa_enabled', false),
                'status' => 'ready',
            ],
        ];

        $healthy = $checks['database'] === 'ok';

        return response()->json([
            'status' => $healthy ? 'healthy' : 'degraded',
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $healthy ? 200 : 503);
    }

    private function checkDatabase(): string
    {
        try {
            DB::connection()->getPdo();

            return 'ok';
        } catch (\Throwable) {
            return 'failed';
        }
    }

    private function checkRedis(): string
    {
        try {
            if (config('cache.default') !== 'redis' && config('queue.default') !== 'redis') {
                return 'skipped';
            }

            \Illuminate\Support\Facades\Redis::connection()->ping();

            return 'ok';
        } catch (\Throwable) {
            return 'failed';
        }
    }
}
