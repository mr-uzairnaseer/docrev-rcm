<?php

namespace App\Services\Compliance;

use App\Models\Patient;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Storage;

class EhiExportService
{
    /**
     * Generate EHI export dataset for a specific patient in computable JSON format.
     */
    public function generateExportData(Patient $patient): array
    {
        // Load all clinical files
        $patient->load([
            'encounters.diagnoses',
            'encounters.charges',
            'prescriptions.provider',
            'prescriptions.pharmacy',
            'labOrders.labVendor',
            'patientForms',
            'vitals',
            'documents'
        ]);

        // Get audit logs related to this patient
        $auditLogs = AuditLog::query()
            ->where('auditable_type', Patient::class)
            ->where('auditable_id', $patient->id)
            ->orderByDesc('created_at')
            ->get();

        return [
            'meta' => [
                'standard' => 'ONC EHI Export §170.315(b)(10)',
                'framework' => 'USCDI Version 3 / US Core v5.0.1',
                'exported_at' => now()->toIso8601String(),
                'export_id' => uniqid('EHI-'),
            ],
            'uscdi_patient' => [
                'identifier' => [
                    ['system' => 'MRN', 'value' => $patient->mrn],
                    ['system' => 'UUID', 'value' => $patient->uuid],
                ],
                'name' => [
                    'family' => $patient->last_name,
                    'given' => [$patient->first_name],
                    'text' => $patient->first_name . ' ' . $patient->last_name,
                ],
                'telecom' => array_filter([
                    $patient->email ? ['system' => 'email', 'value' => $patient->email] : null,
                    $patient->phone ? ['system' => 'phone', 'value' => $patient->phone] : null,
                ]),
                'gender' => $patient->gender,
                'birthDate' => $patient->date_of_birth,
            ],
            'clinical_notes_and_encounters' => $patient->encounters->map(fn ($encounter) => [
                'encounter_id' => $encounter->id,
                'date' => $encounter->encounter_date,
                'status' => $encounter->status,
                'notes' => $encounter->clinical_notes,
                'diagnoses' => $encounter->diagnoses->map(fn ($dx) => [
                    'code' => $dx->icd10_code,
                    'description' => $dx->description,
                    'system' => 'ICD-10-CM'
                ]),
                'charges' => $encounter->charges->map(fn ($ch) => [
                    'code' => $ch->cpt_code,
                    'amount' => $ch->charge_amount,
                    'system' => 'CPT'
                ]),
            ]),
            'medications_and_prescriptions' => $patient->prescriptions->map(fn ($rx) => [
                'prescription_id' => $rx->id,
                'drug' => [
                    'name' => $rx->drug_name,
                    'ndc' => $rx->ndc,
                ],
                'dosage_instruction' => $rx->sig,
                'quantity' => $rx->quantity,
                'status' => $rx->status,
                'prescribed_by' => $rx->provider ? 'Dr. ' . $rx->provider->last_name : 'System',
                'pharmacy' => $rx->pharmacy ? $rx->pharmacy->name : null,
                'date' => $rx->created_at->toDateString(),
            ]),
            'allergies' => is_array($patient->allergies) ? $patient->allergies : [],
            'vitals' => $patient->vitals->map(fn ($v) => [
                'recorded_at' => $v->recorded_at,
                'blood_pressure' => $v->bp_systolic && $v->bp_diastolic ? [
                    'systolic' => $v->bp_systolic,
                    'diastolic' => $v->bp_diastolic,
                    'unit' => 'mmHg'
                ] : null,
                'heart_rate' => $v->heart_rate ? ['value' => $v->heart_rate, 'unit' => 'bpm'] : null,
                'temperature' => $v->temperature_f ? ['value' => $v->temperature_f, 'unit' => 'F'] : null,
                'weight' => $v->weight_lb ? ['value' => $v->weight_lb, 'unit' => 'lb'] : null,
                'oxygen_saturation' => $v->spo2 ? ['value' => $v->spo2, 'unit' => '%'] : null,
            ]),
            'lab_results' => $patient->labOrders->map(fn ($lab) => [
                'order_id' => $lab->id,
                'test_code' => $lab->test_code,
                'test_name' => $lab->test_name,
                'status' => $lab->status,
                'vendor' => $lab->labVendor ? $lab->labVendor->name : null,
                'results' => is_array($lab->results) ? $lab->results : [],
                'ordered_date' => $lab->created_at->toDateString(),
            ]),
            'documents' => $patient->documents->map(fn ($doc) => [
                'id' => $doc->id,
                'title' => $doc->title,
                'type' => $doc->document_type,
                'file_name' => $doc->file_name,
                'uploaded_at' => $doc->created_at->toIso8601String(),
            ]),
            'signed_forms' => $patient->patientForms->map(fn ($form) => [
                'form_name' => $form->form_name,
                'status' => $form->status,
                'signature_name' => $form->signature_name,
                'signed_at' => $form->signed_at,
            ]),
            'phi_access_audit_trail' => $auditLogs->map(fn ($log) => [
                'timestamp' => $log->created_at->toIso8601String(),
                'user' => $log->user ? $log->user->name : 'System',
                'event' => $log->event,
                'ip_address' => $log->ip_address,
            ]),
        ];
    }
}
