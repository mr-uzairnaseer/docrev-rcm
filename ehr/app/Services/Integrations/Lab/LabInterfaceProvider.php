<?php

namespace App\Services\Integrations\Lab;

use App\Models\LabOrder;
use App\Models\LabResult;
use Illuminate\Support\Str;

interface LabInterfaceProvider
{
    public function sendOrder(LabOrder $order, string $hl7Message): array;

    public function testConnection(): array;
}
