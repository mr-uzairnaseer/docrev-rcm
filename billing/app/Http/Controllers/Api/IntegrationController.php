<?php

namespace App\Http\Controllers\Api;

use App\Services\Billing\ClearinghouseConnectionService;
use App\Services\Billing\EligibilityConnectionService;
use App\Services\Integration\OrganizationOnboardingService;
use App\Support\AppFeatures;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntegrationController extends ApiController
{
    public function requirements(OrganizationOnboardingService $onboarding): JsonResponse
    {
        return response()->json($onboarding->requirementsForOrganization($this->organizationId()));
    }

    public function guide(OrganizationOnboardingService $onboarding): JsonResponse
    {
        return response()->json($onboarding->guide());
    }

    public function organizationProfile(OrganizationOnboardingService $onboarding): JsonResponse
    {
        return response()->json([
            'data' => $onboarding->organizationProfile($this->organizationId()),
        ]);
    }

    public function lookupNpi(Request $request, OrganizationOnboardingService $onboarding): JsonResponse
    {
        $request->validate(['npi' => ['required', 'string', 'size:10']]);

        try {
            $result = $onboarding->lookupNpi($request->string('npi'));
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        if (! $result) {
            return response()->json(['message' => 'NPI not found in NPPES registry.'], 404);
        }

        return response()->json(['data' => $result]);
    }

    public function applyNppes(Request $request, OrganizationOnboardingService $onboarding): JsonResponse
    {
        $request->validate([
            'npi' => ['required', 'string', 'size:10'],
            'apply_as' => ['nullable', 'in:organization,rendering_provider'],
        ]);

        try {
            $result = $onboarding->applyFromNppes(
                $this->organizationId(),
                $request->string('npi'),
                $request->input('apply_as')
            );
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($result);
    }

    public function discoverNpiProviders(OrganizationOnboardingService $onboarding): JsonResponse
    {
        return response()->json([
            'data' => $onboarding->discoverRenderingProviders($this->organizationId()),
        ]);
    }

    public function applyNpiProviders(Request $request, OrganizationOnboardingService $onboarding): JsonResponse
    {
        $request->validate([
            'npis' => ['required', 'array', 'min:1'],
            'npis.*' => ['string', 'size:10'],
        ]);

        return response()->json($onboarding->applyRenderingProviders(
            $this->organizationId(),
            $request->input('npis')
        ));
    }

    public function updateOrganizationTaxId(Request $request, OrganizationOnboardingService $onboarding): JsonResponse
    {
        $request->validate(['tax_id' => ['required', 'string', 'max:20']]);

        return response()->json($onboarding->updateOrganizationTaxId(
            $this->organizationId(),
            $request->string('tax_id')
        ));
    }

    public function syncPayers(OrganizationOnboardingService $onboarding): JsonResponse
    {
        return response()->json($onboarding->syncPayerElectronicIds($this->organizationId()));
    }

    public function payerDirectory(Request $request, OrganizationOnboardingService $onboarding): JsonResponse
    {
        return response()->json([
            'data' => $onboarding->payerDirectory($request->integer('limit', 100)),
        ]);
    }

    public function placeOfService(OrganizationOnboardingService $onboarding): JsonResponse
    {
        return response()->json([
            'data' => $onboarding->placeOfServiceCodes(),
        ]);
    }

    public function features(): JsonResponse
    {
        return response()->json([
            'modules' => AppFeatures::modules(),
            'workspace_options' => AppFeatures::workspaceOptions(),
            'drivers' => AppFeatures::drivers(),
            'cms_datasets' => \App\Support\CmsReference\CmsBillingCodeCatalog::datasetKeys(),
        ]);
    }

    public function testClearinghouse(ClearinghouseConnectionService $service): JsonResponse
    {
        try {
            $result = $service->test();
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'driver' => config('clearinghouse.driver', 'stub'),
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function testEligibility(EligibilityConnectionService $service): JsonResponse
    {
        try {
            $result = $service->test();
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'driver' => config('eligibility.driver', 'stub'),
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json($result, $result['success'] ? 200 : 422);
    }
}
