<?php

namespace App\Services\Billing;

use App\Models\Claim;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PortalStatementSyncService
{
    public function syncPatientBalance(Claim $claim, float $patientBalance): bool
    {
        $url = config('docrev.portal_api_url');
        if (! $url) {
            return false;
        }

        $patient = $claim->patient;
        if (! $patient) {
            return false;
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders(['X-DocRev-Api-Key' => config('docrev.internal_api_key')])
                ->post(rtrim($url, '/').'/api/internal/statement-sync', [
                    'billing_patient_uuid' => $patient->uuid,
                    'first_name' => $patient->first_name,
                    'last_name' => $patient->last_name,
                    'date_of_birth' => $patient->date_of_birth?->format('Y-m-d'),
                    'external_claim_uuid' => $claim->uuid,
                    'total_amount' => (float) $claim->total_charge_amount,
                    'paid_amount' => (float) $claim->paid_amount,
                    'balance_due' => $patientBalance,
                    'line_items' => [
                        ['description' => 'Patient responsibility — '.$claim->claim_number, 'amount' => $patientBalance],
                    ],
                ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::warning('Portal statement sync failed: '.$e->getMessage());

            return false;
        }
    }
}
