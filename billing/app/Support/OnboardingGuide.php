<?php

namespace App\Support;

class OnboardingGuide
{
    public static function intro(): string
    {
        return 'Everything works in stub/sandbox mode today. For live claims, eligibility, and patient payments, complete the items below.';
    }

    public static function sections(): array
    {
        return [
            'organization' => [
                'title' => '1. Practice / organization info',
                'subtitle' => 'Legal name, organization NPI, billing address, and rendering provider NPIs can be imported from the CMS NPPES registry. Only the Federal Tax ID (EIN) must be entered manually — it is not published in NPPES.',
                'action' => 'On Setup: lookup your Type-2 organization NPI and click Apply, then discover Type-1 rendering providers at your practice. Enter your EIN from IRS records.',
                'items' => [
                    [
                        'what' => 'Legal practice name',
                        'why' => 'Claims, statements',
                        'how' => 'NPPES lookup (Type-2 org NPI) → Apply to organization',
                        'link' => 'https://npiregistry.cms.hhs.gov/',
                        'source' => 'nppes',
                    ],
                    [
                        'what' => 'Organization NPI (Type 2)',
                        'why' => 'Billing provider on 837',
                        'how' => 'NPPES lookup → Apply to organization',
                        'link' => 'https://npiregistry.cms.hhs.gov/',
                        'source' => 'nppes',
                    ],
                    [
                        'what' => 'Federal Tax ID (EIN)',
                        'why' => 'Payer/clearinghouse enrollment',
                        'how' => 'IRS — enter manually on Setup (not available in NPPES)',
                        'source' => 'local',
                    ],
                    [
                        'what' => 'Billing address',
                        'why' => 'Claim header',
                        'how' => 'NPPES lookup (Type-2 org NPI) → Apply to organization',
                        'link' => 'https://npiregistry.cms.hhs.gov/',
                        'source' => 'nppes',
                    ],
                    [
                        'what' => 'Rendering provider NPIs (Type 1)',
                        'why' => 'Service lines',
                        'how' => 'NPPES lookup each physician, or Discover providers at your practice name',
                        'link' => 'https://npiregistry.cms.hhs.gov/',
                        'source' => 'nppes',
                    ],
                    [
                        'what' => 'Place of service codes',
                        'why' => 'Claim lines (e.g. 11 = office)',
                        'how' => 'CMS POS code list — DocRev loads HL7/CMS POS into CMS Reference',
                        'link' => 'https://www.cms.gov/medicare/coding-billing/place-of-service-codes',
                        'source' => 'cms_pos',
                    ],
                ],
            ],
            'clearinghouse' => [
                'title' => '2. Clearinghouse — claims submit (837) + ERA (835)',
                'subtitle' => 'Pick one vendor: Availity, Change Healthcare (Optum), Waystar, Office Ally, etc.',
                'action' => 'Contact vendor sales, request sandbox credentials, complete payer enrollment, then set billing/.env and use Test Clearinghouse.',
                'items' => [
                    [
                        'what' => 'Vendor choice',
                        'env_vars' => ['CLEARINGHOUSE_DRIVER=availity|change_healthcare|sftp'],
                        'how' => 'Sign up with vendor',
                    ],
                    [
                        'what' => 'API client ID & secret',
                        'env_vars' => ['AVAILITY_CLIENT_ID', 'AVAILITY_CLIENT_SECRET', 'CHANGE_HEALTHCARE_CLIENT_ID', 'CHANGE_HEALTHCARE_CLIENT_SECRET'],
                        'how' => 'Vendor developer portal after contract',
                    ],
                    [
                        'what' => 'Submitter ID',
                        'env_vars' => ['AVAILITY_SUBMITTER_ID', 'CHANGE_HEALTHCARE_SUBMITTER_ID'],
                        'how' => 'Assigned by clearinghouse during onboarding',
                    ],
                    [
                        'what' => 'Payer enrollment',
                        'how' => 'Enroll each insurance you bill through the clearinghouse — 2–6 weeks per payer',
                    ],
                    [
                        'what' => 'SFTP (if used)',
                        'env_vars' => ['CLEARINGHOUSE_SFTP_HOST', 'CLEARINGHOUSE_SFTP_USERNAME', 'CLEARINGHOUSE_SFTP_PASSWORD'],
                        'how' => 'Vendor onboarding packet',
                    ],
                ],
                'vendors' => ClearinghouseVendorCatalog::all(),
            ],
            'eligibility' => [
                'title' => '3. Eligibility (270/271)',
                'subtitle' => 'Usually same vendor and credentials as the clearinghouse.',
                'action' => 'Set ELIGIBILITY_DRIVER and map payer electronic IDs in Billing Payers.',
                'items' => [
                    [
                        'what' => 'Driver',
                        'env_vars' => ['ELIGIBILITY_DRIVER=availity|change_healthcare|stub'],
                        'how' => 'Same vendor account as clearinghouse',
                    ],
                    [
                        'what' => 'API credentials',
                        'how' => 'Same as clearinghouse — included with API access',
                    ],
                    [
                        'what' => 'Payer electronic IDs',
                        'how' => 'Enter in Billing Payers table — sync from CMS Reference payer directory',
                        'source' => 'cms_payers',
                    ],
                ],
            ],
            'ehr_sync' => [
                'title' => '4. EHR → Billing sync',
                'items' => [
                    [
                        'what' => 'Shared internal API key',
                        'env_vars' => ['INTERNAL_API_KEY', 'BILLING_API_KEY (EHR)'],
                        'how' => 'Generate a strong key; set on billing and EHR',
                    ],
                    [
                        'what' => 'Billing API URL on EHR',
                        'env_vars' => ['BILLING_API_URL (EHR)'],
                        'how' => 'Point EHR to billing app URL',
                    ],
                ],
            ],
            'portal_sync' => [
                'title' => '5. Billing → Portal statement sync',
                'items' => [
                    [
                        'what' => 'Portal API URL on billing',
                        'env_vars' => ['PORTAL_API_URL'],
                        'how' => 'Set on billing app',
                    ],
                    [
                        'what' => 'Internal API key on portal',
                        'env_vars' => ['INTERNAL_API_KEY (portal)'],
                        'how' => 'Same key as billing',
                    ],
                ],
            ],
            'cms_reference' => [
                'title' => '6. CMS reference data (auto-loaded)',
                'subtitle' => 'DocRev imports public CMS/NUCC datasets for payers, codes, and jurisdictions.',
                'action' => 'Run php artisan docrev:cms-import --fresh or use CMS Reference → Re-import.',
                'items' => [
                    ['what' => 'States, MACs, Medicaid/CHIP payers', 'source' => 'cms_gov'],
                    ['what' => 'ICD-10-CM, HCPCS, modifiers, CARC/RARC', 'source' => 'cms_gov'],
                    ['what' => 'Place of service, taxonomy, revenue codes', 'source' => 'cms_gov'],
                ],
            ],
        ];
    }
}
