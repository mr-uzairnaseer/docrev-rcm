<?php

namespace App\Services\Integrations\Hie;

use App\Models\HieConnection;
use App\Models\Patient;
use Illuminate\Support\Str;

class StubFhirClient implements FhirClientInterface
{
    public function queryPatient(HieConnection $connection, Patient $patient): array
    {
        return [
            'success' => true,
            'resource_type' => 'Bundle',
            'fhir_resource_id' => 'stub-'.Str::uuid(),
            'payload' => [
                'resourceType' => 'Bundle',
                'type' => 'searchset',
                'total' => 1,
                'entry' => [[
                    'resource' => [
                        'resourceType' => 'Patient',
                        'id' => 'hie-'.$patient->uuid,
                        'name' => [['family' => $patient->last_name, 'given' => [$patient->first_name]]],
                        'birthDate' => $patient->date_of_birth->format('Y-m-d'),
                        'identifier' => [['system' => 'urn:docrev:mrn', 'value' => $patient->mrn]],
                    ],
                ]],
            ],
            'message' => 'HIE patient query simulated (stub). Sign FHIR agreement for live '.$connection->name.'.',
        ];
    }

    public function pushDocument(HieConnection $connection, Patient $patient, array $document): array
    {
        return [
            'success' => true,
            'resource_type' => 'DocumentReference',
            'fhir_resource_id' => 'doc-'.Str::uuid(),
            'payload' => array_merge($document, [
                'resourceType' => 'DocumentReference',
                'subject' => ['reference' => 'Patient/hie-'.$patient->uuid],
                'status' => 'current',
            ]),
            'message' => 'Document push simulated to '.$connection->name.' (stub).',
        ];
    }

    public function testConnection(HieConnection $connection): array
    {
        return [
            'success' => true,
            'driver' => 'stub',
            'message' => 'HIE FHIR stub active for '.$connection->name
                .'. Set client credentials and sign vendor agreement for live exchange.',
        ];
    }
}
