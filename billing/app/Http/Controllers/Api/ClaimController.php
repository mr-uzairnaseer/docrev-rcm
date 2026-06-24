<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\BuildClaimRequest;
use App\Http\Resources\ClaimResource;
use App\Models\Charge;
use App\Models\Claim;
use App\Models\ClaimLine;
use App\Models\Payer;
use App\Models\Patient;
use App\Services\Billing\ClaimExportService;
use App\Services\Billing\ClaimFormBuilder;
use App\Services\Billing\ClaimScrubber;
use App\Services\Billing\ClaimSubmissionService;
use App\Services\Billing\CorrectedClaimService;
use App\Services\Billing\Edi837Builder;
use App\Services\Billing\EligibilityCheckService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClaimController extends ApiController
{
    public function export(Request $request, ClaimExportService $exportService): StreamedResponse
    {
        return $exportService->export($this->organizationId(), $request);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Claim::forOrganization($this->organizationId())
            ->with(['patient', 'claimLines'])
            ->orderByDesc('created_at');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return ClaimResource::collection($query->paginate($request->integer('per_page', 15)));
    }

    public function store(BuildClaimRequest $request): JsonResponse
    {
        $orgId = $this->organizationId();
        $this->ensurePatientInOrganization($request->patient_id);
        $this->ensurePayerInOrganization($request->payer_id);

        $charges = Charge::forOrganization($orgId)
            ->where('patient_id', $request->patient_id)
            ->whereIn('id', $request->charge_ids)
            ->whereIn('status', [Charge::STATUS_PENDING, Charge::STATUS_READY])
            ->get();

        if ($charges->count() !== count($request->charge_ids)) {
            return response()->json(['message' => 'One or more charges are invalid or already billed.'], 422);
        }

        $claim = DB::transaction(function () use ($request, $orgId, $charges) {
            $serviceDates = $charges->pluck('service_date')->sort();
            $total = $charges->sum('charge_amount');

            $claim = Claim::create([
                'organization_id' => $orgId,
                'patient_id' => $request->patient_id,
                'payer_id' => $request->payer_id,
                'claim_number' => 'CLM-'.strtoupper(Str::random(8)),
                'service_date_from' => $serviceDates->first(),
                'service_date_to' => $serviceDates->last(),
                'total_charge_amount' => $total,
                'status' => Claim::STATUS_DRAFT,
                'icd10_codes' => $request->icd10_codes,
                'rendering_provider_npi' => $request->rendering_provider_npi,
                'billing_provider_npi' => $request->billing_provider_npi,
                'place_of_service' => $request->place_of_service ?? '11',
            ]);

            foreach ($charges->values() as $index => $charge) {
                ClaimLine::create([
                    'claim_id' => $claim->id,
                    'charge_id' => $charge->id,
                    'line_number' => $index + 1,
                    'cpt_code' => $charge->cpt_code,
                    'modifier_1' => $charge->modifier_1,
                    'modifier_2' => $charge->modifier_2,
                    'units' => $charge->units,
                    'charge_amount' => $charge->charge_amount,
                    'diagnosis_pointers' => $charge->diagnosis_pointers,
                ]);

                $charge->update(['status' => Charge::STATUS_BILLED]);
            }

            return $claim;
        });

        return (new ClaimResource($claim->load(['patient', 'claimLines'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Claim $claim): ClaimResource
    {
        $this->ensureBelongsToOrganization($claim);

        return new ClaimResource($claim->load(['patient', 'claimLines']));
    }

    public function markReady(Claim $claim, ClaimScrubber $scrubber, Edi837Builder $ediBuilder, EligibilityCheckService $eligibilityService): ClaimResource|JsonResponse
    {
        $this->ensureBelongsToOrganization($claim);

        if ($claim->status !== Claim::STATUS_DRAFT) {
            return response()->json(['message' => 'Only draft claims can be marked ready.'], 422);
        }

        $errors = $scrubber->scrub($claim);

        if ($errors !== []) {
            $claim->update(['scrub_errors' => $errors]);

            return response()->json([
                'message' => 'Claim failed scrubbing validation.',
                'errors' => $errors,
            ], 422);
        }

        $ediContent = $ediBuilder->build($claim);

        $claim->update([
            'status' => Claim::STATUS_READY,
            'edi_837_content' => $ediContent,
            'edi_generated_at' => now(),
            'scrub_errors' => null,
        ]);

        $fresh = $claim->fresh()->load(['patient', 'claimLines']);
        $warning = null;
        if (! $eligibilityService->latestForPatient($this->organizationId(), $claim->patient_id, $claim->payer_id)) {
            $warning = 'No active eligibility on file for this patient/payer. Run an eligibility check before submission.';
        }

        return (new ClaimResource($fresh))->additional(['warning' => $warning]);
    }

    public function edi(Claim $claim): JsonResponse
    {
        $this->ensureBelongsToOrganization($claim);

        if (! $claim->edi_837_content) {
            return response()->json(['message' => 'EDI 837 has not been generated for this claim.'], 404);
        }

        return response()->json([
            'claim_number' => $claim->claim_number,
            'generated_at' => $claim->edi_generated_at?->toIso8601String(),
            'edi_837' => $claim->edi_837_content,
        ]);
    }

    public function form(Claim $claim, string $form, ClaimFormBuilder $builder): JsonResponse
    {
        $this->ensureBelongsToOrganization($claim);

        try {
            $data = $builder->buildForClaim($claim->load(['patient.organization', 'payer', 'claimLines.charge']), $form);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $data]);
    }

    public function submit(Claim $claim, ClaimSubmissionService $submissionService): JsonResponse
    {
        $this->ensureBelongsToOrganization($claim);

        try {
            $submission = $submissionService->submit($claim->fresh()->load(['patient', 'claimLines']));
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Claim submitted to clearinghouse.',
            'submission' => [
                'id' => $submission->id,
                'status' => $submission->status,
                'external_reference' => $submission->external_reference,
                'response_message' => $submission->response_message,
            ],
            'claim' => new ClaimResource($claim->fresh()->load(['patient', 'claimLines'])),
        ]);
    }

    public function correct(Claim $claim, CorrectedClaimService $service): JsonResponse
    {
        $this->ensureBelongsToOrganization($claim);

        try {
            $corrected = $service->createCorrectedClaim($claim->load(['claimLines']));
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Corrected claim created as draft (frequency code 7).',
            'original_claim_id' => $claim->id,
            'claim' => new ClaimResource($corrected),
        ], 201);
    }

    public function correctAndResubmit(
        Claim $claim,
        CorrectedClaimService $service,
        ClaimScrubber $scrubber,
        Edi837Builder $ediBuilder,
        ClaimSubmissionService $submissionService
    ): JsonResponse {
        $this->ensureBelongsToOrganization($claim);

        try {
            $result = $service->correctAndResubmit(
                $claim->load(['claimLines']),
                $scrubber,
                $ediBuilder,
                $submissionService
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Corrected claim scrubbed and resubmitted to clearinghouse.',
            'original_claim_id' => $claim->id,
            'submission' => [
                'id' => $result['submission']->id,
                'status' => $result['submission']->status,
                'external_reference' => $result['submission']->external_reference,
                'response_message' => $result['submission']->response_message,
            ],
            'claim' => new ClaimResource($result['corrected_claim']),
        ]);
    }

    private function ensureBelongsToOrganization(Claim $claim): void
    {
        if (! $this->belongsToOrganization($claim)) {
            abort(404);
        }
    }

    private function ensurePatientInOrganization(int $patientId): void
    {
        if (! Patient::forOrganization($this->organizationId())->where('id', $patientId)->exists()) {
            abort(422, 'Patient does not belong to your organization.');
        }
    }

    private function ensurePayerInOrganization(int $payerId): void
    {
        if (! Payer::forOrganization($this->organizationId())->where('id', $payerId)->exists()) {
            abort(422, 'Payer does not belong to your organization.');
        }
    }
}
