<?php

namespace App\Console\Commands;

use App\Services\CmsReference\CmsReferenceImporter;
use App\Support\CmsReference\CmsBillingCodeCatalog;
use Illuminate\Console\Command;

class CmsReferenceImportCommand extends Command
{
    protected $signature = 'docrev:cms-import
                            {--fresh : Replace existing CMS reference data}
                            {--no-download : Skip downloading latest CMS/NUCC files}
                            {--only= : Comma-separated datasets to import (e.g. icd10,hcpcs,modifiers)}';

    protected $description = 'Import CMS reference data (states, MACs, payers, codes, and billing references)';

    public function handle(CmsReferenceImporter $importer): int
    {
        $only = $this->parseOnly($this->option('only'));

        if ($only !== null) {
            $this->info('Importing CMS datasets: '.implode(', ', $only));
        } else {
            $this->info('Importing full CMS reference datasets...');
        }

        $counts = $importer->import($this->option('fresh'), ! $this->option('no-download'), $only);

        foreach ($counts as $type => $count) {
            $this->line(sprintf('  %-24s %d', str_replace('_', ' ', ucfirst($type)).':', $count));
        }

        $this->info('CMS reference import complete.');

        return self::SUCCESS;
    }

    private function parseOnly(?string $value): ?array
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $keys = array_map('trim', explode(',', strtolower($value)));
        $valid = CmsBillingCodeCatalog::datasetKeys();

        return array_values(array_intersect($valid, $keys));
    }
}
