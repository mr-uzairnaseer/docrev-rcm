<?php

namespace App\Support;

class AppFeatures
{
    public static function modules(): array
    {
        return [
            ['id' => 'dashboard', 'label' => 'Clearinghouse Dashboard', 'enabled' => true],
            ['id' => 'eligibility', 'label' => 'Eligibility (270/271)', 'enabled' => true],
            ['id' => 'charges', 'label' => 'Charge Capture', 'enabled' => true],
            ['id' => 'claims', 'label' => 'Claims & 837', 'enabled' => true],
            ['id' => 'eras', 'label' => 'ERA / 835 Posting', 'enabled' => true],
            ['id' => 'denials', 'label' => 'Denial Management', 'enabled' => true],
            ['id' => 'cms_reference', 'label' => 'CMS Reference Data', 'enabled' => true],
            ['id' => 'integration_setup', 'label' => 'Integration Setup', 'enabled' => true],
        ];
    }

    public static function workspaceOptions(): array
    {
        return [
            'default_cms_tab' => [
                'label' => 'Default CMS tab',
                'type' => 'select',
                'default' => 'payers',
                'choices' => [
                    'payers', 'medicare-advantage', 'qhp', 'states', 'macs', 'icd10',
                    'hcpcs', 'modifiers', 'carc', 'rarc', 'tob', 'revenue', 'pos', 'taxonomy',
                ],
            ],
            'default_per_page' => [
                'label' => 'Default results per page',
                'type' => 'select',
                'default' => '200',
                'choices' => ['50', '100', '200', '500'],
            ],
            'default_place_of_service' => [
                'label' => 'Default place of service (claims)',
                'type' => 'select',
                'default' => '11',
                'choices' => ['11', '02', '10', '20', '21', '22', '23', '24', '31', '32'],
            ],
            'eligibility_auto_refresh' => [
                'label' => 'Refresh eligibility list after check',
                'type' => 'boolean',
                'default' => true,
            ],
            'cms_export_max_rows' => [
                'label' => 'CMS CSV export row limit',
                'type' => 'select',
                'default' => '1000',
                'choices' => ['500', '1000', '2000', '5000'],
            ],
        ];
    }

    public static function drivers(): array
    {
        return [
            'clearinghouse' => config('clearinghouse.driver', 'stub'),
            'eligibility' => config('eligibility.driver', 'stub'),
            'database' => config('database.default'),
            'queue' => config('queue.default'),
            'app_env' => config('app.env'),
        ];
    }
}
