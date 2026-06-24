<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'app' => config('app.name'),
            'type' => config('docrev.app_type'),
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
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
