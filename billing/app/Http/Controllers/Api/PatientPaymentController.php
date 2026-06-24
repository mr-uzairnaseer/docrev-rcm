<?php

namespace App\Http\Controllers\Api;

use App\Models\PatientPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientPaymentController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $payments = PatientPayment::forOrganization($this->organizationId())
            ->with('patient')
            ->orderByDesc('paid_at')
            ->paginate($request->integer('per_page', 50));

        return response()->json($payments);
    }
}
