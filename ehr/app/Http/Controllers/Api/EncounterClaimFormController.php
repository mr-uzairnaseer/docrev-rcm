<?php

namespace App\Http\Controllers\Api;

use App\Models\Encounter;
use App\Services\ClaimFormBuilder;
use Illuminate\Http\JsonResponse;

class EncounterClaimFormController extends ApiController
{
    public function __invoke(Encounter $encounter, string $form, ClaimFormBuilder $builder): JsonResponse
    {
        $this->ensureBelongsToOrganization($encounter);

        if ($encounter->billing_sync_status !== 'synced') {
            return response()->json([
                'message' => 'Encounter must be synced to billing before generating claim forms.',
            ], 422);
        }

        try {
            $data = $builder->buildForEncounter($encounter, $form);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $data]);
    }

    private function ensureBelongsToOrganization(Encounter $encounter): void
    {
        if (! $this->belongsToOrganization($encounter)) {
            abort(404);
        }
    }
}
