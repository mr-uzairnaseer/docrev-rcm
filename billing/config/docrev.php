<?php

return [

    'app_type' => env('DOCREV_APP_TYPE', 'billing'),

    'roles' => [
        'super_admin',
        'org_admin',
        'provider',
        'nurse',
        'biller',
        'front_desk',
        'patient',
    ],

    'internal_api_key' => env('INTERNAL_API_KEY', 'docrev-internal-dev-key'),

    'portal_api_url' => env('PORTAL_API_URL', 'http://localhost:8003'),

];
