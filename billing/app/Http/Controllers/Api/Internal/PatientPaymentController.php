<?php

namespace App\Http\Controllers\Api\Internal;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\Patient;
use App\Services\Billing\PatientPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientPaymentController extends Controller
{
    public function __invoke(Request $request, PatientPaymentService $paymentService): JsonResponse
    {
        $data = $request->validate([
            'billing_patient_uuid' => ['required', 'uuid'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['nullable', 'string', 'max:20'],
            'external_claim_uuid' => ['nullable', 'uuid'],
        ]);

        $patient = Patient::where('uuid', $data['billing_patient_uuid'])->first();
        if (! $patient) {
            return response()->json(['message' => 'Billing patient not found.'], 404);
        }

        $claim = null;
        if (! empty($data['external_claim_uuid'])) {
            $claim = Claim::where('uuid', $data['external_claim_uuid'])
                ->where('patient_id', $patient->id)
                ->first();
        }

        $payment = $paymentService->record(
            $patient->organization_id,
            $patient,
            (float) $data['amount'],
            $data['payment_method'] ?? 'card',
            $claim,
        );

        return response()->json([
            'message' => 'Payment recorded.',
            'reference_number' => $payment->reference_number,
            'amount' => $payment->amount,
        ], 201);
    }
}
