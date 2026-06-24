<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PatientPaymentController extends Controller
{
    public function pay(Request $request): JsonResponse
    {
        $data = $request->validate([
            'statement_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $account = $request->user();
        $statement = $account->statements()->find($data['statement_id']);

        if (! $statement) {
            return response()->json(['message' => 'Statement not found.'], 404);
        }

        if ((float) $data['amount'] > (float) $statement->balance_due) {
            return response()->json(['message' => 'Amount exceeds balance due.'], 422);
        }

        $billingUrl = config('docrev.billing_api_url');
        if (! $billingUrl || ! $account->billing_patient_uuid) {
            return response()->json(['message' => 'Payment processing is not configured.'], 503);
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders(['X-DocRev-Api-Key' => config('docrev.internal_api_key')])
                ->post(rtrim($billingUrl, '/').'/api/internal/patient-payment', [
                    'billing_patient_uuid' => $account->billing_patient_uuid,
                    'amount' => (float) $data['amount'],
                    'payment_method' => 'card',
                    'external_claim_uuid' => $statement->external_claim_uuid,
                ]);

            if (! $response->successful()) {
                return response()->json([
                    'message' => $response->json('message') ?? 'Payment failed.',
                ], $response->status());
            }

            $statement->refresh();

            return response()->json([
                'message' => 'Payment successful (demo — no real card charged).',
                'reference_number' => $response->json('reference_number'),
                'statement' => [
                    'id' => $statement->id,
                    'balance_due' => $statement->balance_due,
                    'status' => $statement->status,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Unable to process payment.'], 502);
        }
    }
}
