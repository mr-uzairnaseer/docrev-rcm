<?php

namespace App\Services\Integrations\Surescripts;

use App\Models\Prescription;
use Illuminate\Support\Str;

class StubSurescriptsProvider implements SurescriptsProviderInterface
{
    public function sendNewRx(Prescription $prescription): array
    {
        $messageId = 'SS-STUB-'.strtoupper(Str::random(10));

        return [
            'success' => true,
            'message_id' => $messageId,
            'message' => 'Prescription accepted by Surescripts stub (sandbox). Replace SURESCRIPTS_DRIVER for live e-prescribing.',
            'payload' => $this->buildNewRxPayload($prescription),
        ];
    }

    public function testConnection(): array
    {
        return [
            'success' => true,
            'driver' => 'stub',
            'message' => 'Surescripts stub active. Complete Surescripts enrollment for live NewRx transmission.',
        ];
    }

    private function buildNewRxPayload(Prescription $prescription): string
    {
        $prescription->load(['patient', 'provider', 'pharmacy']);

        return json_encode([
            'MessageType' => 'NewRx',
            'PrescriberSPI' => config('surescripts.account_id', 'DEMO-SPI'),
            'Patient' => [
                'FirstName' => $prescription->patient->first_name,
                'LastName' => $prescription->patient->last_name,
                'DateOfBirth' => $prescription->patient->date_of_birth->format('Y-m-d'),
            ],
            'Medication' => [
                'DrugDescription' => $prescription->drug_name,
                'NDC' => $prescription->ndc,
                'Quantity' => $prescription->quantity,
                'DaysSupply' => $prescription->days_supply,
                'Sig' => $prescription->sig,
                'Refills' => $prescription->refills,
            ],
            'Pharmacy' => [
                'NCPDPID' => $prescription->pharmacy?->ncpdp_id,
                'Name' => $prescription->pharmacy?->name,
            ],
        ], JSON_PRETTY_PRINT);
    }
}
