<?php

return [

    'app_type' => env('DOCREV_APP_TYPE', 'portal'),

    'roles' => [
        'super_admin',
        'org_admin',
        'provider',
        'nurse',
        'biller',
        'front_desk',
        'patient',
    ],

    'billing_api_url' => env('BILLING_API_URL', 'http://localhost:8002'),

    'internal_api_key' => env('INTERNAL_API_KEY', 'docrev-internal-dev-key'),

];
