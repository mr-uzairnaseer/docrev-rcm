<?php

namespace App\Support;

class TrainingGuide
{
    public static function modules(): array
    {
        return [
            [
                'id' => 'onboarding',
                'title' => 'Staff Onboarding',
                'audience' => 'All users',
                'duration_minutes' => 45,
                'topics' => [
                    'Logging in and password hygiene',
                    'Navigating the EHR sidebar and patient chart sub-tabs',
                    'Understanding your role permissions (RBAC)',
                ],
            ],
            [
                'id' => 'scheduling',
                'title' => 'Scheduling & Check-In',
                'audience' => 'Front desk, nurses',
                'duration_minutes' => 30,
                'topics' => [
                    'Calendar grid and appointment requests',
                    'Approving portal requests and check-in workflow',
                    'Telehealth appointment types',
                ],
            ],
            [
                'id' => 'charting',
                'title' => 'Clinical Charting',
                'audience' => 'Providers, nurses',
                'duration_minutes' => 60,
                'topics' => [
                    'Opening a clinical file and documenting problems, allergies, vitals',
                    'Encounter notes, diagnoses, and charge capture',
                    'Signing encounters and billing sync',
                ],
            ],
            [
                'id' => 'rcm-bridge',
                'title' => 'RCM & Clearinghouse Handoff',
                'audience' => 'Billers, org admins',
                'duration_minutes' => 40,
                'topics' => [
                    'How signed encounters become claims in billing',
                    'HCFA / UB-04 preview from synced encounters',
                    'Eligibility verification workflow',
                ],
            ],
            [
                'id' => 'hipaa',
                'title' => 'HIPAA & Security Awareness',
                'audience' => 'All users',
                'duration_minutes' => 25,
                'topics' => [
                    'Minimum necessary access and audit logs',
                    'PHI handling, MFA readiness, and incident reporting',
                    'Vendor BAAs for Surescripts, clearinghouse, and HIE',
                ],
            ],
            [
                'id' => 'integrations',
                'title' => 'Integrations Setup',
                'audience' => 'Org admins',
                'duration_minutes' => 35,
                'topics' => [
                    'Surescripts, lab, and HIE configuration checklist',
                    'Billing and portal API keys',
                    'Running docrev:requirements before go-live',
                ],
            ],
        ];
    }
}
