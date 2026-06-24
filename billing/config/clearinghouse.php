<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Clearinghouse Driver
    |--------------------------------------------------------------------------
    | stub              — local sandbox (default)
    | availity          — Availity Essentials / API
    | change_healthcare — Optum Change Healthcare
    | sftp              — Generic SFTP drop (837 out / 835 in)
    */
    'driver' => env('CLEARINGHOUSE_DRIVER', 'stub'),

    'availity' => [
        'api_url' => env('AVAILITY_API_URL', 'https://api.availity.com'),
        'client_id' => env('AVAILITY_CLIENT_ID'),
        'client_secret' => env('AVAILITY_CLIENT_SECRET'),
        'submitter_id' => env('AVAILITY_SUBMITTER_ID'),
        'receiver_id' => env('AVAILITY_RECEIVER_ID', '030240928'),
        'scope' => env('AVAILITY_SCOPE', 'hipaa'),
    ],

    'change_healthcare' => [
        'api_url' => env('CHANGE_HEALTHCARE_API_URL', 'https://apis.changehealthcare.com'),
        'client_id' => env('CHANGE_HEALTHCARE_CLIENT_ID'),
        'client_secret' => env('CHANGE_HEALTHCARE_CLIENT_SECRET'),
        'submitter_id' => env('CHANGE_HEALTHCARE_SUBMITTER_ID'),
    ],

    'sftp' => [
        'host' => env('CLEARINGHOUSE_SFTP_HOST'),
        'port' => env('CLEARINGHOUSE_SFTP_PORT', 22),
        'username' => env('CLEARINGHOUSE_SFTP_USERNAME'),
        'password' => env('CLEARINGHOUSE_SFTP_PASSWORD'),
        'private_key_path' => env('CLEARINGHOUSE_SFTP_PRIVATE_KEY'),
        'outbound_path' => env('CLEARINGHOUSE_SFTP_OUTBOUND', '/outbound/837'),
        'inbound_path' => env('CLEARINGHOUSE_SFTP_INBOUND', '/inbound/835'),
    ],

];
