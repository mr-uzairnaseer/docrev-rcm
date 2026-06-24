<?php

return [

    'app_type' => env('DOCREV_APP_TYPE', 'ehr'),

    'internal_api_key' => env('INTERNAL_API_KEY', 'docrev-internal-dev-key'),

    'roles' => [
        'super_admin',
        'org_admin',
        'provider',
        'nurse',
        'biller',
        'front_desk',
        'patient',
    ],

    'mfa_enabled' => env('DOCREV_MFA_ENABLED', false),

];
