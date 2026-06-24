<?php

namespace App\Http\Controllers\Api\Internal;

use App\Http\Controllers\Controller;
use App\Models\PatientAccount;
use App\Models\PatientStatement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatementSyncController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'billing_patient_uuid' => ['nullable', 'uuid'],
            'first_name' => ['required', 'string'],
            'last_name' => ['required', 'string'],
            'date_of_birth' => ['required', 'date'],
            'external_claim_uuid' => ['nullable', 'uuid'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'balance_due' => ['required', 'numeric', 'min:0'],
            'line_items' => ['nullable', 'array'],
        ]);

        $account = null;

        if (! empty($data['billing_patient_uuid'])) {
            $account = PatientAccount::where('billing_patient_uuid', $data['billing_patient_uuid'])->first();
        }

        if (! $account) {
            $account = PatientAccount::where('first_name', $data['first_name'])
                ->where('last_name', $data['last_name'])
                ->whereDate('date_of_birth', $data['date_of_birth'])
                ->first();
        }

        if (! $account) {
            return response()->json(['message' => 'Patient account not found in portal.'], 404);
        }

        if (! empty($data['billing_patient_uuid']) && ! $account->billing_patient_uuid) {
            $account->update(['billing_patient_uuid' => $data['billing_patient_uuid']]);
        }

        $statement = PatientStatement::updateOrCreate(
            [
                'patient_account_id' => $account->id,
                'external_claim_uuid' => $data['external_claim_uuid'] ?? null,
            ],
            [
                'statement_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'total_amount' => $data['total_amount'],
                'paid_amount' => $data['paid_amount'],
                'balance_due' => $data['balance_due'],
                'status' => $data['balance_due'] > 0 ? 'open' : 'paid',
                'line_items' => $data['line_items'] ?? [],
            ]
        );

        return response()->json([
            'message' => 'Statement synced.',
            'statement_id' => $statement->id,
        ]);
    }
}
