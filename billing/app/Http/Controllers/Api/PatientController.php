<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StorePatientRequest;
use App\Http\Resources\PatientResource;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PatientController extends ApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Patient::forOrganization($this->organizationId())->orderBy('last_name');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('mrn', 'like', "%{$search}%");
            });
        }

        return PatientResource::collection($query->paginate($request->integer('per_page', 15)));
    }

    public function store(StorePatientRequest $request): JsonResponse
    {
        $patient = Patient::create(array_merge(
            $request->validated(),
            ['organization_id' => $this->organizationId()]
        ));

        return (new PatientResource($patient))->response()->setStatusCode(201);
    }

    public function show(Patient $patient): PatientResource
    {
        $this->ensureBelongsToOrganization($patient);

        return new PatientResource($patient);
    }

    private function ensureBelongsToOrganization(Patient $patient): void
    {
        if (! $this->belongsToOrganization($patient)) {
            abort(404);
        }
    }
}
