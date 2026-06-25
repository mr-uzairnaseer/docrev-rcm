<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Patient;
use App\Models\EhiRequest;
use App\Models\EhiExport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ComplianceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_can_log_interop_request()
    {
        $user = User::where('role', 'org_admin')->first();
        $patient = Patient::first();

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/interop/requests', [
            'patient_id' => $patient->id,
            'requestor_name' => 'Test Requestor',
            'requestor_type' => 'third_party_app',
            'access_method' => 'fhir_api',
            'status' => 'approved',
            'notes' => 'Compliance test log',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('request.requestor_name', 'Test Requestor');

        $this->assertDatabaseHas('ehi_requests', [
            'requestor_name' => 'Test Requestor',
            'access_method' => 'fhir_api',
        ]);
    }

    public function test_can_update_interop_request()
    {
        $user = User::where('role', 'org_admin')->first();
        $patient = Patient::first();

        Sanctum::actingAs($user, ['*']);

        $request = EhiRequest::create([
            'organization_id' => $user->organization_id,
            'patient_id' => $patient->id,
            'requestor_name' => 'Pending App',
            'requestor_type' => 'third_party_app',
            'access_method' => 'ehi_export',
            'status' => 'pending',
        ]);

        $response = $this->putJson("/api/interop/requests/{$request->id}", [
            'status' => 'denied',
            'exception_reason' => 'security',
            'notes' => 'Blocked due to security exception rule test',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('ehi_requests', [
            'id' => $request->id,
            'status' => 'denied',
            'exception_reason' => 'security',
        ]);
    }

    public function test_can_generate_and_download_ehi_export()
    {
        $user = User::where('role', 'org_admin')->first();
        $patient = Patient::first();

        Sanctum::actingAs($user, ['*']);

        // Generate EHI Export
        $response = $this->postJson('/api/interop/exports', [
            'patient_id' => $patient->id,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('export.status', 'completed');
        $exportId = $response->json('export.id');

        // Verify EhiExport record in DB
        $this->assertDatabaseHas('ehi_exports', [
            'id' => $exportId,
            'patient_id' => $patient->id,
        ]);

        // Download EHI Export
        $downloadResponse = $this->get("/api/interop/exports/{$exportId}/download");
        $downloadResponse->assertStatus(200);
        
        $export = EhiExport::findOrFail($exportId);
        $this->assertTrue(\Storage::exists($export->file_path));
        $data = json_decode(\Storage::get($export->file_path), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('uscdi_patient', $data);
        $this->assertEquals($patient->first_name, $data['uscdi_patient']['name']['given'][0]);
    }

    public function test_can_view_fhir_patient_resource()
    {
        $user = User::where('role', 'org_admin')->first();
        $patient = Patient::first();

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson("/api/interop/fhir-patient/{$patient->id}");
        $response->assertStatus(200);
        $response->assertJsonPath('resourceType', 'Patient');
        $response->assertJsonPath('id', $patient->uuid);
    }

    public function test_patient_duplicate_warning()
    {
        $user = User::where('role', 'org_admin')->first();
        $patient = Patient::first();

        Sanctum::actingAs($user, ['*']);

        // Attempting to store a duplicate patient should return HTTP 409
        $response = $this->postJson('/api/patients', [
            'first_name' => $patient->first_name,
            'last_name' => $patient->last_name,
            'date_of_birth' => $patient->date_of_birth->format('Y-m-d'),
            'gender' => 'female',
        ]);

        $response->assertStatus(409);
        $response->assertJsonPath('duplicate', true);

        // Submitting with ignore_duplicate = true should succeed
        $response = $this->postJson('/api/patients', [
            'first_name' => $patient->first_name,
            'last_name' => $patient->last_name,
            'date_of_birth' => $patient->date_of_birth->format('Y-m-d'),
            'gender' => 'female',
            'ignore_duplicate' => true,
        ]);

        $response->assertStatus(201);
    }

    public function test_can_filter_audit_logs()
    {
        $user = User::where('role', 'org_admin')->first();
        Sanctum::actingAs($user, ['*']);

        // Log some audit entries
        \App\Models\AuditLog::create([
            'organization_id' => $user->organization_id,
            'user_id' => $user->id,
            'auditable_type' => \App\Models\Patient::class,
            'auditable_id' => 1,
            'event' => 'test_event',
        ]);

        $response = $this->getJson('/api/audit-logs?event=test_event');
        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data'));
    }

    public function test_can_manage_tasks()
    {
        $user = User::where('role', 'org_admin')->first();
        Sanctum::actingAs($user, ['*']);

        // Create Task
        $response = $this->postJson('/api/tasks', [
            'title' => 'Review Lab CMP Results',
            'priority' => 'high',
            'due_date' => '2026-07-01',
        ]);
        $response->assertStatus(201);
        $taskId = $response->json('id');

        // List Tasks
        $response = $this->getJson('/api/tasks');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json());

        // Update Task status
        $response = $this->putJson("/api/tasks/{$taskId}", [
            'status' => 'completed',
        ]);
        $response->assertStatus(200);
        $this->assertEquals('completed', $response->json('status'));

        // Delete Task
        $response = $this->deleteJson("/api/tasks/{$taskId}");
        $response->assertStatus(200);
    }
}
