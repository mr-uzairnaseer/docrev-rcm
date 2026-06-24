<?php

namespace App\Services;

use App\Models\Patient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CrossAppSyncService
{
    public function provisionPatient(Patient $patient): void
    {
        $patient->load('organization');
        $billingUuid = $this->syncToBilling($patient);

        if ($billingUuid) {
            $this->syncToPortal($patient, $billingUuid);
        }
    }

    public function syncToBilling(Patient $patient): ?string
    {
        try {
            $response = Http::timeout(config('services.billing.timeout'))
                ->withHeaders([
                    'X-DocRev-Api-Key' => config('services.billing.api_key'),
                    'Accept' => 'application/json',
                ])
                ->post(config('services.billing.url').'/api/internal/patient-provision', [
                    'organization' => [
                        'slug' => $patient->organization->slug,
                        'name' => $patient->organization->name,
                        'npi' => $patient->organization->npi,
                    ],
                    'patient' => [
                        'uuid' => $patient->uuid,
                        'first_name' => $patient->first_name,
                        'last_name' => $patient->last_name,
                        'date_of_birth' => $patient->date_of_birth->format('Y-m-d'),
                        'gender' => $patient->gender,
                        'mrn' => $patient->mrn,
                        'email' => $patient->email,
                        'phone' => $patient->phone,
                    ],
                ]);

            if ($response->successful()) {
                return $response->json('billing_patient_uuid');
            }

            Log::warning('Billing patient provision failed', ['status' => $response->status(), 'body' => $response->body()]);
        } catch (\Throwable $e) {
            Log::warning('Billing patient provision error: '.$e->getMessage());
        }

        return null;
    }

    public function syncToPortal(Patient $patient, ?string $billingPatientUuid = null): bool
    {
        $url = config('services.portal.url');
        if (! $url) {
            return false;
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'X-DocRev-Api-Key' => config('services.portal.api_key'),
                    'Accept' => 'application/json',
                ])
                ->post(rtrim($url, '/').'/api/internal/patient-provision', [
                    'organization' => [
                        'slug' => $patient->organization->slug,
                        'name' => $patient->organization->name,
                    ],
                    'patient' => [
                        'uuid' => $patient->uuid,
                        'billing_patient_uuid' => $billingPatientUuid,
                        'first_name' => $patient->first_name,
                        'last_name' => $patient->last_name,
                        'date_of_birth' => $patient->date_of_birth->format('Y-m-d'),
                        'email' => $patient->email,
                        'phone' => $patient->phone,
                    ],
                ]);

            if ($response->successful()) {
                return true;
            }

            Log::warning('Portal patient provision failed', ['status' => $response->status(), 'body' => $response->body()]);
        } catch (\Throwable $e) {
            Log::warning('Portal patient provision error: '.$e->getMessage());
        }

        return false;
    }
}
