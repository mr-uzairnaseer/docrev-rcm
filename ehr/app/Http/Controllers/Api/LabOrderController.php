<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\LabOrderResource;
use App\Http\Resources\LabVendorResource;
use App\Models\LabOrder;
use App\Models\LabVendor;
use App\Models\Patient;
use App\Models\Provider;
use App\Services\Integrations\Lab\LabOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LabOrderController extends ApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = LabOrder::forOrganization($this->organizationId())
            ->with(['patient', 'provider', 'labVendor', 'results'])
            ->orderByDesc('created_at');

        if ($patientId = $request->query('patient_id')) {
            $query->where('patient_id', $patientId);
        }

        return LabOrderResource::collection($query->paginate($request->integer('per_page', 15)));
    }

    public function vendors(Request $request): AnonymousResourceCollection
    {
        return LabVendorResource::collection(
            LabVendor::forOrganization($this->organizationId())
                ->where('is_active', true)
                ->orderBy('name')
                ->paginate($request->integer('per_page', 50))
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'provider_id' => ['required', 'integer', 'exists:providers,id'],
            'lab_vendor_id' => ['required', 'integer', 'exists:lab_vendors,id'],
            'encounter_id' => ['nullable', 'integer', 'exists:encounters,id'],
            'test_code' => ['required', 'string', 'max:50'],
            'test_name' => ['required', 'string', 'max:255'],
            'priority' => ['nullable', 'string', 'in:routine,stat'],
        ]);

        $orgId = $this->organizationId();
        foreach ([Patient::class => 'patient_id', Provider::class => 'provider_id', LabVendor::class => 'lab_vendor_id'] as $model => $key) {
            if (! $model::forOrganization($orgId)->where('id', $validated[$key])->exists()) {
                abort(422, 'Resource not in organization.');
            }
        }

        $order = LabOrder::create(array_merge($validated, [
            'organization_id' => $orgId,
            'status' => LabOrder::STATUS_ORDERED,
            'priority' => $validated['priority'] ?? 'routine',
        ]));

        return (new LabOrderResource($order->load(['patient', 'provider', 'labVendor'])))
            ->response()->setStatusCode(201);
    }

    public function send(LabOrder $labOrder, LabOrderService $service): LabOrderResource|JsonResponse
    {
        if (! $this->belongsToOrganization($labOrder)) {
            abort(404);
        }

        try {
            $order = $service->send($labOrder);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return new LabOrderResource($order);
    }

    public function simulateResults(LabOrder $labOrder, LabOrderService $service): LabOrderResource
    {
        if (! $this->belongsToOrganization($labOrder)) {
            abort(404);
        }

        return new LabOrderResource($service->simulateResults($labOrder));
    }
}
