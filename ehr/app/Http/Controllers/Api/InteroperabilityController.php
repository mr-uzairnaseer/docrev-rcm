<?php

namespace App\Http\Controllers\Api;

use App\Models\EhiExport;
use App\Models\EhiRequest;
use App\Models\Patient;
use App\Services\Compliance\EhiExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InteroperabilityController extends ApiController
{
    public function getRequests(Request $request): JsonResponse
    {
        $requests = EhiRequest::query()
            ->where('organization_id', $this->organizationId())
            ->with('patient')
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 50));

        return response()->json($requests);
    }

    public function storeRequest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id' => ['nullable', 'exists:patients,id'],
            'requestor_name' => ['required', 'string', 'max:255'],
            'requestor_type' => ['required', 'string', 'in:patient,provider,payer,third_party_app'],
            'access_method' => ['required', 'string', 'in:fhir_api,ehi_export,patient_portal'],
            'status' => ['required', 'string', 'in:approved,pending,denied'],
            'exception_reason' => ['nullable', 'string', 'in:security,privacy,infeasibility,harm_prevention,none'],
            'notes' => ['nullable', 'string'],
        ]);

        $ehiRequest = EhiRequest::create(array_merge($validated, [
            'organization_id' => $this->organizationId(),
        ]));

        // Log audit event for PHI request access check
        \App\Models\AuditLog::create([
            'organization_id' => $this->organizationId(),
            'user_id' => auth()->id(),
            'event' => 'log_compliance_request',
            'auditable_type' => EhiRequest::class,
            'auditable_id' => $ehiRequest->id,
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Compliance access request logged successfully.',
            'request' => $ehiRequest->load('patient'),
        ], 201);
    }

    public function updateRequest(Request $request, EhiRequest $ehiRequest): JsonResponse
    {
        if (! $this->belongsToOrganization($ehiRequest)) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:approved,pending,denied'],
            'exception_reason' => ['nullable', 'string', 'in:security,privacy,infeasibility,harm_prevention,none'],
            'notes' => ['nullable', 'string'],
        ]);

        $ehiRequest->update($validated);

        return response()->json([
            'message' => 'Request status updated successfully.',
            'request' => $ehiRequest->load('patient'),
        ]);
    }

    public function getExports(): JsonResponse
    {
        $exports = EhiExport::query()
            ->where('organization_id', $this->organizationId())
            ->with('patient')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($exports);
    }

    public function generateExport(Request $request, EhiExportService $service): JsonResponse
    {
        $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
        ]);

        $patient = Patient::findOrFail($request->integer('patient_id'));
        if (! $this->belongsToOrganization($patient)) {
            abort(403);
        }

        // Generate JSON data using EhiExportService
        $exportData = $service->generateExportData($patient);

        // Store JSON in private storage
        $fileName = 'ehi-exports/' . $patient->uuid . '-' . time() . '.json';
        \Storage::put($fileName, json_encode($exportData, JSON_PRETTY_PRINT));

        // Create EhiExport database entry
        $ehiExport = EhiExport::create([
            'organization_id' => $this->organizationId(),
            'patient_id' => $patient->id,
            'export_type' => 'single',
            'status' => 'completed',
            'file_path' => $fileName,
            'requested_by' => auth()->user()?->name ?? 'Clinical User',
            'completed_at' => now(),
        ]);

        // Audit Trail log
        \App\Models\AuditLog::create([
            'organization_id' => $this->organizationId(),
            'user_id' => auth()->id(),
            'event' => 'ehi_export_generated',
            'auditable_type' => Patient::class,
            'auditable_id' => $patient->id,
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Computable EHI export package generated successfully.',
            'export' => $ehiExport->load('patient'),
        ], 201);
    }

    public function downloadExport(EhiExport $ehiExport)
    {
        if (! $this->belongsToOrganization($ehiExport)) {
            abort(403);
        }

        if (! \Storage::exists($ehiExport->file_path)) {
            abort(404, 'Export file not found on disk.');
        }

        // Fetch patient code for download naming
        $ehiExport->load('patient');
        $fileName = $ehiExport->patient 
            ? 'ehi-export-' . strtolower($ehiExport->patient->last_name) . '-' . $ehiExport->id . '.json'
            : 'ehi-export-bulk-' . $ehiExport->id . '.json';

        // Audit trail log for EHI download
        \App\Models\AuditLog::create([
            'organization_id' => $this->organizationId(),
            'user_id' => auth()->id(),
            'event' => 'ehi_export_downloaded',
            'auditable_type' => EhiExport::class,
            'auditable_id' => $ehiExport->id,
            'ip_address' => request()->ip(),
        ]);

        return \Storage::download($ehiExport->file_path, $fileName);
    }

    public function viewFhirPatient(Patient $patient, EhiExportService $service): JsonResponse
    {
        if (! $this->belongsToOrganization($patient)) {
            abort(403);
        }

        $exportData = $service->generateExportData($patient);

        // Map to FHIR-like representation
        $fhir = [
            'resourceType' => 'Patient',
            'id' => $patient->uuid,
            'meta' => [
                'profile' => ['http://hl7.org/fhir/us/core/StructureDefinition/us-core-patient']
            ],
            'identifier' => [
                ['system' => 'http://hospital.org/mrn', 'value' => $patient->mrn]
            ],
            'active' => true,
            'name' => [
                [
                    'use' => 'official',
                    'family' => $patient->last_name,
                    'given' => [$patient->first_name]
                ]
            ],
            'telecom' => array_filter([
                $patient->phone ? ['system' => 'phone', 'value' => $patient->phone, 'use' => 'home'] : null,
                $patient->email ? ['system' => 'email', 'value' => $patient->email] : null,
            ]),
            'gender' => $patient->gender === 'female' ? 'female' : 'male',
            'birthDate' => $patient->date_of_birth,
            'uscdi_problems' => $exportData['clinical_notes_and_encounters'],
            'uscdi_medications' => $exportData['medications_and_prescriptions'],
            'uscdi_allergies' => $exportData['allergies'],
            'uscdi_vitals' => $exportData['vitals'],
            'uscdi_labs' => $exportData['lab_results'],
        ];

        return response()->json($fhir);
    }
}
