<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\Internal\AppointmentSyncController;
use App\Http\Controllers\Api\Internal\PatientProvisionController;
use App\Http\Controllers\Api\Internal\StatementSyncController;
use App\Http\Controllers\Api\PatientAuthController;
use App\Http\Controllers\Api\PatientPaymentController;
use App\Models\PatientAccount;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::post('/internal/statement-sync', StatementSyncController::class)
    ->middleware('internal');

Route::post('/internal/patient-provision', PatientProvisionController::class)
    ->middleware('internal');

Route::post('/internal/appointment-sync', AppointmentSyncController::class)
    ->middleware('internal');

Route::post('/internal/form-sync', \App\Http\Controllers\Api\Internal\PatientFormSyncController::class)
    ->middleware('internal');

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/patient/login', [PatientAuthController::class, 'login']);

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/patient/me', [PatientAuthController::class, 'me']);
    Route::post('/patient/logout', [PatientAuthController::class, 'logout']);
    Route::get('/patient/appointments', [PatientAuthController::class, 'appointments']);
    Route::get('/patient/statements', [PatientAuthController::class, 'statements']);
    Route::post('/patient/pay', [PatientPaymentController::class, 'pay']);
    Route::get('/patient/medications', [PatientAuthController::class, 'medications']);
    Route::post('/patient/appointments/request', [PatientAuthController::class, 'requestAppointment']);
    Route::get('/patient/providers', [PatientAuthController::class, 'providers']);
    Route::get('/patient/forms', [\App\Http\Controllers\Api\PatientFormController::class, 'index']);
    Route::post('/patient/forms/{uuid}/sign', [\App\Http\Controllers\Api\PatientFormController::class, 'sign']);
});
