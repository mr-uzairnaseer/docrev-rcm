<?php

namespace App\Support\CmsReference;

class CmsHcpcsParser
{
    /**
     * Parse CMS fixed-width HCPCS contractor file (320 chars/record).
     *
     * @return array<int, array{code: string, short_description: string, long_description: string|null, category: string|null}>
     */
    public static function parseFile(string $path): array
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
            if (strlen($line) < 120) {
                continue;
            }

            $recordId = substr($line, 10, 1);
            if (! in_array($recordId, ['3', '7'], true)) {
                continue;
            }

            $code = trim(substr($line, 0, 5));
            if ($code === '') {
                continue;
            }

            $short = trim(substr($line, 91, 28));
            $long = trim(substr($line, 11, 80));

            $codes[$code] = [
                'code' => $code,
                'short_description' => $short !== '' ? $short : $code,
                'long_description' => $long !== '' ? $long : null,
                'category' => self::categoryForCode($code),
            ];
        }

        fclose($handle);

        return array_values($codes);
    }

    private static function categoryForCode(string $code): string
    {
        if (ctype_digit($code[0] ?? '')) {
            return 'cpt_level_i';
        }

        return match ($code[0]) {
            'A' => 'transportation_medical_supplies',
            'B' => 'enteral_parenteral',
            'C' => 'outpatient_pps',
            'D' => 'dental',
            'E' => 'durable_medical_equipment',
            'G' => 'temporary_procedures',
            'H' => 'behavioral_health',
            'J' => 'drugs_administered',
            'K' => 'temporary_dme',
            'L' => 'orthotic_procedures',
            'M' => 'quality_reporting',
            'P' => 'pathology_laboratory',
            'Q' => 'temporary_codes',
            'R' => 'diagnostic_radiology',
            'S' => 'temporary_national',
            'T' => 'state_medicaid',
            'U' => 'coronavirus',
            'V' => 'vision_hearing_speech',
            'W' => 'temporary_preventive',
            default => 'hcpcs_level_ii',
        };
    }
}
