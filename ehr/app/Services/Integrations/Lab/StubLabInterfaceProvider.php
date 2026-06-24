<?php

namespace App\Services\Integrations\Lab;

use App\Models\LabOrder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class StubLabInterfaceProvider implements LabInterfaceProvider
{
    public function sendOrder(LabOrder $order, string $hl7Message): array
    {
        $ref = 'LAB-STUB-'.strtoupper(Str::random(8));
        $dir = storage_path('app/lab/outbound');
        File::ensureDirectoryExists($dir);
        File::put($dir.DIRECTORY_SEPARATOR.$ref.'.hl7', $hl7Message);

        return [
            'success' => true,
            'external_order_id' => $ref,
            'message' => 'Lab order staged locally (stub). File: storage/app/lab/outbound/'.$ref.'.hl7',
        ];
    }

    public function testConnection(): array
    {
        return [
            'success' => true,
            'driver' => 'stub',
            'message' => 'Lab HL7 stub active. Configure vendor MLLP/FHIR credentials for live orders.',
        ];
    }
}
