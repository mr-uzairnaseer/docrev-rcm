<?php

namespace App\Services\Integrations\Hie;

use App\Models\HieConnection;
use App\Models\Patient;

interface FhirClientInterface
{
    public function queryPatient(HieConnection $connection, Patient $patient): array;

    public function pushDocument(HieConnection $connection, Patient $patient, array $document): array;

    public function testConnection(HieConnection $connection): array;
}
