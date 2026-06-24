<?php

namespace App\Services\Integrations\Hie;

use App\Models\HieConnection;
use App\Models\HieExchange;
use App\Models\Patient;

class HieExchangeService
{
    public function __construct(private FhirClientInterface $fhirClient)
    {
    }

    public function queryPatient(HieConnection $connection, Patient $patient): HieExchange
    {
        $result = $this->fhirClient->queryPatient($connection, $patient);

        return HieExchange::create([
            'organization_id' => $patient->organization_id,
            'hie_connection_id' => $connection->id,
            'patient_id' => $patient->id,
            'direction' => HieExchange::DIRECTION_INBOUND,
            'resource_type' => $result['resource_type'],
            'fhir_resource_id' => $result['fhir_resource_id'],
            'payload' => $result['payload'],
            'status' => $result['success'] ? HieExchange::STATUS_COMPLETED : HieExchange::STATUS_FAILED,
            'response_message' => $result['message'],
            'exchanged_at' => now(),
        ]);
    }

    public function pushSummary(HieConnection $connection, Patient $patient): HieExchange
    {
        $document = [
            'type' => ['text' => 'Clinical Summary'],
            'description' => 'DocRev clinical summary for '.$patient->first_name.' '.$patient->last_name,
            'content' => [[
                'attachment' => [
                    'contentType' => 'application/json',
                    'title' => 'Clinical Summary',
                    'creation' => now()->toIso8601String(),
                ],
            ]],
        ];

        $result = $this->fhirClient->pushDocument($connection, $patient, $document);

        return HieExchange::create([
            'organization_id' => $patient->organization_id,
            'hie_connection_id' => $connection->id,
            'patient_id' => $patient->id,
            'direction' => HieExchange::DIRECTION_OUTBOUND,
            'resource_type' => $result['resource_type'],
            'fhir_resource_id' => $result['fhir_resource_id'],
            'payload' => $result['payload'],
            'status' => $result['success'] ? HieExchange::STATUS_COMPLETED : HieExchange::STATUS_FAILED,
            'response_message' => $result['message'],
            'exchanged_at' => now(),
        ]);
    }
}
