<?php

namespace App\Services;

use App\Models\Appointment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PortalAppointmentSyncService
{
    public function sync(Appointment $appointment): bool
    {
        $url = config('services.portal.url');
        if (! $url) {
            return false;
        }

        $appointment->load(['patient', 'provider', 'location', 'organization']);

        return $this->postSync($appointment, $url, true);
    }

    private function postSync(Appointment $appointment, string $url, bool $allowRetry): bool
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'X-DocRev-Api-Key' => config('services.portal.api_key'),
                    'Accept' => 'application/json',
                ])
                ->post(rtrim($url, '/').'/api/internal/appointment-sync', [
                    'ehr_patient_uuid' => $appointment->patient->uuid,
                    'appointment' => [
                        'uuid' => $appointment->uuid,
                        'provider_name' => $appointment->provider->first_name.' '.$appointment->provider->last_name,
                        'location_name' => $appointment->location?->name,
                        'appointment_at' => $appointment->scheduled_at->toIso8601String(),
                        'appointment_type' => $appointment->appointment_type,
                        'status' => $appointment->status,
                        'notes' => $appointment->notes,
                    ],
                ]);

            if ($response->successful()) {
                $appointment->update([
                    'portal_sync_status' => 'synced',
                    'portal_synced_at' => now(),
                ]);

                return true;
            }

            if ($response->status() === 404 && $allowRetry) {
                app(CrossAppSyncService::class)->provisionPatient($appointment->patient->load('organization'));

                return $this->postSync(
                    $appointment->fresh()->load(['patient', 'provider', 'location', 'organization']),
                    $url,
                    false
                );
            }

            $appointment->update(['portal_sync_status' => 'failed']);
            Log::warning('Portal appointment sync failed', ['body' => $response->body()]);
        } catch (\Throwable $e) {
            $appointment->update(['portal_sync_status' => 'failed']);
            Log::warning('Portal appointment sync error: '.$e->getMessage());
        }

        return false;
    }
}
