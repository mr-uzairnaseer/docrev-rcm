<?php

namespace App\Console\Commands;

use App\Support\IntegrationRequirements;
use Illuminate\Console\Command;

class DocRevRequirementsCommand extends Command
{
    protected $signature = 'docrev:requirements';

    protected $description = 'Show what is configured and what you need to provide for live functionality';

    public function handle(): int
    {
        $this->info('DocRev Clearinghouse — Integration Requirements');
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
