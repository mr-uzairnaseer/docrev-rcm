<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CmsReferenceController;
use App\Http\Controllers\Api\ClaimController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DenialController;
use App\Http\Controllers\Api\EraController;
use App\Http\Controllers\Api\EligibilityController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\IntegrationController;
use App\Http\Controllers\Api\Internal\EncounterFormController;
use App\Http\Controllers\Api\Internal\EncounterSyncController;
use App\Http\Controllers\Api\Internal\PatientPaymentController;
use App\Http\Controllers\Api\PayerController;
use App\Http\Controllers\Api\PatientController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::post('/internal/encounter-sync', EncounterSyncController::class)
    ->middleware(['internal', 'throttle:internal'])
    ->withoutMiddleware(['throttle:api']);

Route::get('/internal/encounter-forms/{encounterUuid}', EncounterFormController::class)
    ->middleware(['internal', 'throttle:internal'])
    ->withoutMiddleware(['throttle:api']);

Route::post('/internal/patient-payment', PatientPaymentController::class)
    ->middleware(['internal', 'throttle:internal'])
    ->withoutMiddleware(['throttle:api']);

Route::post('/internal/patient-provision', \App\Http\Controllers\Api\Internal\PatientProvisionController::class)
    ->middleware(['internal', 'throttle:internal'])
    ->withoutMiddleware(['throttle:api']);

Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/integration/requirements', [IntegrationController::class, 'requirements']);
    Route::get('/integration/guide', [IntegrationController::class, 'guide']);
    Route::get('/integration/organization', [IntegrationController::class, 'organizationProfile']);
    Route::get('/integration/npi-lookup', [IntegrationController::class, 'lookupNpi']);
    Route::post('/integration/apply-nppes', [IntegrationController::class, 'applyNppes']);
    Route::get('/integration/npi-provider-search', [IntegrationController::class, 'discoverNpiProviders']);
    Route::post('/integration/apply-nppes-providers', [IntegrationController::class, 'applyNpiProviders']);
    Route::patch('/integration/organization', [IntegrationController::class, 'updateOrganizationTaxId']);
    Route::post('/integration/sync-payers', [IntegrationController::class, 'syncPayers']);
    Route::get('/integration/payer-directory', [IntegrationController::class, 'payerDirectory']);
    Route::get('/integration/place-of-service', [IntegrationController::class, 'placeOfService']);
    Route::get('/integration/features', [IntegrationController::class, 'features']);
    Route::post('/integration/clearinghouse-test', [IntegrationController::class, 'testClearinghouse']);
    Route::post('/integration/eligibility-test', [IntegrationController::class, 'testEligibility']);
    Route::get('/dashboard', DashboardController::class);

    Route::get('/cms/summary', [CmsReferenceController::class, 'summary']);
    Route::get('/cms/states', [CmsReferenceController::class, 'states']);
    Route::get('/cms/states/{code}', [CmsReferenceController::class, 'showState']);
    Route::get('/cms/macs', [CmsReferenceController::class, 'macs']);
    Route::get('/cms/payers', [CmsReferenceController::class, 'payers']);
    Route::get('/cms/place-of-service', [CmsReferenceController::class, 'placeOfService']);
    Route::get('/cms/taxonomy', [CmsReferenceController::class, 'taxonomy']);
    Route::get('/cms/medicare-advantage', [CmsReferenceController::class, 'medicareAdvantage']);
    Route::get('/cms/hcpcs', [CmsReferenceController::class, 'hcpcs']);
    Route::get('/cms/icd10', [CmsReferenceController::class, 'icd10']);
    Route::get('/cms/modifiers', [CmsReferenceController::class, 'modifiers']);
    Route::get('/cms/claim-adjustments', [CmsReferenceController::class, 'claimAdjustments']);
    Route::get('/cms/remittance-remarks', [CmsReferenceController::class, 'remittanceRemarks']);
    Route::get('/cms/type-of-bill', [CmsReferenceController::class, 'typeOfBill']);
    Route::get('/cms/revenue-codes', [CmsReferenceController::class, 'revenueCodes']);
    Route::get('/cms/qhp-issuers', [CmsReferenceController::class, 'qhpIssuers']);
    Route::post('/cms/import', [CmsReferenceController::class, 'import']);
    Route::get('/cms/export', [CmsReferenceController::class, 'export']);

    Route::get('denials', [DenialController::class, 'index']);
    Route::post('denials/{denial}/appeal', [DenialController::class, 'appeal']);

    Route::apiResource('patients', PatientController::class)->only(['index', 'store', 'show']);
    Route::get('payers', [PayerController::class, 'index']);
    Route::get('eligibility', [EligibilityController::class, 'index']);
    Route::post('eligibility/check', [EligibilityController::class, 'check']);
    Route::get('eligibility/{eligibility}', [EligibilityController::class, 'show']);
    Route::get('eligibility/{eligibility}/edi', [EligibilityController::class, 'edi']);
    Route::apiResource('charges', ChargeController::class)->only(['index', 'store', 'show']);
    Route::post('charges/{charge}/ready', [ChargeController::class, 'markReady']);

    Route::get('claims/export', [ClaimController::class, 'export']);
    Route::post('claims/{claim}/correct', [ClaimController::class, 'correct']);
    Route::post('claims/{claim}/correct-and-resubmit', [ClaimController::class, 'correctAndResubmit']);
    Route::apiResource('claims', ClaimController::class)->only(['index', 'store', 'show']);
    Route::post('claims/{claim}/ready', [ClaimController::class, 'markReady']);
    Route::post('claims/{claim}/submit', [ClaimController::class, 'submit']);
    Route::post('claims/{claim}/simulate-era', [EraController::class, 'simulate']);
    Route::post('claims/{claim}/simulate-denial', [EraController::class, 'simulateDenial']);
    Route::get('claims/{claim}/edi', [ClaimController::class, 'edi']);
    Route::get('claims/{claim}/form/{form}', [ClaimController::class, 'form']);

    Route::get('eras', [EraController::class, 'index']);
    Route::get('patient-payments', [\App\Http\Controllers\Api\PatientPaymentController::class, 'index']);
    Route::post('eras/import', [EraController::class, 'import']);
    Route::get('eras/{era}', [EraController::class, 'show']);
});
