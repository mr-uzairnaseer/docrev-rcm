<?php

return [

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'billing' => [
        'url' => env('BILLING_API_URL', 'http://127.0.0.1:8002'),
        'api_key' => env('BILLING_API_KEY', 'docrev-internal-dev-key'),
        'timeout' => env('BILLING_API_TIMEOUT', 15),
    ],

    'portal' => [
        'url' => env('PORTAL_API_URL', 'http://127.0.0.1:8003'),
        'api_key' => env('PORTAL_API_KEY', env('INTERNAL_API_KEY', 'docrev-internal-dev-key')),
    ],

];
