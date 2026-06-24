<?php

namespace App\Console\Commands;

use App\Support\IntegrationRequirements;
use Illuminate\Console\Command;

class DocRevRequirementsCommand extends Command
{
    protected $signature = 'docrev:requirements';

    protected $description = 'Show EHR integration requirements and project plan status';

    public function handle(): int
    {
        $plan = \App\Support\EhrProjectPlan::summary();

        $this->info('DocRev EHR — Project Plan Summary');
        $this->line('Functional: '.$plan['completion']['functional'].'% | Technical: '.$plan['completion']['technical'].'% | Compliance: '.$plan['completion']['compliance'].'%');
        $this->newLine();
        $this->info('DocRev EHR — Integration Requirements');
        $this->newLine();

        foreach (IntegrationRequirements::all() as $section) {
            $status = $section['ready'] ? '<fg=green>READY</>' : '<fg=yellow>NEEDS SETUP</>';
            $this->line("<fg=cyan>{$section['label']}</> [{$status}]");

            if (! empty($section['driver'])) {
                $this->line("  Driver: {$section['driver']}");
            }

            if (! empty($section['note'])) {
                $this->line("  {$section['note']}");
            }

            if (! empty($section['missing'])) {
                $this->line('  Missing env: '.implode(', ', $section['missing']));
            }

            if (! empty($section['you_provide'])) {
                $this->line('  You provide:');
                foreach ($section['you_provide'] as $item) {
                    $this->line("    • {$item}");
                }
            }

            $this->newLine();
        }

        return self::SUCCESS;
    }
}
