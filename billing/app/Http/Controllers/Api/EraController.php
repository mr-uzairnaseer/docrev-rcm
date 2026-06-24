<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\ImportEraRequest;
use App\Http\Resources\EraRemittanceResource;
use App\Models\Claim;
use App\Models\EraRemittance;
use App\Services\Billing\Edi835Builder;
use App\Services\Billing\EraPostingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EraController extends ApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = EraRemittance::forOrganization($this->organizationId())
            ->with('claimPayments')
            ->orderByDesc('posted_at');

        return EraRemittanceResource::collection($query->paginate($request->integer('per_page', 15)));
    }

    public function show(EraRemittance $era): EraRemittanceResource
    {
        $this->ensureBelongsToOrganization($era);

        return new EraRemittanceResource($era->load('claimPayments'));
    }

    public function import(ImportEraRequest $request, EraPostingService $postingService): JsonResponse
    {
        $remittance = $postingService->post(
            $this->organizationId(),
            $request->edi_835,
            $request->trace_number,
        );

        return response()->json([
            'message' => 'ERA posted successfully.',
            'remittance' => new EraRemittanceResource($remittance),
        ], 201);
    }

    public function simulate(Claim $claim, Edi835Builder $builder, EraPostingService $postingService): JsonResponse
    {
        $this->ensureClaimInOrganization($claim);

        if (! in_array($claim->status, [Claim::STATUS_SUBMITTED, Claim::STATUS_ACCEPTED], true)) {
            return response()->json(['message' => 'Only submitted claims can receive a simulated ERA.'], 422);
        }

        $edi835 = $builder->buildForClaims([$claim]);
        $remittance = $postingService->post($this->organizationId(), $edi835, 'SIM-'.$claim->claim_number);

        return response()->json([
            'message' => 'Simulated ERA posted for claim '.$claim->claim_number.'.',
            'edi_835' => $edi835,
            'remittance' => new EraRemittanceResource($remittance),
            'claim' => new \App\Http\Resources\ClaimResource($claim->fresh()->load(['patient', 'claimLines'])),
        ]);
    }

    public function simulateDenial(Claim $claim, Edi835Builder $builder, EraPostingService $postingService): JsonResponse
    {
        $this->ensureClaimInOrganization($claim);

        if (! in_array($claim->status, [Claim::STATUS_SUBMITTED, Claim::STATUS_ACCEPTED], true)) {
            return response()->json(['message' => 'Only submitted claims can receive a simulated denial ERA.'], 422);
        }

        $edi835 = $builder->buildForClaims([$claim], asDenial: true);
        $remittance = $postingService->post($this->organizationId(), $edi835, 'DENY-'.$claim->claim_number);

        return response()->json([
            'message' => 'Simulated denial ERA posted for claim '.$claim->claim_number.'.',
            'edi_835' => $edi835,
            'remittance' => new EraRemittanceResource($remittance),
            'claim' => new \App\Http\Resources\ClaimResource($claim->fresh()->load(['patient', 'claimLines'])),
        ]);
    }

    private function ensureBelongsToOrganization(EraRemittance $era): void
    {
        if (! $this->belongsToOrganization($era)) {
            abort(404);
        }
    }

    private function ensureClaimInOrganization(Claim $claim): void
    {
        if (! $this->belongsToOrganization($claim)) {
            abort(404);
        }
    }
}
