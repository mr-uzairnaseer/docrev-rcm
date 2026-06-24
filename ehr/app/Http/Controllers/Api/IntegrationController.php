<?php

namespace App\Http\Controllers\Api;

use App\Services\Integrations\Lab\LabInterfaceProvider;
use App\Services\Integrations\Surescripts\SurescriptsProviderInterface;
use App\Support\IntegrationRequirements;
use Illuminate\Http\JsonResponse;

class IntegrationController extends ApiController
{
    public function requirements(): JsonResponse
    {
        $sections = IntegrationRequirements::all();

        return response()->json([
            'all_ready_for_production' => collect($sections)->every(fn ($s) => $s['ready']),
            'sections' => $sections,
        ]);
    }

    public function testSurescripts(SurescriptsProviderInterface $provider): JsonResponse
    {
        $result = $provider->testConnection();

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function testLab(LabInterfaceProvider $provider): JsonResponse
    {
        $result = $provider->testConnection();

        return response()->json($result, $result['success'] ? 200 : 422);
    }
}
