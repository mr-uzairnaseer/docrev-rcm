<?php

namespace App\Console\Commands;

use App\Services\Integration\OrganizationOnboardingService;
use App\Support\OnboardingGuide;
use Illuminate\Console\Command;

class OnboardingSyncCommand extends Command
{
    protected $signature = 'docrev:onboarding-sync
        {--org=1 : Organization ID}
        {--npi= : Validate NPI via NPPES registry}
        {--apply : Apply NPI lookup to organization or rendering provider}
        {--discover-providers : Search NPPES for Type-1 providers at org name}';

    protected $description = 'Sync payer electronic IDs from CMS reference and show onboarding readiness';

    public function handle(OrganizationOnboardingService $onboarding): int
    {
        $orgId = (int) $this->option('org');

        $this->info('DocRev RCM — What You Need & How to Get It');
        $this->line(OnboardingGuide::intro());
        $this->newLine();

        if ($npi = $this->option('npi')) {
            try {
                if ($this->option('apply')) {
                    $result = $onboarding->applyFromNppes($orgId, $npi);
                    $this->line("<fg=green>Applied from NPPES:</> {$result['message']}");
                } else {
                    $result = $onboarding->lookupNpi($npi);
                    if ($result) {
                        $this->line("<fg=green>NPPES lookup OK:</> {$result['name']} ({$result['enumeration_type']}) — apply_as: {$result['apply_as']}");
                        if ($result['primary_practice_address']) {
                            $this->line("  {$result['primary_practice_address']}");
                        }
                    } else {
                        $this->warn('NPI not found in NPPES registry.');
                    }
                }
            } catch (\Throwable $e) {
                $this->error($e->getMessage());
            }
            $this->newLine();
        }

        if ($this->option('discover-providers')) {
            $discovered = $onboarding->discoverRenderingProviders($orgId, 10);
            $this->info("Type-1 providers at “{$discovered['organization_name']}”: ".count($discovered['candidates']));
            foreach ($discovered['candidates'] as $candidate) {
                $this->line("  {$candidate['name']} ({$candidate['npi']})");
            }
            $this->newLine();
        }

        $sync = $onboarding->syncPayerElectronicIds($orgId);
        $this->line($sync['message']);

        $ref = $onboarding->referenceDataSummary();
        $this->table(['Reference data', 'Count'], [
            ['POS codes (CMS)', $ref['place_of_service_codes']],
            ['Payers with electronic ID (CMS)', $ref['reference_payers_with_electronic_id']],
        ]);

        $profile = $onboarding->organizationProfile($orgId);
        $this->newLine();
        $this->info('Organization checks');
        foreach ($profile['checks'] as $key => $value) {
            $this->line(sprintf('  %-32s %s', str_replace('_', ' ', $key).':', is_bool($value) ? ($value ? 'yes' : 'no') : $value));
        }

        return self::SUCCESS;
    }
}
