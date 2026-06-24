<?php

namespace App\Support;

class IntegrationRequirements
{
    public static function all(): array
    {
        return [
            'organization' => self::organization(),
            'clearinghouse' => self::clearinghouse(),
            'eligibility' => self::eligibility(),
            'ehr_sync' => self::ehrSync(),
            'portal_sync' => self::portalSync(),
            'cms_reference' => self::cmsReference(),
            'infrastructure' => self::infrastructure(),
        ];
    }

    public static function allForOrganization(int $organizationId): array
    {
        $payersMissingIds = \App\Models\Payer::forOrganization($organizationId)
            ->where(function ($q) {
                $q->whereNull('electronic_payer_id')->orWhere('electronic_payer_id', '');
            })
            ->count();

        return [
            'organization' => self::organizationSection($organizationId),
            'clearinghouse' => self::clearinghouse(),
            'eligibility' => self::eligibilityForOrganization($payersMissingIds),
            'ehr_sync' => self::ehrSync(),
            'portal_sync' => self::portalSync(),
            'cms_reference' => self::cmsReference(),
            'infrastructure' => self::infrastructure(),
        ];
    }

    public static function cmsReference(): array
    {
        $hasData = \App\Models\CmsState::query()->exists();

        return [
            'label' => 'CMS Reference Data',
            'ready' => $hasData,
            'note' => $hasData
                ? 'CMS datasets loaded. Re-import from Setup or run php artisan docrev:cms-import.'
                : 'Run php artisan docrev:cms-import --fresh to load states, payers, ICD-10, HCPCS, and billing codes.',
            'missing' => $hasData ? [] : ['CMS reference import'],
            'you_provide' => [
                'Periodic refresh of NUCC taxonomy and CMS code files',
                'Optional --only=icd10,hcpcs for selective updates',
                'Map organization payers to cms_reference_payer_id',
            ],
        ];
    }

    public static function organization(): array
    {
        return self::organizationSection(null);
    }

    public static function organizationSection(?int $organizationId): array
    {
        $missing = [];
        if ($organizationId) {
            $org = \App\Models\Organization::find($organizationId);
            if ($org) {
                if (! $org->npi) {
                    $missing[] = 'Organization NPI (Type 2)';
                }
                if (! $org->tax_id) {
                    $missing[] = 'Federal Tax ID (EIN)';
                }
                if (! is_array($org->address) || empty($org->address)) {
                    $missing[] = 'Billing address';
                }
                if (\App\Models\OrganizationProvider::forOrganization($organizationId)->count() === 0) {
                    $missing[] = 'Rendering provider NPI (Type 1)';
                }
            }
        }

        $posLoaded = \App\Models\CmsPlaceOfServiceCode::query()->exists();

        return [
            'label' => 'Practice / Organization',
            'ready' => $missing === [] && $posLoaded,
            'note' => $posLoaded
                ? 'Import name, NPI, address, and rendering providers from NPPES on Setup. Only EIN must be entered manually.'
                : 'Run docrev:cms-import to load CMS place-of-service codes.',
            'missing' => array_merge($missing, $posLoaded ? [] : ['CMS place-of-service codes']),
            'you_provide' => [
                'Federal Tax ID (EIN) — manual entry only',
                'Organization NPI (Type 2) — NPPES lookup & apply',
                'Rendering provider NPIs (Type 1) — NPPES lookup or discover',
                'Legal name & billing address — filled from NPPES when you apply org NPI',
            ],
        ];
    }

    private static function eligibilityForOrganization(int $payersMissingIds): array
    {
        $section = self::eligibility();
        if ($payersMissingIds > 0 && config('eligibility.driver', 'stub') !== 'stub') {
            $section['ready'] = false;
            $section['missing'] = array_merge($section['missing'] ?? [], [
                "{$payersMissingIds} payer(s) missing electronic payer ID",
            ]);
            $section['note'] = trim(($section['note'] ?? '').' Sync payer IDs from CMS Reference or enter manually.');
        }

        return $section;
    }

    public static function clearinghouse(): array
    {
        $driver = config('clearinghouse.driver', 'stub');

        if ($driver === 'stub') {
            return [
                'label' => 'Clearinghouse (837 submit / 835 ERA)',
                'driver' => 'stub',
                'ready' => true,
                'note' => 'Using stub sandbox. Set CLEARINGHOUSE_DRIVER and credentials for live submission.',
                'missing' => [],
                'you_provide' => self::clearinghouseCredentialsFor($driver),
            ];
        }

        $missing = self::missingEnvKeys(self::clearinghouseEnvKeys($driver));

        return [
            'label' => 'Clearinghouse (837 submit / 835 ERA)',
            'driver' => $driver,
            'ready' => $missing === [],
            'missing' => $missing,
            'you_provide' => self::clearinghouseCredentialsFor($driver),
        ];
    }

    public static function eligibility(): array
    {
        $driver = config('eligibility.driver', 'stub');

        if ($driver === 'stub') {
            return [
                'label' => 'Eligibility (270/271)',
                'driver' => 'stub',
                'ready' => true,
                'note' => 'Using stub provider. Set ELIGIBILITY_DRIVER for live checks.',
                'missing' => [],
                'you_provide' => self::eligibilityCredentialsFor($driver),
            ];
        }

        $missing = self::missingEnvKeys(self::eligibilityEnvKeys($driver));

        return [
            'label' => 'Eligibility (270/271)',
            'driver' => $driver,
            'ready' => $missing === [],
            'missing' => $missing,
            'you_provide' => self::eligibilityCredentialsFor($driver),
        ];
    }

    public static function ehrSync(): array
    {
        $missing = self::missingEnvKeys(['INTERNAL_API_KEY']);

        return [
            'label' => 'EHR → Billing sync',
            'ready' => $missing === [],
            'note' => 'EHR must set BILLING_API_URL and BILLING_API_KEY matching billing INTERNAL_API_KEY.',
            'missing' => $missing,
            'you_provide' => [
                'BILLING_API_URL on EHR (e.g. http://billing.yourdomain.com)',
                'Shared INTERNAL_API_KEY / BILLING_API_KEY (same value both apps)',
            ],
        ];
    }

    public static function portalSync(): array
    {
        $missing = self::missingEnvKeys(['PORTAL_API_URL', 'INTERNAL_API_KEY']);

        return [
            'label' => 'Billing → Portal statement sync',
            'ready' => $missing === [],
            'missing' => $missing,
            'you_provide' => [
                'PORTAL_API_URL on billing (e.g. http://portal.yourdomain.com)',
                'INTERNAL_API_KEY on portal (same key as billing)',
            ],
        ];
    }

    public static function infrastructure(): array
    {
        $isSqlite = config('database.default') === 'sqlite';
        $queue = config('queue.default');

        return [
            'label' => 'Production infrastructure',
            'ready' => ! $isSqlite && $queue !== 'sync',
            'note' => $isSqlite
                ? 'SQLite is fine for local dev. Use MySQL + Redis queues in production.'
                : ($queue === 'sync' ? 'Set QUEUE_CONNECTION=redis and run queue workers.' : 'Database and queue look production-ready.'),
            'missing' => array_filter([
                $isSqlite ? 'MySQL (or PostgreSQL) database' : null,
                $queue === 'sync' ? 'Redis queue + php artisan queue:work' : null,
                env('APP_ENV') === 'local' ? 'APP_ENV=production + APP_DEBUG=false' : null,
            ]),
            'you_provide' => [
                'PHP 8.2+ server (Laravel 11 upgrade path)',
                'MySQL 8 (docrev_ehr, docrev_billing, docrev_portal)',
                'Redis for queues and cache',
                'TLS certificates (HTTPS)',
                'Backup strategy for PHI',
            ],
        ];
    }

    private static function clearinghouseEnvKeys(string $driver): array
    {
        return match ($driver) {
            'availity' => ['AVAILITY_CLIENT_ID', 'AVAILITY_CLIENT_SECRET', 'AVAILITY_SUBMITTER_ID'],
            'change_healthcare' => ['CHANGE_HEALTHCARE_CLIENT_ID', 'CHANGE_HEALTHCARE_CLIENT_SECRET', 'CHANGE_HEALTHCARE_SUBMITTER_ID'],
            'sftp' => ['CLEARINGHOUSE_SFTP_HOST', 'CLEARINGHOUSE_SFTP_USERNAME'],
            default => [],
        };
    }

    private static function eligibilityEnvKeys(string $driver): array
    {
        return match ($driver) {
            'availity', 'change_healthcare' => self::clearinghouseEnvKeys($driver),
            default => [],
        };
    }

    private static function clearinghouseCredentialsFor(string $driver): array
    {
        return match ($driver) {
            'availity' => [
                'Availity developer account + API credentials (client ID / secret)',
                'Submitter ID assigned by Availity',
                'Payer enrollment per insurance company',
                'Trading partner agreement',
            ],
            'change_healthcare' => [
                'Change Healthcare / Optum API credentials',
                'Submitter ID',
                'Payer enrollment',
            ],
            'sftp' => [
                'SFTP host, username, password or SSH key',
                'Inbound/outbound folder paths from vendor',
                'Payer enrollment with clearinghouse vendor',
            ],
            default => [
                'Clearinghouse vendor choice (Availity, Change Healthcare, Waystar, etc.)',
                'API or SFTP credentials from vendor',
                'Submitter ID and receiver ID',
                'Payer enrollment for each insurance you bill',
            ],
        };
    }

    private static function eligibilityCredentialsFor(string $driver): array
    {
        return match ($driver) {
            'availity', 'change_healthcare' => [
                'Same API credentials as clearinghouse (usually)',
                'Payer ID / electronic payer IDs in system',
                'Member ID format per payer',
            ],
            default => [
                'Eligibility vendor access (often same as clearinghouse)',
                'Payer electronic IDs configured in billing payers table',
            ],
        };
    }

    private static function missingEnvKeys(array $keys): array
    {
        $missing = [];
        foreach ($keys as $key) {
            if (! env($key)) {
                $missing[] = $key;
            }
        }

        return $missing;
    }
}
