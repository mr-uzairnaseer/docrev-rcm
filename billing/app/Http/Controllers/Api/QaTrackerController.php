<?php

namespace App\Http\Controllers\Api;

use App\Services\Billing\RcmQaService;
use Illuminate\Http\JsonResponse;

class QaTrackerController extends ApiController
{
    public function __invoke(RcmQaService $qa): JsonResponse
    {
        return response()->json([
            'data' => $qa->tracker($this->organizationId()),
        ]);
    }
}
