<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\CheckEligibilityRequest;
use App\Http\Resources\EligibilityInquiryResource;
use App\Models\EligibilityInquiry;
use App\Models\Patient;
use App\Models\Payer;
use App\Services\Billing\EligibilityCheckService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EligibilityController extends ApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = EligibilityInquiry::forOrganization($this->organizationId())
            ->with(['patient', 'payer'])
            ->orderByDesc('checked_at');

        if ($patientId = $request->query('patient_id')) {
            $query->where('patient_id', $patientId);
        }

        if ($status = $request->query('coverage_status')) {
            $query->where('coverage_status', $status);
        }

        return EligibilityInquiryResource::collection($query->paginate($request->integer('per_page', 15)));
    }

    public function show(EligibilityInquiry $eligibility): EligibilityInquiryResource
    {
        $this->ensureBelongsToOrganization($eligibility);

        return new EligibilityInquiryResource($eligibility->load(['patient', 'payer']));
    }

    public function check(CheckEligibilityRequest $request, EligibilityCheckService $checkService): JsonResponse
    {
        $orgId = $this->organizationId();
        $patient = $this->findPatient($request->patient_id, $orgId);
        $payer = $this->findPayer($request->payer_id, $orgId);

        $inquiry = $checkService->check(
            $orgId,
            $patient,
            $payer,
            $request->service_date,
            $request->member_id,
        );

        return response()->json([
            'message' => 'Eligibility check completed.',
            'inquiry' => new EligibilityInquiryResource($inquiry->load(['patient', 'payer'])),
        ], 201);
    }

    public function edi(EligibilityInquiry $eligibility): JsonResponse
    {
        $this->ensureBelongsToOrganization($eligibility);

        return response()->json([
            'trace_number' => $eligibility->trace_number,
            'edi_270' => $eligibility->edi_270_content,
            'edi_271' => $eligibility->edi_271_content,
        ]);
    }

    private function ensureBelongsToOrganization(EligibilityInquiry $inquiry): void
    {
        if (! $this->belongsToOrganization($inquiry)) {
            abort(404);
        }
    }

    private function findPatient(int $patientId, int $orgId): Patient
    {
        $patient = Patient::forOrganization($orgId)->find($patientId);
        if (! $patient) {
            abort(422, 'Patient does not belong to your organization.');
        }

        return $patient;
    }

    private function findPayer(int $payerId, int $orgId): Payer
    {
        $payer = Payer::forOrganization($orgId)->find($payerId);
        if (! $payer) {
            abort(422, 'Payer does not belong to your organization.');
        }

        return $payer;
    }
}
