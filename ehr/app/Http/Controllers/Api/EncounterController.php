<?php

namespace App\Http\Controllers\Api;

use App\Jobs\SyncSignedEncounterToBilling;
use App\Http\Requests\StoreEncounterRequest;
use App\Http\Requests\UpdateEncounterRequest;
use App\Http\Resources\EncounterResource;
use App\Models\Encounter;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EncounterController extends ApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Encounter::forOrganization($this->organizationId())
            ->with(['patient', 'provider', 'location'])
            ->orderByDesc('encounter_date');

        if ($patientId = $request->query('patient_id')) {
            $query->where('patient_id', $patientId);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return EncounterResource::collection(
            $query->paginate($request->integer('per_page', 15))
        );
    }

    public function store(StoreEncounterRequest $request): JsonResponse
    {
        $this->ensurePatientInOrganization($request->patient_id);

        $encounter = Encounter::create(array_merge(
            $request->validated(),
            ['organization_id' => $this->organizationId()]
        ));

        return (new EncounterResource($encounter->load(['patient', 'provider', 'location'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Encounter $encounter): EncounterResource
    {
        $this->ensureBelongsToOrganization($encounter);

        return new EncounterResource($encounter->load(['patient', 'provider', 'location', 'diagnoses', 'charges']));
    }

    public function update(UpdateEncounterRequest $request, Encounter $encounter): EncounterResource
    {
        $this->ensureBelongsToOrganization($encounter);
        $encounter->update($request->validated());

        return new EncounterResource($encounter->fresh()->load(['patient', 'provider', 'location']));
    }

    public function sign(Encounter $encounter): EncounterResource|JsonResponse
    {
        $this->ensureBelongsToOrganization($encounter);

        if ($encounter->signed_at) {
            return response()->json(['message' => 'Encounter already signed.'], 422);
        }

        $encounter->loadCount(['charges', 'diagnoses']);

        if ($encounter->charges_count < 1) {
            return response()->json(['message' => 'Add at least one charge line before signing.'], 422);
        }

        if ($encounter->diagnoses_count < 1) {
            return response()->json(['message' => 'Add at least one ICD-10 diagnosis before signing.'], 422);
        }

        $encounter->update([
            'status' => Encounter::STATUS_COMPLETED,
            'signed_at' => now(),
            'signed_by' => auth()->id(),
            'billing_sync_status' => 'pending',
        ]);

        try {
            SyncSignedEncounterToBilling::dispatchSync($encounter->fresh());
        } catch (\Throwable $e) {
            $encounter->update(['billing_sync_status' => 'failed']);
        }

        return new EncounterResource($encounter->fresh()->load(['patient', 'provider', 'location', 'diagnoses', 'charges']));
    }

    private function ensureBelongsToOrganization(Encounter $encounter): void
    {
        if (! $this->belongsToOrganization($encounter)) {
            abort(404);
        }
    }

    private function ensurePatientInOrganization(int $patientId): void
    {
        $exists = Patient::forOrganization($this->organizationId())
            ->where('id', $patientId)
            ->exists();

        if (! $exists) {
            abort(422, 'Patient does not belong to your organization.');
        }
    }
}
