<?php

namespace App\Http\Controllers\Api;

use App\Services\Billing\RcmDashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends ApiController
{
    public function __invoke(RcmDashboardService $dashboard): JsonResponse
    {
        return response()->json([
            'data' => $dashboard->summary($this->organizationId()),
        ]);
    }
}
