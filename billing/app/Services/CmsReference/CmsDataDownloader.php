<?php

namespace App\Services\CmsReference;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use ZipArchive;

class CmsDataDownloader
{
    public function ensureBundledDatasets(): array
    {
        $results = [];
        $dataDir = database_path('data/cms');
        File::ensureDirectoryExists($dataDir);

        $results['nucc_taxonomy'] = $this->ensureFile(
            $dataDir.'/nucc_taxonomy.csv',
            'https://www.nucc.org/images/stories/CSV/nucc_taxonomy_251.csv'
        );

        $results['medicare_advantage_contracts'] = $this->ensureMedicareAdvantageExport($dataDir);
        $results['hcpcs'] = $this->ensureHcpcsExport($dataDir);
        $results['icd10'] = $this->ensureIcd10Export($dataDir);

        return $results;
    }

    public function ensureDataset(string $dataset): bool
    {
        $dataDir = database_path('data/cms');
        File::ensureDirectoryExists($dataDir);

        return match ($dataset) {
            'taxonomy' => $this->ensureFile(
                $dataDir.'/nucc_taxonomy.csv',
                'https://www.nucc.org/images/stories/CSV/nucc_taxonomy_251.csv'
            ),
            'medicare_advantage' => $this->ensureMedicareAdvantageExport($dataDir),
            'hcpcs' => $this->ensureHcpcsExport($dataDir),
            'icd10' => $this->ensureIcd10Export($dataDir),
            default => true,
        };
    }

    private function ensureFile(string $target, string $url): bool
    {
        if (File::exists($target) && File::size($target) > 1000) {
            return true;
        }

        $response = Http::timeout(120)->get($url);
        if (! $response->successful()) {
            return File::exists($target);
        }

        File::put($target, $response->body());

        return true;
    }

    private function ensureMedicareAdvantageExport(string $dataDir): bool
    {
        $target = $dataDir.'/medicare-advantage-contracts.csv';
        if (File::exists($target) && File::size($target) > 1000) {
            return true;
        }

        $zipPath = storage_path('app/cms-downloads/ma-enrollment.zip');
        File::ensureDirectoryExists(dirname($zipPath));

        if (! File::exists($zipPath)) {
            $response = Http::timeout(120)->get('https://www.cms.gov/files/zip/monthly-enrollment-contract-january-2024.zip');
            if (! $response->successful()) {
                return false;
            }
            File::put($zipPath, $response->body());
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            return false;
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (str_ends_with(strtolower($name), '.csv')) {
                $contents = $zip->getFromIndex($i);
                if ($contents !== false) {
                    File::put($target, $contents);

                    return true;
                }
            }
        }

        throw new RuntimeException('Medicare Advantage CSV not found in CMS archive.');
    }

    private function ensureHcpcsExport(string $dataDir): bool
    {
        $target = $dataDir.'/hcpcs-2025-jan.txt';
        if (File::exists($target) && File::size($target) > 1000) {
            return true;
        }

        $zipPath = storage_path('app/cms-downloads/hcpcs.zip');
        File::ensureDirectoryExists(dirname($zipPath));

        if (! File::exists($zipPath)) {
            $response = Http::timeout(180)->get('https://www.cms.gov/files/zip/january-2025-alpha-numeric-hcpcs-file.zip');
            if (! $response->successful()) {
                return false;
            }
            File::put($zipPath, $response->body());
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            return false;
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (str_contains(strtolower($name), 'anweb') && str_ends_with(strtolower($name), '.txt')) {
                $contents = $zip->getFromIndex($i);
                if ($contents !== false) {
                    File::put($target, $contents);

                    return true;
                }
            }
        }

        return false;
    }

    private function ensureIcd10Export(string $dataDir): bool
    {
        $target = $dataDir.'/icd10cm_codes_2025.txt';
        if (File::exists($target) && File::size($target) > 1000) {
            return true;
        }

        $zipPath = storage_path('app/cms-downloads/icd10.zip');
        File::ensureDirectoryExists(dirname($zipPath));

        if (! File::exists($zipPath)) {
            $response = Http::timeout(180)->get('https://www.cms.gov/files/zip/2025-code-descriptions-tabular-order.zip');
            if (! $response->successful()) {
                return File::exists($target);
            }
            File::put($zipPath, $response->body());
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            return File::exists($target);
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (str_contains(strtolower($name), 'icd10cm_codes_') && str_ends_with(strtolower($name), '.txt')) {
                $contents = $zip->getFromIndex($i);
                if ($contents !== false) {
                    File::put($target, $contents);

                    return true;
                }
            }
        }

        return File::exists($target);
    }
}
