<?php

return [

    'driver' => env('HIE_DRIVER', 'stub'),

    'default_connection' => env('HIE_DEFAULT_CONNECTION'),

    'fhir' => [
        'version' => env('HIE_FHIR_VERSION', 'R4'),
        'timeout' => env('HIE_FHIR_TIMEOUT', 30),
    ],

];
