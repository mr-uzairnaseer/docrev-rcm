<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\PrescriptionResource;
use App\Models\Patient;
use App\Models\Pharmacy;
use App\Models\Prescription;
use App\Models\Provider;
use App\Services\Integrations\Surescripts\PrescriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PrescriptionController extends ApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Prescription::forOrganization($this->organizationId())
            ->with(['patient', 'provider', 'pharmacy'])
            ->orderByDesc('created_at');

        if ($patientId = $request->query('patient_id')) {
            $query->where('patient_id', $patientId);
        }

        return PrescriptionResource::collection($query->paginate($request->integer('per_page', 15)));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'provider_id' => ['required', 'integer', 'exists:providers,id'],
            'pharmacy_id' => ['nullable', 'integer', 'exists:pharmacies,id'],
            'encounter_id' => ['nullable', 'integer', 'exists:encounters,id'],
            'drug_name' => ['required', 'string', 'max:255'],
            'ndc' => ['nullable', 'string', 'max:20'],
            'strength' => ['nullable', 'string', 'max:50'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'days_supply' => ['nullable', 'integer', 'min:1'],
            'refills' => ['nullable', 'integer', 'min:0', 'max:11'],
            'sig' => ['required', 'string', 'max:500'],
        ]);

        $orgId = $this->organizationId();
        $this->ensureInOrg(Patient::class, $validated['patient_id'], $orgId);
        $this->ensureInOrg(Provider::class, $validated['provider_id'], $orgId);
        if (! empty($validated['pharmacy_id'])) {
            $this->ensureInOrg(Pharmacy::class, $validated['pharmacy_id'], $orgId);
        }

        $rx = Prescription::create(array_merge($validated, [
            'organization_id' => $orgId,
            'status' => Prescription::STATUS_DRAFT,
        ]));

        return (new PrescriptionResource($rx->load(['patient', 'provider', 'pharmacy'])))
            ->response()->setStatusCode(201);
    }

    public function send(Prescription $prescription, PrescriptionService $service): PrescriptionResource|JsonResponse
    {
        $this->ensureBelongsToOrganization($prescription);

        try {
            $rx = $service->send($prescription);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return new PrescriptionResource($rx);
    }

    private function ensureBelongsToOrganization(Prescription $prescription): void
    {
        if (! $this->belongsToOrganization($prescription)) {
            abort(404);
        }
    }

    private function ensureInOrg(string $model, int $id, int $orgId): void
    {
        if (! $model::forOrganization($orgId)->where('id', $id)->exists()) {
            abort(422, 'Resource not in organization.');
        }
    }
}
