<?php

namespace App\Http\Controllers\Api;

use App\Models\Encounter;
use App\Services\ClaimFormBuilder;
use App\Services\ClaimFormPdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EncounterClaimFormController extends ApiController
{
    public function show(Encounter $encounter, string $form, ClaimFormBuilder $builder): JsonResponse
    {
        return response()->json(['data' => $this->buildPayload($encounter, $form, $builder)]);
    }

    public function pdf(Encounter $encounter, string $form, ClaimFormPdfService $pdfService): Response
    {
        $this->guardEncounter($encounter);

        try {
            $bytes = $pdfService->buildPdfForEncounter($encounter, $form);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            abort(422, $e->getMessage());
        }

        return $this->pdfResponse($form, $bytes);
    }

    public function pdfWithEdits(Request $request, Encounter $encounter, string $form, ClaimFormPdfService $pdfService): Response
    {
        $this->guardEncounter($encounter);

        $payload = $request->all();
        if (empty($payload['form_type'])) {
            abort(422, 'Invalid claim form payload.');
        }

        try {
            $bytes = $pdfService->renderPdf($payload);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            abort(422, $e->getMessage());
        }

        return $this->pdfResponse($form, $bytes);
    }

    private function pdfResponse(string $form, string $bytes): Response
    {
        $filename = $form === 'ub04' ? 'ub04-claim.pdf' : 'cms1500-claim.pdf';

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Cache-Control' => 'no-store',
        ]);
    }

    private function buildPayload(Encounter $encounter, string $form, ClaimFormBuilder $builder): array
    {
        $this->guardEncounter($encounter);

        try {
            return $builder->buildForEncounter($encounter, $form);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            abort(422, $e->getMessage());
        }
    }

    private function guardEncounter(Encounter $encounter): void
    {
        if (! $this->belongsToOrganization($encounter)) {
            abort(404);
        }

        if ($encounter->billing_sync_status !== 'synced') {
            abort(422, 'Encounter must be synced to billing before generating claim forms.');
        }
    }
}
