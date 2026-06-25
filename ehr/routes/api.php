<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\EncounterChargeController;
use App\Http\Controllers\Api\EncounterClaimFormController;
use App\Http\Controllers\Api\EncounterController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\HieController;
use App\Http\Controllers\Api\IntegrationController;
use App\Http\Controllers\Api\LabOrderController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\OperationsController;
use App\Http\Controllers\Api\PatientChartController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\PatientFormController;
use App\Http\Controllers\Api\PharmacyController;
use App\Http\Controllers\Api\PrescriptionController;
use App\Http\Controllers\Api\ProjectPlanController;
use App\Http\Controllers\Api\ProviderController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SurescriptsEnrollmentController;
use App\Http\Controllers\Api\TrainingController;
use App\Http\Controllers\Api\InteroperabilityController;
use App\Http\Controllers\Api\TaskController;
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

    Route::get('patients', [PatientController::class, 'index']);
    Route::get('patients/{patient}', [PatientController::class, 'show']);
    Route::get('patients/{patient}/chart', [PatientChartController::class, 'show']);
    Route::get('patients/{patient}/forms', [PatientFormController::class, 'index']);

    Route::middleware('ability:patients.manage')->group(function () {
        Route::post('patients', [PatientController::class, 'store']);
        Route::put('patients/{patient}', [PatientController::class, 'update']);
        Route::patch('patients/{patient}', [PatientController::class, 'update']);
    });

    Route::delete('patients/{patient}', [PatientController::class, 'destroy'])
        ->middleware('ability:admin.integrations');

    Route::middleware('ability:chart.write')->group(function () {
        Route::post('patients/{patient}/problems', [PatientChartController::class, 'storeProblem']);
        Route::post('patients/{patient}/vitals', [PatientChartController::class, 'storeVital']);
        Route::post('patients/{patient}/documents', [PatientChartController::class, 'storeDocument']);
        Route::post('patients/{patient}/allergies', [PatientChartController::class, 'storeAllergy']);
        Route::post('patients/{patient}/insurances/{insurance}/check-eligibility', [PatientChartController::class, 'checkEligibility']);
        Route::post('patients/{patient}/forms', [PatientFormController::class, 'store']);
    });

    Route::get('providers', [ProviderController::class, 'index']);
    Route::get('providers/{provider}', [ProviderController::class, 'show']);
    Route::get('locations', [LocationController::class, 'index']);
    Route::get('locations/{location}', [LocationController::class, 'show']);

    Route::middleware('ability:admin.integrations')->group(function () {
        Route::post('providers', [ProviderController::class, 'store']);
        Route::post('locations', [LocationController::class, 'store']);
    });

    Route::get('encounters', [EncounterController::class, 'index']);
    Route::get('encounters/{encounter}', [EncounterController::class, 'show']);

    Route::middleware('ability:clinical.write')->group(function () {
        Route::post('encounters', [EncounterController::class, 'store']);
        Route::put('encounters/{encounter}', [EncounterController::class, 'update']);
        Route::patch('encounters/{encounter}', [EncounterController::class, 'update']);
        Route::post('encounters/{encounter}/charges', [EncounterChargeController::class, 'store']);
        Route::post('encounters/{encounter}/diagnoses', [EncounterChargeController::class, 'storeDiagnosis']);
    });

    Route::post('encounters/{encounter}/sign', [EncounterController::class, 'sign'])
        ->middleware('ability:encounters.sign');

    Route::get('encounters/{encounter}/claim-form/{form}', [EncounterClaimFormController::class, 'show'])
        ->where('form', 'hcfa|ub04');
    Route::get('encounters/{encounter}/claim-form/{form}/pdf', [EncounterClaimFormController::class, 'pdf'])
        ->where('form', 'hcfa|ub04');
    Route::post('encounters/{encounter}/claim-form/{form}/pdf', [EncounterClaimFormController::class, 'pdfWithEdits'])
        ->where('form', 'hcfa|ub04');

    Route::get('appointments', [AppointmentController::class, 'index']);
    Route::get('appointments/{appointment}', [AppointmentController::class, 'show']);

    Route::middleware('ability:appointments.manage')->group(function () {
        Route::post('appointments', [AppointmentController::class, 'store']);
        Route::put('appointments/{appointment}', [AppointmentController::class, 'update']);
        Route::post('appointments/{appointment}/check-in', [AppointmentController::class, 'checkIn']);
        Route::post('appointments/{appointment}/cancel', [AppointmentController::class, 'cancel']);
        Route::post('appointments/{appointment}/approve', [AppointmentController::class, 'approve']);
        Route::post('appointments/{appointment}/decline', [AppointmentController::class, 'decline']);
    });

    Route::get('integration/requirements', [IntegrationController::class, 'requirements']);
    Route::get('project-plan', [ProjectPlanController::class, 'show']);
    Route::get('training/modules', [TrainingController::class, 'index']);
    Route::get('operations/status', [OperationsController::class, 'status']);

    Route::middleware('ability:reports.read')->group(function () {
        Route::get('reports/dashboard', [ReportController::class, 'dashboard']);
        Route::get('reports/quality', [ReportController::class, 'quality']);
        Route::get('reports/productivity', [ReportController::class, 'productivity']);
    });

    Route::get('audit-logs', [AuditLogController::class, 'index'])
        ->middleware('ability:audit.read');

    Route::middleware('ability:admin.integrations')->group(function () {
        Route::post('integration/test-surescripts', [IntegrationController::class, 'testSurescripts']);
        Route::post('integration/test-lab', [IntegrationController::class, 'testLab']);
        Route::post('surescripts-enrollments', [SurescriptsEnrollmentController::class, 'store']);
        Route::post('surescripts-enrollments/{surescriptsEnrollment}/activate', [SurescriptsEnrollmentController::class, 'activate']);
        Route::post('hie/connections', [HieController::class, 'storeConnection']);
        Route::post('hie/connections/{hieConnection}/activate', [HieController::class, 'activateConnection']);
    });

    Route::get('pharmacies', [PharmacyController::class, 'index']);
    Route::get('prescriptions', [PrescriptionController::class, 'index']);
    Route::get('surescripts-enrollments', [SurescriptsEnrollmentController::class, 'index']);
    Route::get('lab-vendors', [LabOrderController::class, 'vendors']);
    Route::get('lab-orders', [LabOrderController::class, 'index']);
    Route::get('hie/connections', [HieController::class, 'connections']);
    Route::get('hie/exchanges', [HieController::class, 'exchanges']);

    Route::middleware('ability:prescriptions.write')->group(function () {
        Route::post('prescriptions', [PrescriptionController::class, 'store']);
        Route::post('prescriptions/{prescription}/send', [PrescriptionController::class, 'send']);
    });

    Route::middleware('ability:labs.manage')->group(function () {
        Route::post('lab-orders', [LabOrderController::class, 'store']);
        Route::post('lab-orders/{labOrder}/send', [LabOrderController::class, 'send']);
        Route::post('lab-orders/{labOrder}/simulate-results', [LabOrderController::class, 'simulateResults']);
    });

    Route::middleware('ability:hie.read')->group(function () {
        Route::post('hie/connections/{hieConnection}/test', [HieController::class, 'testConnection']);
        Route::post('hie/connections/{hieConnection}/patients/{patient}/query', [HieController::class, 'queryPatient']);
        Route::post('hie/connections/{hieConnection}/patients/{patient}/push-summary', [HieController::class, 'pushSummary']);
    });

    Route::get('interop/requests', [InteroperabilityController::class, 'getRequests']);
    Route::post('interop/requests', [InteroperabilityController::class, 'storeRequest']);
    Route::put('interop/requests/{ehiRequest}', [InteroperabilityController::class, 'updateRequest']);
    Route::get('interop/exports', [InteroperabilityController::class, 'getExports']);
    Route::post('interop/exports', [InteroperabilityController::class, 'generateExport']);
    Route::get('interop/exports/{ehiExport}/download', [InteroperabilityController::class, 'downloadExport'])
        ->name('interop.exports.download');
    Route::get('interop/fhir-patient/{patient}', [InteroperabilityController::class, 'viewFhirPatient']);

    Route::get('tasks', [TaskController::class, 'index']);
    Route::post('tasks', [TaskController::class, 'store']);
    Route::put('tasks/{task}', [TaskController::class, 'update']);
    Route::delete('tasks/{task}', [TaskController::class, 'destroy']);
});
