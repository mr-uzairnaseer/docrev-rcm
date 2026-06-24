<?php

namespace App\Http\Controllers\Api\Internal;

use App\Http\Controllers\Controller;
use App\Models\PatientAccount;
use App\Models\PortalPatientForm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientFormSyncController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ehr_patient_uuid' => ['required', 'uuid'],
            'form' => ['required', 'array'],
            'form.uuid' => ['required', 'uuid'],
            'form.form_name' => ['required', 'string'],
            'form.status' => ['required', 'string'],
            'form.form_content' => ['required', 'string'],
        ]);

        $account = PatientAccount::where('ehr_patient_uuid', $data['ehr_patient_uuid'])->first();
        if (!$account) {
            return response()->json(['message' => 'Patient account not found on portal.'], 404);
        }

        $form = PortalPatientForm::updateOrCreate([
            'external_form_uuid' => $data['form']['uuid']
        ], [
            'patient_account_id' => $account->id,
            'form_name' => $data['form']['form_name'],
            'status' => $data['form']['status'],
            'form_content' => $data['form']['form_content'],
        ]);

        return response()->json([
            'success' => true,
            'form' => $form
        ], 200);
    }
}
