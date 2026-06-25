<?php

namespace App\Http\Controllers\Api;

use App\Models\AutoPostingRule;
use App\Models\EftDeposit;
use App\Models\EftEnrollment;
use App\Models\EraRemittance;
use App\Services\Billing\ReassociationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EftController extends ApiController
{
    public function getEnrollment(): JsonResponse
    {
        $enrollment = EftEnrollment::query()
            ->where('organization_id', $this->organizationId())
            ->first();

        if (! $enrollment) {
            // Seed a default one with organisation info if available
            $org = \App\Models\Organization::find($this->organizationId());
            $enrollment = EftEnrollment::create([
                'organization_id' => $this->organizationId(),
                'legal_name' => $org?->name,
                'npi' => $org?->npi,
                'tax_id' => $org?->tax_id,
                'address' => $org?->address,
                'medicare_eft_status' => 'not_started',
                'commercial_payer_status' => [],
                'era_enrollment_status' => [],
                'vcc_policy' => 'accept',
                'onboarding_checklist' => [
                    'provider_identity' => false,
                    'bank_info' => false,
                    'medicare_eft' => false,
                    'commercial_eft' => false,
                    'era_enrollment' => false,
                    'reassociation' => false,
                    'auto_posting' => false,
                    'reconciliation' => false,
                    'vcc_policy' => false,
                    'sop_complete' => false,
                ],
            ]);
        }

        return response()->json($enrollment);
    }

    public function updateEnrollment(Request $request): JsonResponse
    {
        $enrollment = EftEnrollment::query()
            ->where('organization_id', $this->organizationId())
            ->firstOrCreate(['organization_id' => $this->organizationId()]);

        $data = $request->only([
            'legal_name', 'dba', 'npi', 'tax_id', 'ptan', 'address',
            'contact_name', 'contact_phone', 'contact_email',
            'bank_routing', 'bank_account', 'bank_account_type', 'authorized_signer',
            'medicare_eft_status', 'commercial_payer_status', 'era_enrollment_status',
            'vcc_policy', 'onboarding_checklist'
        ]);

        // Only update if present in request to prevent overwriting with null
        $enrollment->update(array_filter($data, fn ($val) => $val !== null));

        return response()->json([
            'message' => 'EFT profile updated successfully.',
            'enrollment' => $enrollment,
        ]);
    }

    public function getDeposits(): JsonResponse
    {
        $deposits = EftDeposit::query()
            ->where('organization_id', $this->organizationId())
            ->with(['payer', 'eraRemittance'])
            ->orderByDesc('deposit_date')
            ->orderByDesc('id')
            ->get();

        return response()->json($deposits);
    }

    public function createDeposit(Request $request, ReassociationService $service): JsonResponse
    {
        $request->validate([
            'trace_number' => 'required|string',
            'amount' => 'required|numeric',
            'deposit_date' => 'required|date',
            'payer_id' => 'nullable|exists:payers,id',
        ]);

        $deposit = EftDeposit::create([
            'organization_id' => $this->organizationId(),
            'trace_number' => $request->string('trace_number'),
            'amount' => $request->float('amount'),
            'deposit_date' => $request->date('deposit_date'),
            'payer_id' => $request->input('payer_id'),
            'matched_status' => 'unmatched',
        ]);

        $service->associateDeposit($deposit);

        return response()->json([
            'message' => 'Bank EFT deposit created successfully.',
            'deposit' => $deposit->load(['payer', 'eraRemittance']),
        ], 201);
    }

    public function manualReassociate(Request $request, ReassociationService $service): JsonResponse
    {
        $request->validate([
            'deposit_id' => 'required|exists:eft_deposits,id',
            'era_remittance_id' => 'required|exists:era_remittances,id',
        ]);

        $service->manualMatch(
            $request->integer('deposit_id'),
            $request->integer('era_remittance_id')
        );

        return response()->json([
            'message' => 'Manual reassociation matched successfully.',
        ]);
    }

    public function getRules(): JsonResponse
    {
        $rules = AutoPostingRule::query()
            ->where('organization_id', $this->organizationId())
            ->get();

        return response()->json($rules);
    }

    public function saveRules(Request $request): JsonResponse
    {
        $rules = $request->input('rules', []);
        
        DB::transaction(function () use ($rules) {
            foreach ($rules as $key => $val) {
                AutoPostingRule::updateOrCreate(
                    [
                        'organization_id' => $this->organizationId(),
                        'rule_key' => $key,
                    ],
                    [
                        'rule_value' => $val,
                    ]
                );
            }
        });

        return response()->json([
            'message' => 'Auto-posting rules updated successfully.',
        ]);
    }

    public function getReconciliationReport(): JsonResponse
    {
        $orgId = $this->organizationId();
        
        $totalDeposits = (float) EftDeposit::where('organization_id', $orgId)->sum('amount');
        $postedAmount = (float) EftDeposit::where('organization_id', $orgId)
            ->where('matched_status', 'matched')
            ->sum('amount');
        
        $unpostedAmount = (float) EftDeposit::where('organization_id', $orgId)
            ->where('matched_status', 'unmatched')
            ->sum('amount');

        $exceptionsCount = EftDeposit::where('organization_id', $orgId)
            ->where('matched_status', 'exception')
            ->count();

        $dailyStats = EftDeposit::where('organization_id', $orgId)
            ->select('deposit_date', DB::raw('SUM(amount) as total_amount'), DB::raw('COUNT(*) as count'))
            ->groupBy('deposit_date')
            ->orderByDesc('deposit_date')
            ->limit(10)
            ->get();

        return response()->json([
            'total_deposits' => $totalDeposits,
            'posted_amount' => $postedAmount,
            'unposted_amount' => $unpostedAmount,
            'exceptions_count' => $exceptionsCount,
            'daily_stats' => $dailyStats,
        ]);
    }
}
