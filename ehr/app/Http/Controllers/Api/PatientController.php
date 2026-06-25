<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Http\Resources\PatientResource;
use App\Models\Patient;
use App\Services\CrossAppSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PatientController extends ApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Patient::forOrganization($this->organizationId())
            ->orderBy('last_name')
            ->orderBy('first_name');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('mrn', 'like', "%{$search}%");
            });
        }

        return PatientResource::collection(
            $query->paginate($request->integer('per_page', 15))
        );
    }

    public function store(StorePatientRequest $request, CrossAppSyncService $syncService): JsonResponse
    {
        $firstName = $request->input('first_name');
        $lastName = $request->input('last_name');
        $dob = $request->input('date_of_birth');

        $exists = Patient::forOrganization($this->organizationId())
            ->where('first_name', 'like', $firstName)
            ->where('last_name', 'like', $lastName)
            ->whereDate('date_of_birth', $dob)
            ->exists();

        if ($exists && ! $request->boolean('ignore_duplicate')) {
            return response()->json([
                'duplicate' => true,
                'message' => "Warning: A patient named '$firstName $lastName' with Date of Birth '$dob' already exists.",
            ], 409);
        }

        $patient = Patient::create(array_merge(
            $request->validated(),
            ['organization_id' => $this->organizationId()]
        ));

        $syncService->provisionPatient($patient->fresh()->load('organization'));

        return (new PatientResource($patient->fresh()))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Patient $patient): PatientResource
    {
        $this->ensureBelongsToOrganization($patient);

        return new PatientResource($patient);
    }

    public function update(UpdatePatientRequest $request, Patient $patient): PatientResource
    {
        $this->ensureBelongsToOrganization($patient);
        $patient->update($request->validated());

        return new PatientResource($patient->fresh());
    }

    public function destroy(Patient $patient): JsonResponse
    {
        $this->ensureBelongsToOrganization($patient);
        $patient->update(['is_active' => false]);

        return response()->json(['message' => 'Patient deactivated.']);
    }

    private function ensureBelongsToOrganization(Patient $patient): void
    {
        if (! $this->belongsToOrganization($patient)) {
            abort(404);
        }
    }
}
