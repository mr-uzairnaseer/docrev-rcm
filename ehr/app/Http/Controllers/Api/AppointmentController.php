<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\Location;
use App\Models\Patient;
use App\Models\Provider;
use App\Services\AppointmentCheckInService;
use App\Services\CrossAppSyncService;
use App\Services\PortalAppointmentSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AppointmentController extends ApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Appointment::forOrganization($this->organizationId())
            ->with(['patient', 'provider', 'location'])
            ->orderBy('scheduled_at');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($from = $request->query('from')) {
            $query->where('scheduled_at', '>=', $from);
        }

        return AppointmentResource::collection($query->paginate($request->integer('per_page', 15)));
    }

    public function store(StoreAppointmentRequest $request, CrossAppSyncService $crossAppSync, PortalAppointmentSyncService $syncService): JsonResponse
    {
        $orgId = $this->organizationId();
        $this->ensurePatientInOrg($request->patient_id, $orgId);
        $this->ensureProviderInOrg($request->provider_id, $orgId);
        if ($request->location_id) {
            $this->ensureLocationInOrg($request->location_id, $orgId);
        }

        $appointment = Appointment::create(array_merge(
            $request->validated(),
            [
                'organization_id' => $orgId,
                'status' => Appointment::STATUS_SCHEDULED,
            ]
        ));

        $appointment->load(['patient', 'provider', 'location', 'organization']);
        $crossAppSync->provisionPatient($appointment->patient);
        $syncService->sync($appointment->fresh()->load(['patient', 'provider', 'location', 'organization']));

        return (new AppointmentResource($appointment->fresh()->load(['patient', 'provider', 'location'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Appointment $appointment): AppointmentResource
    {
        $this->ensureBelongsToOrganization($appointment);

        return new AppointmentResource($appointment->load(['patient', 'provider', 'location']));
    }

    public function update(Request $request, Appointment $appointment, PortalAppointmentSyncService $syncService): AppointmentResource
    {
        $this->ensureBelongsToOrganization($appointment);

        $data = $request->validate([
            'provider_id' => ['sometimes', 'required', 'integer', 'exists:providers,id'],
            'location_id' => ['sometimes', 'nullable', 'integer', 'exists:locations,id'],
            'scheduled_at' => ['sometimes', 'required', 'date'],
            'duration_minutes' => ['sometimes', 'nullable', 'integer', 'min:5', 'max:480'],
            'appointment_type' => ['sometimes', 'nullable', 'string', 'max:50'],
            'status' => ['sometimes', 'required', 'string', 'in:scheduled,checked_in,completed,cancelled,no_show'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ]);

        $appointment->update($data);
        
        $appointment->load(['patient', 'provider', 'location', 'organization']);
        $syncService->sync($appointment);

        return new AppointmentResource($appointment->fresh()->load(['patient', 'provider', 'location']));
    }

    public function checkIn(Appointment $appointment, AppointmentCheckInService $checkInService): AppointmentResource
    {
        $this->ensureBelongsToOrganization($appointment);

        $appointment = $checkInService->checkIn($appointment);

        return new AppointmentResource($appointment);
    }

    public function cancel(Appointment $appointment, PortalAppointmentSyncService $syncService): AppointmentResource
    {
        $this->ensureBelongsToOrganization($appointment);

        $appointment->update(['status' => Appointment::STATUS_CANCELLED]);
        $syncService->sync($appointment->fresh()->load(['patient', 'provider', 'location', 'organization']));

        return new AppointmentResource($appointment->fresh()->load(['patient', 'provider', 'location']));
    }

    public function approve(Appointment $appointment, PortalAppointmentSyncService $syncService): AppointmentResource
    {
        $this->ensureBelongsToOrganization($appointment);

        $appointment->update(['status' => Appointment::STATUS_SCHEDULED]);
        $syncService->sync($appointment->fresh()->load(['patient', 'provider', 'location', 'organization']));

        return new AppointmentResource($appointment->fresh()->load(['patient', 'provider', 'location']));
    }

    public function decline(Appointment $appointment, PortalAppointmentSyncService $syncService): AppointmentResource
    {
        $this->ensureBelongsToOrganization($appointment);

        $appointment->update(['status' => Appointment::STATUS_CANCELLED]);
        $syncService->sync($appointment->fresh()->load(['patient', 'provider', 'location', 'organization']));

        return new AppointmentResource($appointment->fresh()->load(['patient', 'provider', 'location']));
    }

    private function ensureBelongsToOrganization(Appointment $appointment): void
    {
        if (! $this->belongsToOrganization($appointment)) {
            abort(404);
        }
    }

    private function ensurePatientInOrg(int $id, int $orgId): void
    {
        if (! Patient::forOrganization($orgId)->where('id', $id)->exists()) {
            abort(422, 'Patient not in organization.');
        }
    }

    private function ensureProviderInOrg(int $id, int $orgId): void
    {
        if (! Provider::forOrganization($orgId)->where('id', $id)->exists()) {
            abort(422, 'Provider not in organization.');
        }
    }

    private function ensureLocationInOrg(int $id, int $orgId): void
    {
        if (! Location::forOrganization($orgId)->where('id', $id)->exists()) {
            abort(422, 'Location not in organization.');
        }
    }
}
