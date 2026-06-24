<?php

namespace App\Support\CmsReference;

class CmsIcd10Parser
{
    /**
     * Parse CMS ICD-10-CM tabular codes file (code + description per line).
     *
     * @return array<int, array{code: string, description: string, is_billable: bool}>
     */
    public static function parseCodesFile(string $path): array
    {
        if (! is_readable($path)) {
            return [];
        }

        $codes = [];
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return [];
        }

        while (($line = fgets($handle)) !== false) {
            $line = rtrim($line, "\r\n");
            if ($line === '') {
                continue;
            }

            if (! preg_match('/^([A-TV-Z][0-9A-Z]{2,6})\s+(.+)$/', $line, $matches)) {
                continue;
            }

            $code = $matches[1];
            $description = trim($matches[2]);
            $isBillable = strlen($code) > 3 || preg_match('/\d{2,}$/', $code) === 1;

            $codes[$code] = [
                'code' => $code,
                'description' => $description,
                'is_billable' => $isBillable,
            ];
        }

        fclose($handle);

        return array_values($codes);
    }
}
