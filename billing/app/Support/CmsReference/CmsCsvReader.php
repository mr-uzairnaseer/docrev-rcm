<?php

namespace App\Support\CmsReference;

use RuntimeException;

class CmsCsvReader
{
    public static function rows(string $path): array
    {
        if (! is_readable($path)) {
            return [];
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            return [];
        }

        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);

            return [];
        }

        $headers = array_map(fn ($header) => trim(trim((string) $header, '"')), $headers);
        $rows = [];

        while (($data = fgetcsv($handle)) !== false) {
            if ($data === [null] || $data === []) {
                continue;
            }

            $row = [];
            foreach ($headers as $index => $header) {
                $row[$header] = isset($data[$index]) ? trim((string) $data[$index], " \t\n\r\0\x0B\"") : null;
            }
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    public static function requireRows(string $path): array
    {
        $rows = self::rows($path);
        if ($rows === []) {
            throw new RuntimeException('CMS dataset is missing or empty: '.$path);
        }

        return $rows;
    }
}
