<?php

namespace App\Services\Integrations\Surescripts;

use App\Models\Prescription;

interface SurescriptsProviderInterface
{
    public function sendNewRx(Prescription $prescription): array;

    public function testConnection(): array;
}
