<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ClaimDenialResource;
use App\Models\ClaimDenial;
use App\Services\Billing\ClaimDenialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DenialController extends ApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = ClaimDenial::forOrganization($this->organizationId())
            ->with(['claim.patient', 'claim.claimLines'])
            ->orderByDesc('created_at');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return ClaimDenialResource::collection($query->paginate($request->integer('per_page', 15)));
    }

    public function appeal(ClaimDenial $denial, Request $request, ClaimDenialService $service): JsonResponse
    {
        $this->ensureBelongsToOrganization($denial);

        $request->validate([
            'notes' => ['required', 'string', 'max:8000'],
            'template_type' => ['nullable', 'string', 'max:64'],
        ]);

        try {
            $denial = $service->appeal($denial, $request->notes, $request->template_type);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Appeal submitted. Denial marked Appealed; claim re-scrubbed and resubmitted to clearinghouse.',
            'denial' => new ClaimDenialResource($denial->load(['claim.patient', 'claim.claimLines'])),
        ]);
    }

    private function ensureBelongsToOrganization(ClaimDenial $denial): void
    {
        if (! $this->belongsToOrganization($denial)) {
            abort(404);
        }
    }
}
