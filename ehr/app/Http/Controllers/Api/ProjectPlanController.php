<?php

namespace App\Http\Controllers\Api;

use App\Support\EhrProjectPlan;
use Illuminate\Http\JsonResponse;

class ProjectPlanController extends ApiController
{
    public function show(): JsonResponse
    {
        return response()->json(['data' => EhrProjectPlan::summary()]);
    }
}
