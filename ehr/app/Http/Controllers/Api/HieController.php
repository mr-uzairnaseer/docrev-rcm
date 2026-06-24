<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\HieConnectionResource;
use App\Http\Resources\HieExchangeResource;
use App\Models\HieConnection;
use App\Models\HieExchange;
use App\Models\Patient;
use App\Services\Integrations\Hie\FhirClientInterface;
use App\Services\Integrations\Hie\HieExchangeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class HieController extends ApiController
{
    public function connections(Request $request): AnonymousResourceCollection
    {
        return HieConnectionResource::collection(
            HieConnection::forOrganization($this->organizationId())
                ->orderBy('name')
                ->paginate($request->integer('per_page', 15))
        );
    }

    public function storeConnection(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'network_type' => ['nullable', 'string', 'max:30'],
            'fhir_base_url' => ['nullable', 'url', 'max:500'],
            'client_id' => ['nullable', 'string', 'max:255'],
            'scopes' => ['nullable', 'string', 'max:500'],
            'agreement_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $connection = HieConnection::create(array_merge($validated, [
            'organization_id' => $this->organizationId(),
            'status' => HieConnection::STATUS_PENDING,
        ]));

        return (new HieConnectionResource($connection))
            ->response()->setStatusCode(201);
    }

    public function activateConnection(HieConnection $hieConnection): HieConnectionResource
    {
        if (! $this->belongsToOrganization($hieConnection)) {
            abort(404);
        }

        $hieConnection->update([
            'status' => HieConnection::STATUS_ACTIVE,
            'agreement_signed_at' => now(),
        ]);

        return new HieConnectionResource($hieConnection->fresh());
    }

    public function exchanges(Request $request): AnonymousResourceCollection
    {
        return HieExchangeResource::collection(
            HieExchange::forOrganization($this->organizationId())
                ->with(['patient', 'hieConnection'])
                ->orderByDesc('created_at')
                ->paginate($request->integer('per_page', 15))
        );
    }

    public function queryPatient(HieConnection $hieConnection, Patient $patient, HieExchangeService $service): JsonResponse
    {
        if (! $this->belongsToOrganization($hieConnection) || ! $this->belongsToOrganization($patient)) {
            abort(404);
        }

        if ($hieConnection->status !== HieConnection::STATUS_ACTIVE) {
            return response()->json(['message' => 'HIE connection must be active. Sign agreement first.'], 422);
        }

        $exchange = $service->queryPatient($hieConnection, $patient);

        return response()->json([
            'message' => $exchange->response_message,
            'exchange' => new HieExchangeResource($exchange->load(['patient', 'hieConnection'])),
        ]);
    }

    public function pushSummary(HieConnection $hieConnection, Patient $patient, HieExchangeService $service): JsonResponse
    {
        if (! $this->belongsToOrganization($hieConnection) || ! $this->belongsToOrganization($patient)) {
            abort(404);
        }

        if ($hieConnection->status !== HieConnection::STATUS_ACTIVE) {
            return response()->json(['message' => 'HIE connection must be active.'], 422);
        }

        $exchange = $service->pushSummary($hieConnection, $patient);

        return response()->json([
            'message' => $exchange->response_message,
            'exchange' => new HieExchangeResource($exchange->load(['patient', 'hieConnection'])),
        ]);
    }

    public function testConnection(HieConnection $hieConnection, FhirClientInterface $client): JsonResponse
    {
        if (! $this->belongsToOrganization($hieConnection)) {
            abort(404);
        }

        $result = $client->testConnection($hieConnection);

        return response()->json($result, $result['success'] ? 200 : 422);
    }
}
