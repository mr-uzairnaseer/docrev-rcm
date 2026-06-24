<?php

namespace App\Support\CmsReference;

use Illuminate\Support\Facades\File;

class CmsBillingCodeCatalog
{
    public static function modifiers(): array
    {
        return self::loadJson('modifiers.json');
    }

    public static function claimAdjustmentCodes(): array
    {
        return self::loadJson('carc.json');
    }

    public static function remittanceRemarkCodes(): array
    {
        return self::loadJson('rarc.json');
    }

    public static function typeOfBillCodes(): array
    {
        return self::loadJson('type-of-bill.json');
    }

    public static function revenueCodes(): array
    {
        return self::loadJson('revenue-codes.json');
    }

    public static function datasetKeys(): array
    {
        return [
            'regions',
            'states',
            'macs',
            'payers',
            'medicare_advantage',
            'qhp_issuers',
            'pos',
            'taxonomy',
            'hcpcs',
            'icd10',
            'modifiers',
            'carc',
            'rarc',
            'type_of_bill',
            'revenue_codes',
        ];
    }

    private static function loadJson(string $filename): array
    {
        $path = database_path('data/cms/'.$filename);
        if (! File::exists($path)) {
            return [];
        }

        $data = json_decode(File::get($path), true);

        return is_array($data) ? $data : [];
    }
}
