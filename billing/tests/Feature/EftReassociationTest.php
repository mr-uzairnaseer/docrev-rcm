<?php

namespace Tests\Feature;

use App\Models\EftDeposit;
use App\Models\EftEnrollment;
use App\Models\EraRemittance;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EftReassociationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::create([
            'name' => 'Test Practice',
            'slug' => 'test-practice',
        ]);

        $this->user = User::create([
            'organization_id' => $this->organization->id,
            'name' => 'Biller User',
            'email' => 'biller@example.com',
            'password' => bcrypt('password'),
            'role' => 'biller',
            'is_active' => true,
        ]);
    }

    public function test_can_get_and_update_eft_enrollment(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/eft/enrollment');

        $response->assertStatus(200);
        $response->assertJsonFragment(['medicare_eft_status' => 'not_started']);

        $updateResponse = $this->actingAs($this->user)
            ->postJson('/api/eft/enrollment', [
                'legal_name' => 'Updated LLC',
                'bank_routing' => '123456789',
                'bank_account' => '987654321',
                'medicare_eft_status' => 'enrolled',
            ]);

        $updateResponse->assertStatus(200);
        $this->assertDatabaseHas('eft_enrollments', [
            'organization_id' => $this->organization->id,
            'legal_name' => 'Updated LLC',
            'medicare_eft_status' => 'enrolled',
        ]);

        // Verify routing & account are encrypted in DB
        $raw = \DB::table('eft_enrollments')->where('organization_id', $this->organization->id)->first();
        $this->assertNotEquals('123456789', $raw->bank_routing);
        $this->assertNotEquals('987654321', $raw->bank_account);

        // Verify decryption when getting profile
        $getResponse = $this->actingAs($this->user)->getJson('/api/eft/enrollment');
        $this->assertEquals('123456789', $getResponse->json('bank_routing'));
        $this->assertEquals('987654321', $getResponse->json('bank_account'));
    }

    public function test_eft_deposit_auto_associates_with_era(): void
    {
        // 1. Create Era Remittance
        $era = EraRemittance::create([
            'organization_id' => $this->organization->id,
            'trace_number' => 'TRN-XYZ-123',
            'edi_835_content' => 'ISA*00...',
            'total_payment_amount' => 150.00,
            'claim_count' => 1,
            'matched_count' => 1,
            'status' => 'posted',
            'posted_at' => now(),
        ]);

        // 2. Post Bank Deposit with matching trace number
        $response = $this->actingAs($this->user)
            ->postJson('/api/eft/deposits', [
                'trace_number' => 'TRN-XYZ-123',
                'amount' => 150.00,
                'deposit_date' => now()->toDateString(),
            ]);

        $response->assertStatus(201);
        $response->assertJsonFragment(['matched_status' => 'matched']);

        $this->assertDatabaseHas('eft_deposits', [
            'organization_id' => $this->organization->id,
            'trace_number' => 'TRN-XYZ-123',
            'matched_status' => 'matched',
            'era_remittance_id' => $era->id,
        ]);
    }

    public function test_eft_reconciliation_report_returns_correct_sums(): void
    {
        // Create a matched deposit
        EftDeposit::create([
            'organization_id' => $this->organization->id,
            'trace_number' => 'TRN-MATCH',
            'amount' => 200.00,
            'deposit_date' => now()->toDateString(),
            'matched_status' => 'matched',
        ]);

        // Create an unmatched deposit
        EftDeposit::create([
            'organization_id' => $this->organization->id,
            'trace_number' => 'TRN-UNMATCH',
            'amount' => 50.00,
            'deposit_date' => now()->toDateString(),
            'matched_status' => 'unmatched',
        ]);

        // Create an exception deposit
        EftDeposit::create([
            'organization_id' => $this->organization->id,
            'trace_number' => 'TRN-EXCEPTION',
            'amount' => 100.00,
            'deposit_date' => now()->toDateString(),
            'matched_status' => 'exception',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/eft/reconciliation-report');

        $response->assertStatus(200);
        $response->assertJson([
            'total_deposits' => 350.00,
            'posted_amount' => 200.00,
            'unposted_amount' => 50.00,
            'exceptions_count' => 1,
        ]);
    }
}
