<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreEncounterChargeRequest;
use App\Http\Requests\StoreEncounterDiagnosisRequest;
use App\Models\Encounter;
use App\Models\EncounterCharge;
use App\Models\EncounterDiagnosis;
use Illuminate\Http\JsonResponse;

class EncounterChargeController extends ApiController
{
    public function store(StoreEncounterChargeRequest $request, Encounter $encounter): JsonResponse
    {
        $this->ensureBelongsToOrganization($encounter);

        if ($encounter->signed_at) {
            return response()->json(['message' => 'Cannot add charges to a signed encounter.'], 422);
        }

        $charge = $encounter->charges()->create($request->validated());

        return response()->json(['data' => $charge], 201);
    }

    public function storeDiagnosis(StoreEncounterDiagnosisRequest $request, Encounter $encounter): JsonResponse
    {
        $this->ensureBelongsToOrganization($encounter);

        if ($encounter->signed_at) {
            return response()->json(['message' => 'Cannot add diagnoses to a signed encounter.'], 422);
        }

        $sequence = $request->sequence ?? (($encounter->diagnoses()->max('sequence') ?? 0) + 1);

        $diagnosis = $encounter->diagnoses()->create(array_merge(
            $request->validated(),
            ['sequence' => $sequence]
        ));

        return response()->json(['data' => $diagnosis], 201);
    }

    private function ensureBelongsToOrganization(Encounter $encounter): void
    {
        if (! $this->belongsToOrganization($encounter)) {
            abort(404);
        }
    }
}
