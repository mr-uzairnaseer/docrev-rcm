<?php

namespace App\Services;

use App\Models\Encounter;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BillingSyncService
{
    public function syncSignedEncounter(Encounter $encounter): array
    {
        $encounter->load(['patient', 'provider', 'location', 'diagnoses', 'charges', 'organization']);

        if ($encounter->charges->isEmpty()) {
            throw new \RuntimeException('Encounter must have at least one charge line before billing sync.');
        }

        $payload = [
            'organization' => [
                'slug' => $encounter->organization->slug,
                'name' => $encounter->organization->name,
                'npi' => $encounter->organization->npi,
            ],
            'patient' => [
                'uuid' => $encounter->patient->uuid,
                'first_name' => $encounter->patient->first_name,
                'last_name' => $encounter->patient->last_name,
                'date_of_birth' => $encounter->patient->date_of_birth->format('Y-m-d'),
                'gender' => $encounter->patient->gender,
                'mrn' => $encounter->patient->mrn,
            ],
            'encounter' => [
                'uuid' => $encounter->uuid,
                'encounter_date' => $encounter->encounter_date->toIso8601String(),
                'provider_npi' => $encounter->provider->npi,
                'place_of_service' => $encounter->location?->place_of_service_code ?? '11',
            ],
            'diagnoses' => $encounter->diagnoses->pluck('icd10_code')->values()->all(),
            'charges' => $encounter->charges->map(fn ($charge) => [
                'cpt_code' => $charge->cpt_code,
                'hcpcs_code' => $charge->hcpcs_code,
                'modifier_1' => $charge->modifier_1,
                'modifier_2' => $charge->modifier_2,
                'units' => $charge->units,
                'charge_amount' => (float) $charge->charge_amount,
                'diagnosis_pointers' => $charge->diagnosis_pointers ?? [1],
                'service_date' => $encounter->encounter_date->format('Y-m-d'),
            ])->values()->all(),
        ];

        $response = Http::timeout(config('services.billing.timeout'))
            ->withHeaders([
                'X-DocRev-Api-Key' => config('services.billing.api_key'),
                'Accept' => 'application/json',
            ])
            ->post(config('services.billing.url').'/api/internal/encounter-sync', $payload);

        if (! $response->successful()) {
            Log::error('Billing sync failed', [
                'encounter_uuid' => $encounter->uuid,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException('Billing sync failed: '.$response->json('message', $response->body()));
        }

        $data = $response->json();

        $encounter->charges()->update([
            'synced_to_billing' => true,
            'synced_at' => now(),
        ]);

        $encounter->update([
            'billing_synced_at' => now(),
            'billing_sync_status' => 'synced',
        ]);

        return $data;
    }
}
