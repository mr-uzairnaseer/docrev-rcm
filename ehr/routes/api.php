<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EncounterChargeController;
use App\Http\Controllers\Api\EncounterController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\HieController;
use App\Http\Controllers\Api\IntegrationController;
use App\Http\Controllers\Api\LabOrderController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\PatientFormController;
use App\Http\Controllers\Api\PharmacyController;
use App\Http\Controllers\Api\PrescriptionController;
use App\Http\Controllers\Api\ProviderController;
use App\Http\Controllers\Api\SurescriptsEnrollmentController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware(['internal'])->group(function () {
    Route::get('/internal/patients/{uuid}/prescriptions', [App\Http\Controllers\Api\Internal\PatientDataController::class, 'prescriptions']);
    Route::post('/internal/appointments/request', [App\Http\Controllers\Api\Internal\AppointmentSyncController::class, 'requestAppointment']);
    Route::get('/internal/providers', [App\Http\Controllers\Api\Internal\AppointmentSyncController::class, 'providers']);
    Route::get('/internal/patients/{uuid}/forms', [App\Http\Controllers\Api\Internal\PatientFormDataController::class, 'getPatientForms']);
    Route::post('/internal/forms/{uuid}/sign', [App\Http\Controllers\Api\Internal\PatientFormDataController::class, 'signForm']);
});

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::apiResource('patients', PatientController::class);
    Route::get('patients/{patient}/forms', [PatientFormController::class, 'index']);
    Route::post('patients/{patient}/forms', [PatientFormController::class, 'store']);
    Route::apiResource('providers', ProviderController::class)->only(['index', 'store', 'show']);
    Route::apiResource('locations', LocationController::class)->only(['index', 'store', 'show']);
    Route::apiResource('encounters', EncounterController::class);
    Route::post('encounters/{encounter}/sign', [EncounterController::class, 'sign']);
    Route::post('encounters/{encounter}/charges', [EncounterChargeController::class, 'store']);
    Route::post('encounters/{encounter}/diagnoses', [EncounterChargeController::class, 'storeDiagnosis']);

    Route::get('appointments', [AppointmentController::class, 'index']);
    Route::post('appointments', [AppointmentController::class, 'store']);
    Route::get('appointments/{appointment}', [AppointmentController::class, 'show']);
    Route::post('appointments/{appointment}/check-in', [AppointmentController::class, 'checkIn']);
    Route::post('appointments/{appointment}/cancel', [AppointmentController::class, 'cancel']);
    Route::post('appointments/{appointment}/approve', [AppointmentController::class, 'approve']);
    Route::post('appointments/{appointment}/decline', [AppointmentController::class, 'decline']);

    Route::get('integration/requirements', [IntegrationController::class, 'requirements']);
    Route::post('integration/test-surescripts', [IntegrationController::class, 'testSurescripts']);
    Route::post('integration/test-lab', [IntegrationController::class, 'testLab']);

    Route::get('pharmacies', [PharmacyController::class, 'index']);
    Route::get('prescriptions', [PrescriptionController::class, 'index']);
    Route::post('prescriptions', [PrescriptionController::class, 'store']);
    Route::post('prescriptions/{prescription}/send', [PrescriptionController::class, 'send']);

    Route::get('surescripts-enrollments', [SurescriptsEnrollmentController::class, 'index']);
    Route::post('surescripts-enrollments', [SurescriptsEnrollmentController::class, 'store']);
    Route::post('surescripts-enrollments/{surescriptsEnrollment}/activate', [SurescriptsEnrollmentController::class, 'activate']);

    Route::get('lab-vendors', [LabOrderController::class, 'vendors']);
    Route::get('lab-orders', [LabOrderController::class, 'index']);
    Route::post('lab-orders', [LabOrderController::class, 'store']);
    Route::post('lab-orders/{labOrder}/send', [LabOrderController::class, 'send']);
    Route::post('lab-orders/{labOrder}/simulate-results', [LabOrderController::class, 'simulateResults']);

    Route::get('hie/connections', [HieController::class, 'connections']);
    Route::post('hie/connections', [HieController::class, 'storeConnection']);
    Route::post('hie/connections/{hieConnection}/activate', [HieController::class, 'activateConnection']);
    Route::post('hie/connections/{hieConnection}/test', [HieController::class, 'testConnection']);
    Route::get('hie/exchanges', [HieController::class, 'exchanges']);
    Route::post('hie/connections/{hieConnection}/patients/{patient}/query', [HieController::class, 'queryPatient']);
    Route::post('hie/connections/{hieConnection}/patients/{patient}/push-summary', [HieController::class, 'pushSummary']);
});
