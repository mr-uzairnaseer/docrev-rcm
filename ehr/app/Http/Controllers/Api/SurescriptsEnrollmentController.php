<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\SurescriptsEnrollmentResource;
use App\Models\Provider;
use App\Models\SurescriptsEnrollment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SurescriptsEnrollmentController extends ApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return SurescriptsEnrollmentResource::collection(
            SurescriptsEnrollment::forOrganization($this->organizationId())
                ->with('provider')
                ->orderByDesc('created_at')
                ->paginate($request->integer('per_page', 15))
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider_id' => ['required', 'integer', 'exists:providers,id'],
            'dea_number' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $orgId = $this->organizationId();
        if (! Provider::forOrganization($orgId)->where('id', $validated['provider_id'])->exists()) {
            abort(422, 'Provider not in organization.');
        }

        $enrollment = SurescriptsEnrollment::updateOrCreate(
            ['organization_id' => $orgId, 'provider_id' => $validated['provider_id']],
            [
                'dea_number' => $validated['dea_number'] ?? null,
                'status' => SurescriptsEnrollment::STATUS_SUBMITTED,
                'notes' => $validated['notes'] ?? 'Enrollment submitted via DocRev EHR.',
            ]
        );

        return (new SurescriptsEnrollmentResource($enrollment->load('provider')))
            ->response()->setStatusCode(201);
    }

    public function activate(SurescriptsEnrollment $surescriptsEnrollment): SurescriptsEnrollmentResource
    {
        if (! $this->belongsToOrganization($surescriptsEnrollment)) {
            abort(404);
        }

        $surescriptsEnrollment->update([
            'status' => SurescriptsEnrollment::STATUS_ACTIVE,
            'spi' => $surescriptsEnrollment->spi ?? 'SPI-DEMO-'.str_pad((string) $surescriptsEnrollment->provider_id, 6, '0', STR_PAD_LEFT),
            'enrolled_at' => now(),
        ]);

        return new SurescriptsEnrollmentResource($surescriptsEnrollment->fresh()->load('provider'));
    }
}
