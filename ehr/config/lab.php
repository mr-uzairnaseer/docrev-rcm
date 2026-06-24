<?php

return [

    'driver' => env('LAB_INTERFACE_DRIVER', 'stub'),

    'default_vendor' => env('LAB_DEFAULT_VENDOR', 'demo_lab'),

    'hl7' => [
        'sending_application' => env('LAB_SENDING_APPLICATION', 'DOCREV_EHR'),
        'sending_facility' => env('LAB_SENDING_FACILITY', 'DEMO_MEDICAL'),
        'version' => '2.5.1',
    ],

    'fhir' => [
        'base_url' => env('LAB_FHIR_BASE_URL'),
        'client_id' => env('LAB_FHIR_CLIENT_ID'),
        'client_secret' => env('LAB_FHIR_CLIENT_SECRET'),
    ],

];
