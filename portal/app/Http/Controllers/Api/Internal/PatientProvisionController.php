<?php

namespace App\Http\Controllers\Api\Internal;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\PatientAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PatientProvisionController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'organization.slug' => ['required', 'string'],
            'organization.name' => ['required', 'string'],
            'patient.uuid' => ['required', 'uuid'],
            'patient.billing_patient_uuid' => ['nullable', 'uuid'],
            'patient.first_name' => ['required', 'string'],
            'patient.last_name' => ['required', 'string'],
            'patient.date_of_birth' => ['required', 'date'],
            'patient.email' => ['nullable', 'email'],
            'patient.phone' => ['nullable', 'string'],
        ]);

        $organization = Organization::firstOrCreate(
            ['slug' => $validated['organization']['slug']],
            ['name' => $validated['organization']['name'], 'timezone' => 'America/New_York']
        );

        $email = $validated['patient']['email']
            ?? strtolower($validated['patient']['first_name'].'.'.$validated['patient']['last_name'].'@patient.test');

        $account = PatientAccount::firstOrNew(['ehr_patient_uuid' => $validated['patient']['uuid']]);
        $account->fill([
            'organization_id' => $organization->id,
            'billing_patient_uuid' => $validated['patient']['billing_patient_uuid'],
            'first_name' => $validated['patient']['first_name'],
            'last_name' => $validated['patient']['last_name'],
            'date_of_birth' => $validated['patient']['date_of_birth'],
            'email' => $email,
            'phone' => $validated['patient']['phone'],
            'is_active' => true,
        ]);

        if (! $account->exists || ! $account->password) {
            $account->password = Hash::make('password');
        }

        $account->save();

        return response()->json([
            'message' => 'Patient account provisioned in portal.',
            'portal_account_id' => $account->id,
            'email' => $account->email,
        ]);
    }
}
