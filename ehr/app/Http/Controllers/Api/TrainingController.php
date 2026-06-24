<?php

namespace App\Http\Controllers\Api;

use App\Support\TrainingGuide;
use Illuminate\Http\JsonResponse;

class TrainingController extends ApiController
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => TrainingGuide::modules(),
        ]);
    }
}
