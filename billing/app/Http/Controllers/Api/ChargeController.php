<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreChargeRequest;
use App\Http\Resources\ChargeResource;
use App\Models\Charge;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ChargeController extends ApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Charge::forOrganization($this->organizationId())
            ->with('patient')
            ->orderByDesc('service_date');

        if ($patientId = $request->query('patient_id')) {
            $query->where('patient_id', $patientId);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return ChargeResource::collection($query->paginate($request->integer('per_page', 15)));
    }

    public function store(StoreChargeRequest $request): JsonResponse
    {
        $this->ensurePatientInOrganization($request->patient_id);

        $charge = Charge::create(array_merge(
            $request->validated(),
            [
                'organization_id' => $this->organizationId(),
                'status' => Charge::STATUS_PENDING,
            ]
        ));

        return (new ChargeResource($charge->load('patient')))->response()->setStatusCode(201);
    }

    public function show(Charge $charge): ChargeResource
    {
        $this->ensureBelongsToOrganization($charge);

        return new ChargeResource($charge->load('patient'));
    }

    public function markReady(Charge $charge): ChargeResource|JsonResponse
    {
        $this->ensureBelongsToOrganization($charge);

        if ($charge->status === Charge::STATUS_BILLED) {
            return response()->json(['message' => 'Charge already billed.'], 422);
        }

        $charge->update(['status' => Charge::STATUS_READY]);

        return new ChargeResource($charge->fresh()->load('patient'));
    }

    private function ensureBelongsToOrganization(Charge $charge): void
    {
        if (! $this->belongsToOrganization($charge)) {
            abort(404);
        }
    }

    private function ensurePatientInOrganization(int $patientId): void
    {
        if (! Patient::forOrganization($this->organizationId())->where('id', $patientId)->exists()) {
            abort(422, 'Patient does not belong to your organization.');
        }
    }
}
