<?php

namespace App\Support;

class ClearinghouseVendorCatalog
{
    public static function all(): array
    {
        return [
            [
                'id' => 'availity',
                'name' => 'Availity',
                'driver' => 'availity',
                'signup' => 'https://www.availity.com/',
                'developer' => 'https://developer.availity.com/',
                'supports' => ['837', '835', '270/271'],
            ],
            [
                'id' => 'change_healthcare',
                'name' => 'Change Healthcare (Optum)',
                'driver' => 'change_healthcare',
                'signup' => 'https://www.changehealthcare.com/',
                'developer' => 'https://developers.changehealthcare.com/',
                'supports' => ['837', '835', '270/271'],
            ],
            [
                'id' => 'waystar',
                'name' => 'Waystar',
                'driver' => 'sftp',
                'signup' => 'https://www.waystar.com/',
                'supports' => ['837', '835'],
            ],
            [
                'id' => 'office_ally',
                'name' => 'Office Ally',
                'driver' => 'sftp',
                'signup' => 'https://www.officeally.com/',
                'supports' => ['837', '835'],
            ],
        ];
    }
}
