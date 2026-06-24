<?php

return [

    'driver' => env('ELIGIBILITY_DRIVER', 'stub'),

    'availity' => [
        'api_url' => env('AVAILITY_API_URL', 'https://api.availity.com'),
        'client_id' => env('AVAILITY_CLIENT_ID'),
        'client_secret' => env('AVAILITY_CLIENT_SECRET'),
    ],

    'change_healthcare' => [
        'api_url' => env('CHANGE_HEALTHCARE_API_URL', 'https://apis.changehealthcare.com'),
        'client_id' => env('CHANGE_HEALTHCARE_CLIENT_ID'),
        'client_secret' => env('CHANGE_HEALTHCARE_CLIENT_SECRET'),
    ],

];
