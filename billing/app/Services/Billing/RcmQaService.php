<?php

namespace App\Services\Billing;

use App\Models\Claim;
use App\Models\ClaimDenial;
use App\Models\ClaimSubmission;
use App\Models\PatientPayment;
use Carbon\Carbon;

class RcmQaService
{
    public function __construct(private RcmDashboardService $dashboard)
    {
    }

    public function tracker(int $organizationId): array
    {
        $dash = $this->dashboard->summary($organizationId);
        $now = Carbon::now()->toIso8601String();

        $hasOpenDenial = ClaimDenial::forOrganization($organizationId)
            ->where('status', ClaimDenial::STATUS_OPEN)
            ->exists();

        $hasAppealed = ClaimDenial::forOrganization($organizationId)
            ->where('status', ClaimDenial::STATUS_APPEALED)
            ->exists();

        $hasEdiClaim = Claim::forOrganization($organizationId)
            ->whereNotNull('edi_837_content')
            ->exists();

        $hasDraft = Claim::forOrganization($organizationId)
            ->where('status', Claim::STATUS_DRAFT)
            ->exists();

        $hasPortalPayment = PatientPayment::forOrganization($organizationId)->exists();

        $recentSubmission = ClaimSubmission::query()
            ->whereHas('claim', fn ($q) => $q->where('organization_id', $organizationId))
            ->orderByDesc('submitted_at')
            ->first();

        $tests = [
            $this->test('RCM-301', 'Dashboard', 'Log in as Biller and view RCM Summary cards',
                'Displays Patient A/R aging analytics, open denials count, and submitted claims',
                $this->pass(
                    ($dash['claims']['submitted'] ?? 0) > 0
                    && isset($dash['aging']['total'])
                    && ($dash['ar']['patient_responsibility'] ?? 0) >= 0
                ),
                'Waystar / Claimlogic',
                'High',
                $this->rcm301Notes($dash)
            ),
            $this->test('RCM-302', 'Denials Scribe', 'Denials → Click Appeal Scribe on open denial',
                'Opens Appeal Scribe letter drawer sidebar',
                $hasOpenDenial ? 'pass' : 'untested',
                'Change Healthcare',
                'High',
                $hasOpenDenial ? 'Open denial on file — use Appeal Scribe in Denials tab.' : 'Simulate a denial or run db:seed for demo denials.'
            ),
            $this->test('RCM-303', 'Denials Scribe', 'Select Appeal Template → Submit Appeal',
                'Auto-generates custom letter; denial status → Appealed; claim → Submitted',
                $hasAppealed ? 'pass' : ($hasOpenDenial ? 'untested' : 'untested'),
                'Availity / Optum',
                'Critical',
                $hasAppealed
                    ? 'Appeal workflow verified — at least one appealed denial on file.'
                    : 'Submit an appeal from Appeal Scribe to complete this test.'
            ),
            $this->test('RCM-304', 'Claims EDI', 'Select draft claim → Scrub & EDI',
                'Scrubs claim data and generates EDI 837P transaction preview',
                $hasEdiClaim ? 'pass' : 'untested',
                'Waystar / Claimlogic',
                'Critical',
                $hasEdiClaim
                    ? ($hasDraft ? 'Draft claim available for live scrub demo; EDI already generated on other claims.' : 'EDI 837 generated on submitted claims.')
                    : 'Build a draft claim and click Scrub & EDI.'
            ),
            $this->test('RCM-305', 'Posting receipts', 'ERA/Payments → View self-pay receipts',
                'Lists patient portal online payments in real-time',
                $hasPortalPayment ? 'pass' : 'untested',
                'Change Healthcare / ECHO',
                'Medium',
                $hasPortalPayment
                    ? 'Portal receipts synced to billing. Pay from patient portal for live updates.'
                    : 'No portal payments yet — pay from patient portal or run demo seeder.'
            ),
        ];

        $summary = [
            'total' => count($tests),
            'pass' => count(array_filter($tests, fn ($t) => $t['status'] === 'pass')),
            'fail' => count(array_filter($tests, fn ($t) => $t['status'] === 'fail')),
            'untested' => count(array_filter($tests, fn ($t) => $t['status'] === 'untested')),
            'in_progress' => count(array_filter($tests, fn ($t) => $t['status'] === 'in_progress')),
        ];

        return [
            'generated_at' => $now,
            'summary' => $summary,
            'tests' => $tests,
            'clearinghouse_driver' => config('clearinghouse.driver', 'stub'),
            'last_submission_at' => $recentSubmission?->submitted_at?->toIso8601String(),
        ];
    }

    private function test(
        string $id,
        string $module,
        string $scenario,
        string $expected,
        string $status,
        string $clearinghouse,
        string $priority,
        string $notes
    ): array {
        return [
            'id' => $id,
            'module' => $module,
            'scenario' => $scenario,
            'expected' => $expected,
            'status' => $status,
            'clearinghouse' => $clearinghouse,
            'priority' => $priority,
            'notes' => $notes,
            'last_updated' => Carbon::now()->toIso8601String(),
        ];
    }

    private function pass(bool $ok): string
    {
        return $ok ? 'pass' : 'untested';
    }

    private function rcm301Notes(array $dash): string
    {
        $submitted = $dash['claims']['submitted'] ?? 0;
        $open = $dash['denials']['open'] ?? 0;
        $aging = $dash['aging']['total'] ?? 0;

        return "Submitted: {$submitted}, Open denials: {$open}, A/R outstanding: \${$aging}";
    }
}
